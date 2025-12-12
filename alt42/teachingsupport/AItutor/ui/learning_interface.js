/**
 * AI 튜터 학습 인터페이스 JavaScript
 * 풀이 단계, 감정 상태, 펜 제스처 처리
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 * @version    1.0
 */

// ========== 상태 관리 ==========
const state = {
    // 풀이 단계 (통일: 문제해석, 식세우기, 풀이과정, 점검, 장기기억화)
    steps: [
        { id: 1, label: '문제해석', status: 'current', desc: '문제를 읽고 조건 파악' },
        { id: 2, label: '식세우기', status: 'pending', desc: '방정식/전략 설정' },
        { id: 3, label: '풀이과정', status: 'pending', desc: '계산 수행' },
        { id: 4, label: '점검', status: 'pending', desc: '답 확인 및 검산' },
        { id: 5, label: '장기기억화', status: 'pending', desc: '반복 연습' }
    ],
    stepSource: 'auto', // 'auto' | 'manual'
    
    // 감정 상태
    emotion: { type: 'neutral', source: 'auto' },
    autoDetectedEmotion: 'neutral',
    
    // 펜 제스처
    isDrawing: false,
    currentPath: [],
    recognizedGesture: null,
    
    // 페르소나 상태
    currentItemId: 1,
    currentPersonaType: null,
    isPositiveTransformed: false,
    personaHistory: [],
    personaSource: 'ai', // 'ai' | 'student'
    
    // 장기기억 활동 상태
    memoryActivity: {
        isActive: false,
        type: null,           // 'counter', 'timer', 'repeat'
        counter: 0,
        targetCount: 3,
        timerSeconds: 0,
        timerInterval: null,
        repeatCount: 0,
        targetRepeat: 3,
        completed: false
    },
    
    // 필기 지연 감지 상태
    writingDetection: {
        lastStrokeTime: 0,
        strokeCount: 0,
        pauseThreshold: 5000,     // 5초 이상 멈추면 분석 트리거
        isAnalyzing: false,
        pauseTimer: null,
        capturedImage: null,
        analysisCount: 0,
        maxAnalysisPerSession: 10  // 세션당 최대 분석 횟수
    },
    
    // TTS 상태
    tts: {
        isGenerating: false,
        isPlaying: false,
        interactionId: null,
        sections: [],
        textSections: [],
        currentSectionIndex: 0,
        hasGenerated: false,
        autoPlay: true,
        speed: 1.0,
        currentAudio: null
    },
    
    // 추천 페르소나 상태
    recommendedPersona: {
        persona: null,
        isDetailOpen: false,
        overcomeLevel: 0,
        overcomeHistory: [],
        audioPlaying: false
    },
    
    // FAQ 점층상호작용 상태
    faq: {
        data: null,             // faqtext JSON 데이터
        currentStepIndex: 0,    // 현재 표시 중인 단계 인덱스
        currentStepData: null,  // 현재 단계의 faqtext 데이터 객체
        currentFaqIndex: 0,     // 현재 표시 중인 FAQ 인덱스 (0-5)
        isDisplaying: false,    // FAQ 표시 중인지 여부
        displayTimer: null,     // 표시 타이머
        sessionGestureCount: 0, // 세션 내 X 제스처 총 횟수
        completedOnce: false    // 6개 모두 완료 여부
    }
};

// ========== FAQ 격려 메시지 (7번째 제스처 이후) ==========
const faqEncouragementMessages = [
    // 자신감 북돋우기 (1-10)
    "이제 스스로 해볼 시간이야! 💪",
    "충분히 들었어, 이제 네 차례야!",
    "자, 이제 직접 풀어보자! 🎯",
    "반복은 여기까지! 실전이다~",
    "이제 머릿속에 다 들어갔지? 고고! 🚀",
    "듣기만 하면 안 돼~ 직접 해봐!",
    "OK, 이제 네가 주인공이야! ⭐",
    "충분해! 이제 손으로 풀어보자 ✏️",
    "여기까지! 이제 실력 발휘 시간~",
    "들은 거 써먹어봐! 화이팅! 🔥",
    // 유머러스한 독려 (11-20)
    "더 듣고 싶어? 안 돼~ 이제 풀어! 😎",
    "귀로 배운 거 손으로 증명해봐!",
    "이 정도면 박사급인데? 직접 해봐!",
    "무한반복은 노래방에서만! 이제 풀자~",
    "뇌가 꽉 찼어! 출력할 시간이야 🖨️",
    "듣기 모드 OFF! 풀기 모드 ON! 🔛",
    "이제 선생님은 쉴게~ 네 차례야!",
    "반복의 신이 강림했다! 이제 실전! ⚡",
    "더 들으면 귀에서 수학이 흘러나와~",
    "충전 완료! 배터리 100%! 출발! 🔋",
    // 도전 의식 자극 (21-30)
    "네 실력 보여줄 때가 왔어! 🎪",
    "준비됐지? 실전에서 빛나봐! ✨",
    "이론은 끝! 액션 시작! 🎬",
    "연습은 충분해, 이제 진검승부!",
    "워밍업 완료! 본게임 돌입! 🏆",
    "듣는 건 여기까지! 푸는 건 네 몫!",
    "귀가 했으니 손이 할 차례야! ✋",
    "설명 듣기 레벨 MAX! 이제 풀기 도전!",
    "이해력 충전 완료! 실행력 발동! 💫",
    "이제 증명해봐, 네가 알고 있다는 걸!",
    // 가벼운 압박 (31-40)
    "슬슬 직접 해보는 게 어때? 🤔",
    "듣기만 하면 시험에서 울어~",
    "손이 심심해 보여! 풀어줘! ✏️",
    "머리로만 풀면 0점이야~ 써봐!",
    "이해했으면 증명해! 실전 고고!",
    "아는 것과 푸는 것은 달라~ 도전!",
    "눈으로 보고 손으로 안 하면 휘발!",
    "지금 안 풀면 내일 다 잊어버려~",
    "반복 청취의 함정! 직접 풀어야 내 것!",
    "듣기 연습 A+! 이제 풀기도 A+ 가자!",
    // 응원과 격려 (41-50)
    "할 수 있어! 한 번 해봐! 🌟",
    "틀려도 괜찮아! 도전이 중요해!",
    "첫 시도가 어려워도 해보는 거야!",
    "실수해도 OK! 그게 진짜 공부야!",
    "자신감 폭발! 넌 할 수 있어! 💥",
    "걱정 마! 이미 다 알고 있잖아!",
    "떨려도 일단 시작해봐! 🎵",
    "완벽하지 않아도 돼! 일단 도전!",
    "네 잠재력을 믿어! 풀어봐! 🔓",
    "시작이 반이야! 펜을 들어! 🖊️",
    // 재치있는 마무리 (51-60)
    "AI도 지쳤어~ 이제 네가 해줘! 🤖",
    "설명충 모드 종료! 실전 모드 시작!",
    "이 정도면 충분히 씹고 뜯었어! 삼켜!",
    "뇌세포들이 준비됐대! 출동시켜!",
    "수학의 신이 너를 부르고 있어! 📢",
    "지금이 골든타임! 바로 풀어!",
    "머릿속 지식, 종이 위로 대피시켜! 📝",
    "이해 완료! 이제 손맛을 보여줘!",
    "더 들으면 뇌 용량 초과야! 풀자! 💾",
    "마지막 경고! 이제 직접 풀 시간! ⏰"
];

// ========== 감정 데이터 ==========
const emotions = {
    confident: { icon: '😊', label: '자신있어', color: 'green' },
    neutral: { icon: '😐', label: '보통', color: 'gray' },
    confused: { icon: '🤔', label: '헷갈려', color: 'amber' },
    stuck: { icon: '😵', label: '막혔어', color: 'red' },
    anxious: { icon: '😰', label: '불안해', color: 'purple' }
};

// ========== 제스처 데이터 ==========
const gestures = {
    check: { symbol: '✓', meaning: '이해했어', feedback: '좋아! 다음 단계로 넘어갈까?' },
    x: { symbol: '✗', meaning: '아니야', feedback: '그럼 다른 방법으로 설명해줄게' },
    question: { symbol: '?', meaning: '모르겠어', feedback: '여기서 막혔구나. 힌트를 줄게' },
    circle: { symbol: '○', meaning: '확인해줘', feedback: '검토해볼게... 여기까지 잘했어!' },
    arrow: { symbol: '→', meaning: '다음으로', feedback: '알겠어, 진행할게' }
};

// ========== 초기화 ==========
document.addEventListener('DOMContentLoaded', function() {
    console.log('[learning_interface.js] 초기화 시작');
    
    try {
        // 기본 데이터 확인
        if (!state.steps || state.steps.length === 0) {
            console.log('[learning_interface.js] 기본 단계 데이터 사용');
        }
        
        // 순서대로 초기화
        initializeFromAnalysisData();
        initializePersonaSystem();
        initializeStagePersonaSystem();
        
        // 단계 렌더링 (약간의 딜레이로 DOM 안정화)
        setTimeout(() => {
            renderSteps();
            console.log('[learning_interface.js] 단계 렌더링 완료, 총 ' + state.steps.length + '개');
        }, 100);
        
        initGestureCanvas();
        startAutoUpdate();
        initWritingDetection();  // 필기 지연 감지 초기화
        initRecommendedPersona();  // 추천 페르소나 초기화
        initTtsState();  // TTS 상태 초기화
        
        console.log('[learning_interface.js] 초기화 완료');
    } catch (error) {
        console.error('[learning_interface.js] 초기화 오류:', error);
        // 오류 시에도 기본 단계는 표시
        renderSteps();
    }
});

// ========== 풀이 단계별 페르소나 시스템 ==========
let currentStage = '문제해석';

function initializeStagePersonaSystem() {
    // 초기 단계의 페르소나 렌더링
    renderStagePersonas('문제해석');
    
    // 현재 풀이 단계와 동기화
    syncStageWithCurrentStep();
}

function syncStageWithCurrentStep() {
    const currentStep = state.steps.find(s => s.status === 'current');
    if (!currentStep) return;
    
    // 단계 라벨과 매핑
    const stepToStage = {
        '문제해석': '문제해석',
        '식세우기': '식세우기',
        '풀이과정': '풀이과정',
        '점검': '점검',
        '장기기억화': '장기기억화'
    };
    
    const mappedStage = stepToStage[currentStep.label];
    if (mappedStage && mappedStage !== currentStage) {
        selectStageTab(mappedStage);
    }
}

function selectStageTab(stageName) {
    currentStage = stageName;
    
    // 탭 활성화 상태 업데이트
    document.querySelectorAll('.stage-tab').forEach(tab => {
        tab.classList.toggle('active', tab.dataset.stage === stageName);
    });
    
    // 헤더의 현재 단계 라벨 업데이트
    const stageLabel = document.getElementById('currentStageLabel');
    if (stageLabel) {
        stageLabel.textContent = stageName;
    }
    
    // 해당 단계의 페르소나 렌더링
    renderStagePersonas(stageName);
}

function renderStagePersonas(stageName) {
    const grid = document.getElementById('stagePersonaGrid');
    if (!grid) return;
    
    const stages = window.SOLVING_STAGES;
    const personas = window.PERSONAS_60;
    
    if (!stages || !personas) {
        grid.innerHTML = '<p class="loading-text">페르소나 데이터 로딩 중...</p>';
        return;
    }
    
    const stageData = stages[stageName];
    if (!stageData) {
        grid.innerHTML = '<p class="loading-text">해당 단계 정보 없음</p>';
        return;
    }
    
    // 해당 단계의 페르소나 ID로 필터링
    const stagePersonas = stageData.ids.map(id => personas.find(p => p.id === id)).filter(Boolean);
    
    if (stagePersonas.length === 0) {
        grid.innerHTML = '<p class="loading-text">해당 단계의 페르소나 없음</p>';
        return;
    }
    
    // 우선순위 색상 매핑
    const priorityColors = {
        'high': '#ef4444',
        'medium': '#f59e0b',
        'low': '#10b981'
    };
    
    grid.innerHTML = stagePersonas.map(p => `
        <div class="stage-persona-card" data-persona-id="${p.id}" onclick="showPersonaDetail(${p.id})">
            <div class="persona-priority-dot" style="background: ${priorityColors[p.priority] || priorityColors.medium}"></div>
            <div class="persona-card-icon">${p.icon}</div>
            <div class="persona-card-id">#${String(p.id).padStart(2, '0')}</div>
            <div class="persona-card-name">${p.name}</div>
            <div class="persona-card-category">${p.category}</div>
            <div class="persona-card-desc">${p.desc}</div>
        </div>
    `).join('');
}

function showPersonaDetail(personaId) {
    const personas = window.PERSONAS_60;
    const persona = personas.find(p => p.id === personaId);
    
    if (!persona) return;
    
    // 페르소나 선택 시 피드백 표시
    showFeedback(`${persona.icon} ${persona.name}: ${persona.desc.substring(0, 50)}...`);
    
    // 현재 페르소나로 설정
    state.currentPersonaType = `persona_${personaId}`;
    
    // 서버에 페르소나 선택 기록
    savePersonaInteraction('stage_persona_select', {
        persona_id: personaId,
        persona_name: persona.name,
        current_stage: currentStage,
        source: 'student'
    });
}

// ========== 페르소나 시스템 ==========
function initializePersonaSystem() {
    // 초기 페르소나 설정
    if (window.ITEM_PERSONAS && window.ITEM_PERSONAS.length > 0) {
        const firstItem = window.ITEM_PERSONAS[0];
        state.currentItemId = firstItem.item_id;
        state.currentPersonaType = firstItem.recommended_persona;
    }
    updatePersonaDisplay();
}

function togglePersonaPicker() {
    const picker = document.getElementById('personaPicker');
    const overlay = document.getElementById('personaPickerOverlay');
    const btn = document.getElementById('personaBtn');
    
    picker.classList.toggle('hidden');
    if (overlay) overlay.classList.toggle('hidden');
    btn.classList.toggle('open');
}

// 타로 카드 뒤집기 및 선택
function flipAndSelectCard(itemId) {
    const allCards = document.querySelectorAll('.tarot-card-wrapper');
    const clickedCard = document.querySelector(`.tarot-card-wrapper[data-item-id="${itemId}"]`);
    
    if (!clickedCard) return;
    
    // 이미 뒤집힌 상태면 다시 뒤집기
    if (clickedCard.classList.contains('flipped')) {
        clickedCard.classList.remove('flipped');
        return;
    }
    
    // 다른 카드들 앞면으로
    allCards.forEach(card => {
        if (card !== clickedCard) {
            card.classList.remove('flipped');
        }
    });
    
    // 클릭한 카드 뒤집기
    clickedCard.classList.add('flipped');
}

function selectItemPersona(itemId) {
    if (!window.ITEM_PERSONAS || !Array.isArray(window.ITEM_PERSONAS)) return;
    
    const itemPersona = window.ITEM_PERSONAS.find(ip => ip.item_id === itemId);
    if (!itemPersona) return;
    
    state.currentItemId = itemId;
    state.currentPersonaType = itemPersona.recommended_persona;
    state.isPositiveTransformed = true;
    state.personaSource = 'student'; // 학생이 직접 선택
    
    // 타로 카드 선택 UI 업데이트
    document.querySelectorAll('.tarot-card-wrapper').forEach(card => {
        const isSelected = parseInt(card.dataset.itemId) === itemId;
        card.classList.toggle('selected', isSelected);
    });
    
    // 기존 persona-item도 업데이트 (하위 호환)
    document.querySelectorAll('.persona-item').forEach(item => {
        const isSelected = parseInt(item.dataset.itemId) === itemId;
        item.classList.toggle('selected', isSelected);
        item.classList.remove('ai-recommended');
        if (isSelected) {
            item.classList.add('student-selected');
        } else {
            item.classList.remove('student-selected');
        }
    });
    
    // 페르소나 버튼 업데이트
    updatePersonaDisplay();
    updateSourceBadgeDisplay();
    
    // 긍정 페르소나 전환 배너 표시
    showPositiveGuidance(itemPersona.base_persona);
    
    // 피드백 표시
    showFeedback(`✓ 직접 선택: 문항 ${itemId} - ${itemPersona.base_persona.positive}로 전환!`);
    
    // 드롭다운 닫기
    togglePersonaPicker();
    
    // 서버에 페르소나 변경 기록
    savePersonaInteraction('item_persona_select', {
        item_id: itemId,
        from_persona: itemPersona.recommended_persona,
        to_positive_persona: itemPersona.base_persona.positive,
        item_text: itemPersona.item_text,
        source: 'student'
    });
    
    // 페르소나 히스토리 기록
    state.personaHistory.push({
        timestamp: new Date().toISOString(),
        item_id: itemId,
        persona_type: itemPersona.recommended_persona,
        action: 'positive_transform',
        source: 'student'
    });
}

function selectPersonaType(personaKey) {
    const basePersona = window.BASE_PERSONAS[personaKey];
    if (!basePersona) return;
    
    state.currentPersonaType = personaKey;
    state.isPositiveTransformed = true;
    state.personaSource = 'student'; // 학생이 직접 선택
    
    // 문항 아이템에서 ai-recommended 제거
    document.querySelectorAll('.persona-item').forEach(item => {
        item.classList.remove('ai-recommended', 'student-selected', 'selected');
    });
    
    // 페르소나 타입 버튼 업데이트
    document.querySelectorAll('.persona-type-btn').forEach(btn => {
        btn.classList.toggle('selected', btn.dataset.personaKey === personaKey);
    });
    
    // 페르소나 버튼 업데이트
    updatePersonaDisplayWithType(basePersona);
    updateSourceBadgeDisplay();
    
    // 긍정 페르소나 전환 배너 표시
    showPositiveGuidance(basePersona);
    
    // 피드백 표시
    showFeedback(`✓ 직접 선택: ${basePersona.name} → ${basePersona.positive}로 전환!`);
    
    // 드롭다운 닫기
    togglePersonaPicker();
    
    // 서버에 페르소나 변경 기록
    savePersonaInteraction('persona_type_select', {
        persona_type: personaKey,
        persona_name: basePersona.name,
        positive_persona: basePersona.positive,
        source: 'student'
    });
}

function updateSourceBadgeDisplay() {
    const headerBadge = document.getElementById('selectionSourceBadge');
    const pickerBadge = document.getElementById('pickerSourceBadge');
    
    if (state.personaSource === 'student') {
        if (headerBadge) {
            headerBadge.className = 'student-override-badge';
            headerBadge.innerHTML = '<span class="ai-icon">✓</span> 직접';
        }
        if (pickerBadge) {
            pickerBadge.className = 'student-override-badge';
            pickerBadge.innerHTML = '<span class="ai-icon">✓</span> 학생 직접 선택 (우선 적용)';
        }
    } else {
        if (headerBadge) {
            headerBadge.className = 'ai-selected-badge';
            headerBadge.innerHTML = '<span class="ai-icon">🤖</span> AI';
        }
        if (pickerBadge) {
            pickerBadge.className = 'ai-selected-badge';
            pickerBadge.innerHTML = '<span class="ai-icon">🤖</span> AI 자동 선택';
        }
    }
}

function updatePersonaDisplay() {
    // ITEM_PERSONAS가 정의되어 있는지 확인
    if (!window.ITEM_PERSONAS || !Array.isArray(window.ITEM_PERSONAS) || window.ITEM_PERSONAS.length === 0) {
        console.log('[learning_interface.js:updatePersonaDisplay] ITEM_PERSONAS 없음, 스킵');
        return;
    }
    
    const itemPersona = window.ITEM_PERSONAS.find(ip => ip.item_id === state.currentItemId);
    if (!itemPersona || !itemPersona.base_persona) return;
    
    const iconEl = document.getElementById('currentPersonaIcon');
    const labelEl = document.getElementById('currentPersonaLabel');
    const badgeEl = document.getElementById('currentItemBadge');
    
    if (state.isPositiveTransformed) {
        // 긍정 페르소나로 표시
        if (iconEl) iconEl.textContent = itemPersona.base_persona.positive_icon || '💪';
        if (labelEl) labelEl.textContent = itemPersona.base_persona.positive || '';
    } else {
        // 기본 페르소나로 표시
        if (iconEl) iconEl.textContent = itemPersona.base_persona.icon || '📝';
        if (labelEl) labelEl.textContent = itemPersona.base_persona.name || '';
    }
    if (badgeEl) badgeEl.textContent = `문항${state.currentItemId}`;
}

function updatePersonaDisplayWithType(basePersona) {
    const iconEl = document.getElementById('currentPersonaIcon');
    const labelEl = document.getElementById('currentPersonaLabel');
    
    if (iconEl) iconEl.textContent = basePersona.positive_icon;
    if (labelEl) labelEl.textContent = basePersona.positive;
}

function showPositiveGuidance(basePersona) {
    const banner = document.getElementById('positiveGuidanceBanner');
    const icon = document.getElementById('positiveGuidanceIcon');
    const text = document.getElementById('positiveGuidanceText');
    
    if (banner && icon && text) {
        icon.textContent = basePersona.positive_icon;
        text.textContent = basePersona.guidance;
        banner.classList.remove('hidden');
        
        // 10초 후 자동 숨김
        setTimeout(() => {
            hidePositiveGuidance();
        }, 10000);
    }
}

function hidePositiveGuidance() {
    const banner = document.getElementById('positiveGuidanceBanner');
    if (banner) {
        banner.classList.add('hidden');
    }
}

function savePersonaInteraction(type, data) {
    const payload = {
        type: type,
        data: {
            ...data,
            current_step: state.steps.find(s => s.status === 'current')?.id,
            current_emotion: state.emotion.type,
            persona_history: state.personaHistory
        },
        student_id: window.STUDENT_ID,
        analysis_id: window.ANALYSIS_ID,
        content_id: window.CONTENT_ID,
        timestamp: new Date().toISOString()
    };
    
    fetch('../api/interact.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    }).catch(err => console.error('Persona interaction save error:', err));
}

// 페르소나 정보를 프롬프트용으로 가져오기
function getCurrentPersonaPrompt() {
    if (!window.ITEM_PERSONAS || !Array.isArray(window.ITEM_PERSONAS)) return '';
    
    const itemPersona = window.ITEM_PERSONAS.find(ip => ip.item_id === state.currentItemId);
    if (!itemPersona) return '';
    
    const basePersona = itemPersona.base_persona;
    
    return `
[현재 학습자 페르소나]
- 문항: (${itemPersona.item_id}) ${itemPersona.item_text}
- 주제: ${itemPersona.topic}
- 난이도: ${itemPersona.difficulty}
- 원래 페르소나: ${basePersona.name} (${basePersona.icon})
- 긍정 전환 페르소나: ${basePersona.positive} (${basePersona.positive_icon})
- 상태: ${state.isPositiveTransformed ? '긍정 전환됨' : '미전환'}
- 맞춤 지도 문구: ${basePersona.guidance}
- 문항 맥락: ${itemPersona.context}

[맞춤형 피드백 지침]
이 학습자는 ${basePersona.name} 성향을 가지고 있습니다.
${state.isPositiveTransformed ? `현재 ${basePersona.positive} 모드로 전환되어 긍정적인 학습 태도를 보이고 있습니다.` : `아직 긍정 전환이 되지 않았으므로, ${basePersona.guidance}를 통해 유도해주세요.`}
    `.trim();
}

// 힌트 요청 시 페르소나 정보 포함
function requestHintWithPersona() {
    const currentStep = state.steps.find(s => s.status === 'current');
    const personaPrompt = getCurrentPersonaPrompt();
    
    fetch('../api/interact.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            type: 'hint_request',
            student_id: window.STUDENT_ID,
            analysis_id: window.ANALYSIS_ID,
            current_step: currentStep?.id,
            emotion: state.emotion.type,
            persona_prompt: personaPrompt,
            current_item_id: state.currentItemId,
            is_positive_transformed: state.isPositiveTransformed
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success && data.hint) {
            showFeedback(data.hint);
        }
    })
    .catch(err => console.error('Hint request error:', err));
}

function initializeFromAnalysisData() {
    const data = window.ANALYSIS_DATA;
    if (!data) return;
    
    // 분석 데이터에서 단계 설정
    if (data.dialogue_analysis && data.dialogue_analysis.learning_sequence) {
        const sequence = data.dialogue_analysis.learning_sequence;
        if (sequence.length > 0) {
            state.steps = sequence.map((step, i) => ({
                id: i + 1,
                label: step,
                status: i === 0 ? 'current' : 'pending',
                desc: ''
            }));
        }
    }
    
    // 5단계 장기기억 활동 항상 추가 (없으면 추가)
    ensureMemoryStep();
    
    // 문제 표시
    if (data.dialogue_analysis && data.dialogue_analysis.problems) {
        const problems = data.dialogue_analysis.problems;
        if (problems.length > 0) {
            const problemText = document.getElementById('problemText');
            if (problemText) {
                problemText.textContent = problems[0].text || '문제를 분석 중입니다...';
            }
        }
    }
}

// 5단계 장기기억화 활동이 항상 포함되도록 보장
function ensureMemoryStep() {
    const memoryStepExists = state.steps.some(s => s.label === '장기기억화' || s.id === 5);
    
    if (!memoryStepExists) {
        // 기존 단계들의 id를 1-4로 재정렬
        state.steps = state.steps.slice(0, 4).map((step, i) => ({
            ...step,
            id: i + 1
        }));
        
        // 5번째 단계로 장기기억화 추가
        state.steps.push({
            id: 5,
            label: '장기기억화',
            status: 'pending',
            desc: '반복 연습',
            isMemoryStep: true
        });
    }
}

// ========== 단계 관리 ==========
function renderSteps() {
    const container = document.getElementById('stepsList');
    if (!container) {
        console.error('[learning_interface.js:renderSteps] stepsList 요소를 찾을 수 없음');
        return;
    }
    
    // 단계 데이터 검증
    if (!state.steps || state.steps.length === 0) {
        container.innerHTML = '<div class="steps-empty">단계 정보를 불러오는 중...</div>';
        console.warn('[learning_interface.js:renderSteps] 단계 데이터 없음');
        return;
    }
    
    try {
        container.innerHTML = state.steps.map(step => {
            const isMemoryStep = step.id === 5 || step.isMemoryStep || step.label === '장기기억화';
            const memoryClass = isMemoryStep ? 'memory-step' : '';
            const icon = step.status === 'completed' ? '✓' : (isMemoryStep ? '🧠' : step.id);
            const isCurrent = step.status === 'current';
            const stepLabel = step.label || `단계 ${step.id}`;
            
            let html = `
                <button 
                    class="step-item ${step.status} ${memoryClass}"
                    onclick="handleStepClick(${step.id})"
                    title="${stepLabel}"
                >
                    <div class="step-content">
                        <div class="step-number ${isMemoryStep ? 'memory-number' : ''}">
                            ${icon}
                        </div>
                        <span class="step-label">${stepLabel}</span>
                        ${isCurrent ? '<span class="status-dot"></span>' : ''}
                    </div>
                </button>
            `;
            
            // 5단계 버튼 바로 아래에 장기기억 카운터 삽입
            if (isMemoryStep && isCurrent && state.memoryActivity.isActive) {
                html += renderMemoryActivityInline();
            }
            
            return html;
        }).join('');
        
        updateProgress();
        updateCurrentStepIndicator();
        updateSourceBadge();
        
        // 5단계(장기기억)가 현재 단계면 활동 UI 표시
        const currentStep = state.steps.find(s => s.status === 'current');
        if (currentStep && (currentStep.id === 5 || currentStep.isMemoryStep)) {
            if (!state.memoryActivity.isActive) {
                activateMemoryActivity();
            }
        }
    } catch (error) {
        console.error('[learning_interface.js:renderSteps] 렌더링 오류:', error);
        container.innerHTML = '<div class="steps-empty">단계 표시 오류</div>';
    }
}

// 장기기억 활동 인라인 HTML 생성
function renderMemoryActivityInline() {
    const activity = state.memoryActivity;
    const progress = (activity.counter / activity.targetCount) * 100;
    
    // V 제스처 카운트 (1, 2, 3)
    const countDots = [];
    for (let i = 1; i <= activity.targetCount; i++) {
        const isCompleted = i <= activity.counter;
        countDots.push(`<span class="count-dot ${isCompleted ? 'completed' : ''}">${isCompleted ? '✓' : i}</span>`);
    }
    
    const content = `
        <div class="memory-inline-row">
            <span class="memory-inline-label">✓ V제스처로 반복</span>
            <div class="memory-count-dots">
                ${countDots.join('')}
            </div>
        </div>
    `;
    
    // 진행 상황에 따른 추천 활동 문구
    const recommendationTip = getMemoryRecommendationTip(activity.counter, activity.targetCount, activity.completed);
    
    const completionHtml = activity.completed ? '<div class="memory-inline-complete">🎉 완료!</div>' : '';
    
    return `
        <div class="memory-activity-inline-dynamic">
            ${content}
            <div class="memory-inline-progress">
                <div class="memory-inline-fill" style="width: ${Math.min(progress, 100)}%"></div>
            </div>
            <div class="memory-recommendation-tip">${recommendationTip}</div>
            ${completionHtml}
        </div>
    `;
}

// 장기기억 추천 활동 문구 반환 (진행 상황에 따라 변경)
function getMemoryRecommendationTip(counter, target, completed) {
    if (completed) {
        const completeTips = [
            '🏆 장기기억에 저장 완료!',
            '⭐ 오래 기억될 거예요!',
            '🧠 뇌에 새겨졌어요!'
        ];
        return completeTips[Math.floor(Math.random() * completeTips.length)];
    }
    
    // 진행 단계별 추천 활동 문구
    const tips = {
        0: [
            '✏️ 풀이 과정을 소리 내어 말해보세요',
            '📝 핵심 공식을 손으로 써보세요',
            '🔍 왜 이 방법을 썼는지 설명해보세요',
            '💭 문제의 핵심 조건을 떠올려보세요'
        ],
        1: [
            '💪 좋아요! 다른 방법으로 풀어보세요',
            '🎯 핵심 포인트를 2가지 말해보세요',
            '👀 실수하기 쉬운 부분을 확인하세요',
            '🔄 처음부터 빠르게 다시 풀어보세요'
        ],
        2: [
            '🔥 마지막! 친구에게 설명해보세요',
            '🏃 빠르게 한 번 더 훑어보세요',
            '✅ 답을 가리고 다시 풀어보세요',
            '📊 비슷한 문제 패턴을 떠올려보세요'
        ]
    };
    
    const currentTips = tips[Math.min(counter, 2)] || tips[0];
    // 현재 카운터에 따라 고정된 문구 (매번 바뀌지 않도록)
    return currentTips[counter % currentTips.length];
}

function handleStepClick(stepId) {
    state.stepSource = 'manual';
    
    state.steps = state.steps.map(step => ({
        ...step,
        status: step.id < stepId ? 'completed' 
              : step.id === stepId ? 'current' 
              : 'pending'
    }));
    
    renderSteps();
    
    const step = state.steps.find(s => s.id === stepId);
    showFeedback(`'${step.label}' 단계로 이동 (직접 선택)`);
    
    // 5단계 (장기기억) 도달 시 활동 활성화
    if (stepId === 5) {
        activateMemoryActivity();
    } else {
        deactivateMemoryActivity();
    }
    
    // 30초 후 자동 모드로 복귀
    setTimeout(() => {
        if (state.stepSource === 'manual') {
            state.stepSource = 'auto';
            updateSourceBadge();
        }
    }, 30000);
    
    // 서버에 상호작용 기록
    saveInteraction('step_change', { step_id: stepId, source: 'manual' });
}

function updateProgress() {
    const completed = state.steps.filter(s => s.status === 'completed').length;
    const percent = Math.round((completed / state.steps.length) * 100);
    
    const percentEl = document.getElementById('progressPercent');
    const fillEl = document.getElementById('progressFill');
    
    if (percentEl) percentEl.textContent = `${percent}%`;
    if (fillEl) fillEl.style.width = `${percent}%`;
}

function updateCurrentStepIndicator() {
    const current = state.steps.find(s => s.status === 'current');
    const indicator = document.getElementById('currentStepText');
    
    if (indicator && current) {
        indicator.textContent = `${current.id}. ${current.label}`;
    }
}

function updateSourceBadge() {
    const badge = document.getElementById('stepSourceBadge');
    if (badge) {
        badge.textContent = state.stepSource === 'manual' ? '직접' : 'AI';
        badge.className = `source-badge ${state.stepSource === 'manual' ? 'manual' : ''}`;
    }
}

// ========== 감정 관리 ==========
function toggleEmotionPicker() {
    const picker = document.getElementById('emotionPicker');
    const btn = document.getElementById('emotionBtn');
    
    picker.classList.toggle('hidden');
    btn.classList.toggle('open');
}

function selectEmotion(type) {
    state.emotion = { type, source: 'manual' };
    
    updateEmotionDisplay();
    toggleEmotionPicker();
    
    const responses = {
        confident: '자신감 있네! 그 느낌 유지해봐 💪',
        confused: '어디가 헷갈려? 같이 봐줄게',
        stuck: '막혔구나. 힌트 줄까?',
        anxious: '괜찮아, 천천히 해도 돼',
        neutral: ''
    };
    
    if (responses[type]) {
        showFeedback(responses[type]);
    }
    
    // 30초 후 자동으로 복귀
    setTimeout(() => {
        if (state.emotion.source === 'manual') {
            state.emotion = { type: state.autoDetectedEmotion, source: 'auto' };
            updateEmotionDisplay();
        }
    }, 30000);
    
    // 서버에 감정 상태 기록
    saveInteraction('emotion_change', { emotion: type, source: 'manual' });
}

function updateEmotionDisplay() {
    const em = emotions[state.emotion.type] || emotions.neutral;
    
    const iconEl = document.getElementById('currentEmotionIcon');
    const labelEl = document.getElementById('currentEmotionLabel');
    const sourceEl = document.getElementById('emotionSource');
    const btn = document.getElementById('emotionBtn');
    
    if (iconEl) iconEl.textContent = em.icon;
    if (labelEl) labelEl.textContent = em.label;
    if (sourceEl) sourceEl.style.display = state.emotion.source === 'auto' ? '' : 'none';
    if (btn) btn.className = `emotion-btn ${state.emotion.source === 'manual' ? 'manual' : ''}`;
    
    // 옵션 선택 상태 업데이트
    document.querySelectorAll('.emotion-option').forEach(opt => {
        const type = opt.dataset.type;
        opt.classList.toggle('selected', 
            type === state.emotion.type && state.emotion.source === 'manual');
    });
}

// ========== 펜 제스처 ==========
function initGestureCanvas() {
    const canvas = document.getElementById('gestureCanvas');
    if (!canvas) {
        console.warn('[learning_interface.js:initGestureCanvas] 캔버스 요소를 찾을 수 없음');
        return;
    }
    
    console.log('[learning_interface.js:initGestureCanvas] 캔버스 초기화:', canvas.width, 'x', canvas.height);
    
    // 포인터 이벤트 지원 확인
    if (window.PointerEvent) {
        // 포인터 이벤트 사용 (마우스, 터치, 펜 모두 지원)
        canvas.addEventListener('pointerdown', handlePointerDown);
        canvas.addEventListener('pointermove', handlePointerMove);
        canvas.addEventListener('pointerup', handlePointerUp);
        canvas.addEventListener('pointerleave', handlePointerUp);
        canvas.addEventListener('pointercancel', handlePointerUp);
    } else {
        // 폴백: 마우스 + 터치 이벤트
        canvas.addEventListener('mousedown', startDrawing);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', stopDrawing);
        canvas.addEventListener('mouseleave', stopDrawing);
        
        canvas.addEventListener('touchstart', startDrawing, { passive: false });
        canvas.addEventListener('touchmove', draw, { passive: false });
        canvas.addEventListener('touchend', stopDrawing);
    }
    
    renderGestureCanvas();
}

function handlePointerDown(e) {
    e.preventDefault();
    const canvas = e.target;
    canvas.setPointerCapture(e.pointerId);
    startDrawing(e);
}

function handlePointerMove(e) {
    draw(e);
}

function handlePointerUp(e) {
    const canvas = e.target;
    if (canvas.hasPointerCapture && canvas.hasPointerCapture(e.pointerId)) {
        canvas.releasePointerCapture(e.pointerId);
    }
    stopDrawing(e);
}

function getCanvasPoint(e) {
    const canvas = document.getElementById('gestureCanvas');
    if (!canvas) return { x: 0, y: 0 };
    
    const rect = canvas.getBoundingClientRect();
    
    // 터치, 포인터, 마우스 이벤트 모두 지원
    let clientX, clientY;
    if (e.touches && e.touches.length > 0) {
        clientX = e.touches[0].clientX;
        clientY = e.touches[0].clientY;
    } else {
        clientX = e.clientX;
        clientY = e.clientY;
    }
    
    // CSS 크기와 캔버스 속성 크기 비율 계산
    const scaleX = canvas.width / rect.width;
    const scaleY = canvas.height / rect.height;
    
    return { 
        x: (clientX - rect.left) * scaleX, 
        y: (clientY - rect.top) * scaleY 
    };
}

function startDrawing(e) {
    if (e && e.preventDefault) e.preventDefault();
    
    state.isDrawing = true;
    state.recognizedGesture = null;
    const point = getCanvasPoint(e);
    state.currentPath = [point];
    
    console.log('[learning_interface.js:startDrawing] 그리기 시작:', point.x.toFixed(1), point.y.toFixed(1));
    hideGestureLabel();
    renderGestureCanvas();
}

function draw(e) {
    if (!state.isDrawing) return;
    if (e && e.preventDefault) e.preventDefault();
    
    const point = getCanvasPoint(e);
    state.currentPath.push(point);
    renderGestureCanvas();
}

function stopDrawing(e) {
    if (!state.isDrawing) return;
    if (e && e.preventDefault) e.preventDefault();
    
    state.isDrawing = false;
    console.log('[learning_interface.js:stopDrawing] 그리기 종료, 포인트 수:', state.currentPath.length);
    
    if (state.currentPath.length > 5) {
        const gesture = detectGesture(state.currentPath);
        console.log('[learning_interface.js:stopDrawing] 감지된 제스처:', gesture);
        
        if (gesture) {
            state.recognizedGesture = gesture;
            renderGestureCanvas();
            
            const info = gestures[gesture.type];
            if (info) {
                showGestureLabel(info.meaning);
                showFeedback(info.feedback);
            }
            
            // 제스처에 따른 액션
            handleGestureAction(gesture.type);
        }
    }
    
    setTimeout(() => {
        state.currentPath = [];
        state.recognizedGesture = null;
        hideGestureLabel();
        renderGestureCanvas();
    }, 1500);
}

function detectGesture(path) {
    if (path.length < 5) return null;
    
    const bounds = {
        minX: Math.min(...path.map(p => p.x)),
        maxX: Math.max(...path.map(p => p.x)),
        minY: Math.min(...path.map(p => p.y)),
        maxY: Math.max(...path.map(p => p.y))
    };
    
    const width = bounds.maxX - bounds.minX;
    const height = bounds.maxY - bounds.minY;
    const first = path[0];
    const last = path[path.length - 1];
    const closedDistance = Math.sqrt(Math.pow(last.x - first.x, 2) + Math.pow(last.y - first.y, 2));
    
    // 체크마크
    const midIndex = Math.floor(path.length / 2);
    const midPoint = path[midIndex];
    if (height > 15 && width > 10) {
        const goesDown = midPoint.y > first.y && midPoint.y > last.y;
        if (goesDown) return { type: 'check', symbol: '✓' };
    }
    
    // 동그라미
    if (closedDistance < 30 && width > 20 && height > 20 && Math.abs(width - height) < 25) {
        return { type: 'circle', symbol: '○' };
    }
    
    // 물음표
    if (height > 25 && width < 35 && height > width * 1.2) {
        return { type: 'question', symbol: '?' };
    }
    
    // 화살표
    if (width > 40 && height < 30 && width > height * 1.5) {
        return { type: 'arrow', symbol: '→' };
    }
    
    // X
    if (width > 15 && height > 15 && Math.abs(width - height) < 20) {
        return { type: 'x', symbol: '✗' };
    }
    
    return null;
}

function handleGestureAction(gestureType) {
    // 서버에 제스처 기록
    saveInteraction('gesture', { gesture_type: gestureType });
    
    const currentStep = state.steps.find(s => s.status === 'current');
    const isMemoryStep = currentStep && (currentStep.id === 5 || currentStep.isMemoryStep);
    
    if (gestureType === 'check') {
        // 장기기억 단계에서는 V 제스처로 카운트 증가
        if (isMemoryStep && state.memoryActivity.isActive) {
            handleMemoryGestureCheck();
        } else {
            // 🔥 TTS 섹션이 있으면 TTS와 풀이단계 연동 진행
            if (state.tts.sections && state.tts.sections.length > 0) {
                advanceTtsAndStep();
            } else {
                // TTS 없으면 일반 단계 진행
                if (currentStep && currentStep.id < state.steps.length) {
                    setTimeout(() => {
                        handleStepClick(currentStep.id + 1);
                    }, 500);
                }
            }
        }
    } else if (gestureType === 'arrow') {
        // 다음 단계로 진행 (TTS 연동)
        if (state.tts.sections && state.tts.sections.length > 0) {
            advanceTtsAndStep();
        } else if (currentStep && currentStep.id < state.steps.length) {
            setTimeout(() => {
                handleStepClick(currentStep.id + 1);
            }, 500);
        }
    } else if (gestureType === 'question') {
        // 힌트 요청 (페르소나 정보 포함)
        requestHintWithPersona();
    } else if (gestureType === 'x') {
        // X 제스처: 현재 단계의 faqtext 점층 표시
        showFaqProgressive();
    }
}

/**
 * TTS 섹션과 풀이 단계 연동 진행
 * - 1, 2번째 섹션 → 문제해석
 * - 3번째 섹션 → 식세우기
 * - 4번째 이후 ~ 마지막-2 → 풀이과정
 * - 마지막 2개 섹션 → 점검
 * - 다 끝나면 → 장기기억화
 */
function advanceTtsAndStep() {
    const sections = state.tts.sections || [];
    const totalSections = sections.length;
    const currentSection = state.tts.currentSectionIndex || 0;
    const nextSection = currentSection + 1;
    
    console.log('[learning_interface.js:advanceTtsAndStep] 현재 섹션:', currentSection, '/ 총:', totalSections);
    
    if (nextSection < totalSections) {
        // 다음 TTS 섹션 재생
        playTtsSection(nextSection);
        
        // 해당 섹션에 맞는 풀이 단계로 이동
        const stepId = mapSectionToStep(nextSection, totalSections);
        updateStepForSection(stepId);
        
        showFeedback(`✓ ${nextSection + 1}/${totalSections} 단계 진행 중...`);
    } else {
        // 모든 TTS 섹션 완료 → 장기기억화로 이동
        stopCurrentTts();
        handleStepClick(5); // 장기기억화 단계
        showFeedback('🎉 모든 풀이 설명 완료! 장기기억화 단계로 이동합니다.');
    }
}

/**
 * TTS 섹션 번호를 풀이 단계 ID로 매핑
 * @param {number} sectionIndex - 현재 섹션 인덱스 (0-based)
 * @param {number} totalSections - 총 섹션 수
 * @returns {number} 풀이 단계 ID (1-5)
 */
function mapSectionToStep(sectionIndex, totalSections) {
    // 섹션 수에 따른 동적 매핑
    // 1, 2번째 섹션 (0, 1) → 문제해석 (1)
    // 3번째 섹션 (2) → 식세우기 (2)
    // 4번째 ~ 마지막-2 → 풀이과정 (3)
    // 마지막 2개 → 점검 (4)
    
    if (sectionIndex < 2) {
        return 1; // 문제해석
    } else if (sectionIndex === 2) {
        return 2; // 식세우기
    } else if (sectionIndex < totalSections - 2) {
        return 3; // 풀이과정
    } else {
        return 4; // 점검
    }
}

/**
 * 섹션에 맞게 풀이 단계 업데이트
 */
function updateStepForSection(stepId) {
    const currentStep = state.steps.find(s => s.status === 'current');
    
    // 현재 단계와 다를 때만 업데이트
    if (currentStep && currentStep.id !== stepId) {
        state.steps = state.steps.map(step => ({
            ...step,
            status: step.id < stepId ? 'completed' 
                  : step.id === stepId ? 'current' 
                  : 'pending'
        }));
        
        renderSteps();
        
        // 추천 페르소나 갱신
        setTimeout(() => onStepChange(), 100);
        
        const step = state.steps.find(s => s.id === stepId);
        console.log('[learning_interface.js:updateStepForSection] 단계 변경:', step?.label);
    }
}

// ========== FAQ 점층상호작용 표시 ==========

/**
 * X 제스처 시 현재 단계의 faqtext를 점층적으로 표시
 * - 세션 내 제스처 횟수를 카운트하여 순차 진행
 * - 0~5번째: faqtext 순차 표시
 * - 6번째(7번째 제스처) 이후: 격려 메시지 랜덤 표시
 */
async function showFaqProgressive() {
    console.log('[learning_interface.js:showFaqProgressive] FAQ 점층 표시 - 세션 카운트:', state.faq.sessionGestureCount);
    
    // faqtext 데이터 로드 (없으면 서버에서 가져오기)
    if (!state.faq.data) {
        const loaded = await loadFaqData();
        if (!loaded) {
            showFeedback('❌ 점층상호작용 데이터가 없습니다. TTS를 먼저 생성해주세요.');
            return;
        }
    }
    
    // 현재 TTS 섹션에 해당하는 단계 찾기
    const currentSectionIndex = state.tts.currentSectionIndex || 0;
    let faqStepData = state.faq.data.find(d => d.step_index === currentSectionIndex + 1);
    
    if (!faqStepData || !faqStepData.faqtext || faqStepData.faqtext.length === 0) {
        // 해당 단계에 faqtext가 없으면 첫 번째 단계 사용
        faqStepData = state.faq.data[0];
        if (!faqStepData || !faqStepData.faqtext) {
            showFeedback('❌ 이 단계의 점층상호작용 데이터가 없습니다.');
            return;
        }
        state.faq.currentStepIndex = 0;
    } else {
        state.faq.currentStepIndex = currentSectionIndex;
    }
    
    // 찾은 faqStepData를 state에 저장 (displayCurrentFaq에서 사용)
    state.faq.currentStepData = faqStepData;
    
    const totalFaqs = faqStepData.faqtext.length; // 보통 6개
    
    // 현재 세션 카운트 기준으로 표시할 내용 결정
    if (state.faq.sessionGestureCount < totalFaqs) {
        // 0~5번째: faqtext 순차 표시
        state.faq.currentFaqIndex = state.faq.sessionGestureCount;
        state.faq.isDisplaying = true;
        
        showFaqBubble();
        displayCurrentFaq();
        
        // 카운트 증가
        state.faq.sessionGestureCount++;
        
        console.log(`[showFaqProgressive] FAQ ${state.faq.currentFaqIndex + 1}/${totalFaqs} 표시`);
        
        // 마지막(6번째) 표시 시 완료 플래그 설정
        if (state.faq.sessionGestureCount >= totalFaqs) {
            state.faq.completedOnce = true;
        }
    } else {
        // 6번째 이후 (7번째 제스처부터): 격려 메시지 랜덤 표시
        showEncouragementMessage();
        
        // 카운트 계속 증가 (통계용)
        state.faq.sessionGestureCount++;
    }
}

/**
 * 서버에서 faqtext 데이터 로드
 */
async function loadFaqData() {
    const interactionId = state.tts.interactionId;
    if (!interactionId) {
        console.log('[learning_interface.js:loadFaqData] interactionId 없음');
        return false;
    }
    
    try {
        const response = await fetch(`/moodle/local/augmented_teacher/alt42/teachingsupport/get_interaction_data.php?id=${interactionId}&format=full`);
        const result = await response.json();
        
        if (result.success && result.faqtext) {
            // faqtext가 문자열이면 JSON 파싱
            let faqData = result.faqtext;
            if (typeof faqData === 'string') {
                faqData = JSON.parse(faqData);
            }
            state.faq.data = faqData;
            console.log('[learning_interface.js:loadFaqData] FAQ 데이터 로드 완료:', faqData.length, '개 단계');
            return true;
        }
        
        console.log('[learning_interface.js:loadFaqData] FAQ 데이터 없음');
        return false;
    } catch (error) {
        console.error('[learning_interface.js:loadFaqData] 로드 오류:', error);
        return false;
    }
}

/**
 * FAQ 말풍선 표시 (내부 함수) - 비활성화됨, 피드백 배너만 사용
 */
function showFaqBubble() {
    // 말풍선 비활성화 - 피드백 배너에서만 표시
    // const bubble = document.getElementById('faqBubble');
    // if (bubble) {
    //     bubble.classList.remove('hidden');
    // }
}

/**
 * FAQ 말풍선 숨기기
 */
function hideFaqBubble() {
    const bubble = document.getElementById('faqBubble');
    if (bubble) {
        bubble.style.animation = 'bubbleFadeIn 0.2s ease reverse';
        setTimeout(() => {
            bubble.classList.add('hidden');
            state.faq.isDisplaying = false;
        }, 200);
    }
}

/**
 * 격려 메시지 표시 (7번째 제스처 이후)
 */
function showEncouragementMessage() {
    // 랜덤 메시지 선택
    const randomIndex = Math.floor(Math.random() * faqEncouragementMessages.length);
    const message = faqEncouragementMessages[randomIndex];
    
    // 🔥 피드백 배너에만 격려 메시지 표시 (아이콘 없이, 큰 글씨)
    showFaqFeedback(message, 5, false);
    
    console.log(`[showEncouragementMessage] 격려 메시지: "${message}", 반복 ${state.faq.sessionGestureCount}회`);
}

/**
 * 현재 FAQ 항목을 말풍선으로 표시
 */
function displayCurrentFaq() {
    // state.faq.currentStepData를 직접 사용 (showFaqProgressive에서 저장됨)
    const currentStepData = state.faq.currentStepData;
    
    if (!currentStepData || !currentStepData.faqtext) {
        console.error('[displayCurrentFaq] faqtext 데이터 없음:', currentStepData);
        showFeedback('❌ FAQ 데이터를 불러올 수 없습니다.');
        return;
    }
    
    const faqIndex = state.faq.currentFaqIndex;
    const faqText = currentStepData.faqtext[faqIndex];
    const totalFaqs = currentStepData.faqtext.length;
    
    if (!faqText) {
        console.error('[displayCurrentFaq] faqText 없음 - index:', faqIndex, 'total:', totalFaqs);
        return;
    }
    
    console.log(`[displayCurrentFaq] 단계: ${currentStepData.step_label}, 문구 ${faqIndex + 1}/${totalFaqs}: "${faqText}"`);
    
    // 마지막(6번째)은 파란색 강조
    const isLast = faqIndex === totalFaqs - 1;
    
    // 🔥 피드백 배너에만 faqtext 표시 (아이콘 없이, 크기 점층적)
    showFaqFeedback(faqText, faqIndex + 1, isLast);
    
    // 기존 타이머 제거
    if (state.faq.displayTimer) {
        clearTimeout(state.faq.displayTimer);
    }
    
    // 마지막 완료 시 피드백 (5초 후)
    if (isLast) {
        state.faq.displayTimer = setTimeout(() => {
            showFeedback('✅ 점층 강조 완료! 다음 X 제스처로 격려 메시지를 볼 수 있어요');
        }, 5000);
    }
    
    console.log(`[learning_interface.js:displayCurrentFaq] FAQ ${faqIndex + 1}/${totalFaqs} 표시`);
}

/**
 * FAQ 말풍선 표시 (호환성 유지)
 */
function showFaqOverlay() {
    showFaqBubble();
}

/**
 * FAQ 말풍선 닫기 (호환성 유지)
 */
function closeFaqOverlay() {
    hideFaqBubble();
    
    // 세션 카운트는 유지 (리셋하지 않음)
    state.faq.currentFaqIndex = 0;
    
    if (state.faq.displayTimer) {
        clearTimeout(state.faq.displayTimer);
        state.faq.displayTimer = null;
    }
}

// 장기기억 단계에서 V 제스처 처리
function handleMemoryGestureCheck() {
    const activity = state.memoryActivity;
    
    if (activity.type === 'counter') {
        incrementCounter();
        showGestureSuccessFeedback();
    } else if (activity.type === 'repeat') {
        incrementRepeat();
        showGestureSuccessFeedback();
    } else if (activity.type === 'timer') {
        // 타이머는 V로 완료 신호
        showFeedback('⏱️ 타이머 진행 중... 끝까지 집중!');
    }
}

// V 제스처 성공 피드백
function showGestureSuccessFeedback() {
    const canvas = document.getElementById('gestureCanvas');
    if (canvas) {
        canvas.classList.add('gesture-success');
        setTimeout(() => {
            canvas.classList.remove('gesture-success');
        }, 300);
    }
}

function renderGestureCanvas() {
    const canvas = document.getElementById('gestureCanvas');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    const width = canvas.width;
    const height = canvas.height;
    
    // 배경
    ctx.clearRect(0, 0, width, height);
    const gradient = ctx.createLinearGradient(0, 0, width, height);
    gradient.addColorStop(0, '#f8fafc');
    gradient.addColorStop(1, '#e2e8f0');
    ctx.fillStyle = gradient;
    ctx.fillRect(0, 0, width, height);
    
    // 격자 (더 세밀하게)
    ctx.strokeStyle = '#cbd5e1';
    ctx.lineWidth = 0.5;
    const gridSize = 20;
    for (let i = 0; i <= width; i += gridSize) {
        ctx.beginPath();
        ctx.moveTo(i, 0);
        ctx.lineTo(i, height);
        ctx.stroke();
    }
    for (let i = 0; i <= height; i += gridSize) {
        ctx.beginPath();
        ctx.moveTo(0, i);
        ctx.lineTo(width, i);
        ctx.stroke();
    }
    
    // 현재 경로
    if (state.currentPath.length > 1) {
        ctx.beginPath();
        ctx.strokeStyle = state.recognizedGesture ? '#6366f1' : '#475569';
        ctx.lineWidth = 4;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.moveTo(state.currentPath[0].x, state.currentPath[0].y);
        state.currentPath.forEach(p => ctx.lineTo(p.x, p.y));
        ctx.stroke();
    }
    
    // 인식 결과
    if (state.recognizedGesture) {
        // 배경 원
        ctx.beginPath();
        ctx.arc(width / 2, height / 2, 35, 0, Math.PI * 2);
        ctx.fillStyle = 'rgba(99, 102, 241, 0.2)';
        ctx.fill();
        
        // 심볼
        ctx.fillStyle = '#6366f1';
        ctx.font = 'bold 40px sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(state.recognizedGesture.symbol, width / 2, height / 2);
    }
    
    // 안내 (그리지 않은 상태)
    if (state.currentPath.length === 0 && !state.recognizedGesture) {
        ctx.fillStyle = '#64748b';
        ctx.font = 'bold 14px sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText('여기에 그리세요', width / 2, height / 2 - 12);
        
        ctx.fillStyle = '#94a3b8';
        ctx.font = '16px sans-serif';
        ctx.fillText('✓  ✗  ?  ○', width / 2, height / 2 + 14);
    }
}

function showGestureLabel(text) {
    // 제스처 라벨 표시 비활성화 - 시선 분산 방지
    // const label = document.getElementById('gestureLabel');
    // if (label) {
    //     label.textContent = text;
    //     label.classList.remove('hidden');
    // }
}

function hideGestureLabel() {
    const label = document.getElementById('gestureLabel');
    if (label) {
        label.classList.add('hidden');
    }
}

// ========== AI 피드백 ==========
function showFeedback(message) {
    const feedback = document.getElementById('aiFeedback');
    const text = document.getElementById('feedbackText');
    const emotionEl = document.getElementById('feedbackEmotion');
    
    if (feedback && text) {
        // 현재 감정 이모티콘을 글머리로 표시
        const emotions = {
            confident: '😊',
            neutral: '😐',
            confused: '🤔',
            stuck: '😵',
            anxious: '😰'
        };
        
        if (emotionEl) {
            emotionEl.textContent = emotions[state.emotion.type] || '😐';
            emotionEl.style.display = ''; // 기본 표시
        }
        
        text.textContent = message;
        text.style.fontSize = ''; // 기본 크기
        feedback.classList.remove('hidden');
        
        setTimeout(() => {
            feedback.classList.add('hidden');
        }, 3500);
    }
}

/**
 * FAQ 전용 피드백 표시 (아이콘 없이 텍스트만, 크기 점층적)
 * @param {string} message - 표시할 메시지
 * @param {number} level - 점층 레벨 (1-6), 클수록 글자가 커짐
 * @param {boolean} isLast - 마지막(확정) 여부 (파란색 강조)
 */
function showFaqFeedback(message, level = 1, isLast = false) {
    const feedback = document.getElementById('aiFeedback');
    const text = document.getElementById('feedbackText');
    const emotionEl = document.getElementById('feedbackEmotion');
    
    if (feedback && text) {
        // 아이콘 숨기기
        if (emotionEl) {
            emotionEl.style.display = 'none';
        }
        
        // 점층적 폰트 크기 (14px ~ 24px)
        const baseFontSize = 14;
        const maxFontSize = 24;
        const fontSize = baseFontSize + (maxFontSize - baseFontSize) * ((level - 1) / 5);
        
        text.textContent = message;
        text.style.fontSize = fontSize + 'px';
        text.style.fontWeight = level >= 5 ? 'bold' : (level >= 3 ? '600' : '500');
        text.style.color = isLast ? '#fbbf24' : ''; // 마지막은 노란색
        
        feedback.classList.remove('hidden');
        
        // 마지막은 5초, 나머지는 3초
        const hideDelay = isLast ? 5000 : 3000;
        setTimeout(() => {
            feedback.classList.add('hidden');
            // 스타일 리셋
            text.style.fontSize = '';
            text.style.fontWeight = '';
            text.style.color = '';
            if (emotionEl) emotionEl.style.display = '';
        }, hideDelay);
    }
}

// ========== 자동 업데이트 ==========
function startAutoUpdate() {
    // 풀이 단계 자동 진행 (시뮬레이션)
    setInterval(() => {
        if (state.stepSource !== 'auto') return;
        
        const currentIndex = state.steps.findIndex(s => s.status === 'current');
        if (currentIndex === -1 || currentIndex >= state.steps.length - 1) return;
        
        // 랜덤하게 다음 단계로 (실제로는 필기 패턴 분석)
        if (Math.random() > 0.7) {
            state.steps = state.steps.map((step, i) => ({
                ...step,
                status: i < currentIndex + 1 ? 'completed' 
                      : i === currentIndex + 1 ? 'current' 
                      : 'pending'
            }));
            renderSteps();
        }
    }, 5000);
    
    // 감정 자동 감지 (시뮬레이션)
    setInterval(() => {
        const current = state.steps.find(s => s.status === 'current');
        const emotionTypes = ['neutral', 'confused', 'confident', 'stuck'];
        const weights = current && current.id === 3 
            ? [0.2, 0.4, 0.2, 0.2] 
            : [0.5, 0.2, 0.2, 0.1];
        
        let rand = Math.random();
        let detected = 'neutral';
        let cumulative = 0;
        
        for (let i = 0; i < emotionTypes.length; i++) {
            cumulative += weights[i];
            if (rand < cumulative) {
                detected = emotionTypes[i];
                break;
            }
        }
        
        state.autoDetectedEmotion = detected;
        
        if (state.emotion.source === 'auto') {
            state.emotion = { type: detected, source: 'auto' };
            updateEmotionDisplay();
        }
    }, 4000);
}

// ========== 서버 통신 ==========
function saveInteraction(type, data) {
    // 페르소나 정보를 항상 포함하여 맞춤형 피드백에 활용
    const personaPrompt = getCurrentPersonaPrompt();
    
    const payload = {
        type: type,
        data: data,
        student_id: window.STUDENT_ID,
        analysis_id: window.ANALYSIS_ID,
        content_id: window.CONTENT_ID,
        timestamp: new Date().toISOString(),
        current_step: state.steps.find(s => s.status === 'current')?.id,
        current_emotion: state.emotion.type,
        // 페르소나 관련 정보 추가
        persona_context: {
            current_item_id: state.currentItemId,
            current_persona_type: state.currentPersonaType,
            is_positive_transformed: state.isPositiveTransformed,
            persona_prompt: personaPrompt
        }
    };
    
    fetch('../api/interact.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    }).catch(err => console.error('Interaction save error:', err));
}

// 기존 requestHint는 유지 (호환성)
function requestHint() {
    requestHintWithPersona();
}

// ========== 장기기억 활동 시스템 ==========

// 페르소나 기반 활동 유형 매핑
const personaActivityMap = {
    'avoider': { type: 'counter', target: 2, message: '작은 목표! 2번만 해보자 👣' },
    'checker': { type: 'timer', target: 30, message: '30초 동안 스스로 확인해봐 🔍' },
    'emotion_driven': { type: 'timer', target: 20, message: '20초 심호흡 후 다시 도전 🌊' },
    'speed_miss': { type: 'counter', target: 3, message: '검증 3회! 정확도가 실력 ✅' },
    'attention_hopper': { type: 'timer', target: 45, message: '45초 집중! 한 문제에만 🔬' },
    'pattern_seeker': { type: 'repeat', target: 2, message: '구조 2번 반복 확인 🗺️' },
    'efficiency_max': { type: 'counter', target: 2, message: '핵심 2번 복습으로 완성 💡' },
    'over_focus': { type: 'timer', target: 60, message: '1분 안에 마무리! 적정 몰입 ⚖️' },
    'concrete_learner': { type: 'repeat', target: 3, message: '예시 3번 반복 연습 🎨' },
    'interactive': { type: 'counter', target: 3, message: '혼자서 3번 해보기 🌟' },
    'low_drive': { type: 'counter', target: 1, message: '딱 1번만! 지금 이것만 🔥' },
    'meta_high': { type: 'repeat', target: 4, message: '고난도 4회 반복 도전 ⚔️' }
};

function activateMemoryActivity() {
    // 항상 V 제스처 카운트 방식으로 3번 반복연습
    state.memoryActivity = {
        isActive: true,
        type: 'counter',
        counter: 0,
        targetCount: 3,
        timerSeconds: 0,
        timerInterval: null,
        repeatCount: 0,
        targetRepeat: 3,
        completed: false
    };
    
    // faqtext의 현재 단계 확정형(6번째) 메시지가 있으면 사용, 없으면 기본 메시지
    let feedbackMsg = '🧠 V 제스처로 3번 반복연습하세요! ✓✓✓';
    if (state.faq.currentStepData && state.faq.currentStepData.faqtext && state.faq.currentStepData.faqtext.length >= 6) {
        // 확정형(6번째) 메시지 사용
        feedbackMsg = '🧠 ' + state.faq.currentStepData.faqtext[5];
    }
    showFeedback(feedbackMsg);
    showMemoryActivityUI();
    
    // 서버에 활동 시작 기록
    saveInteraction('memory_activity_start', {
        persona_type: state.currentPersonaType || 'default',
        activity_type: 'counter',
        target: 3
    });
}

function deactivateMemoryActivity() {
    if (state.memoryActivity.timerInterval) {
        clearInterval(state.memoryActivity.timerInterval);
    }
    state.memoryActivity.isActive = false;
    hideMemoryActivityUI();
}

function showMemoryActivityUI() {
    // 동적으로 renderSteps에서 표시되므로 다시 렌더링
    renderSteps();
}

function hideMemoryActivityUI() {
    // 동적으로 renderSteps에서 숨겨지므로 다시 렌더링
    renderSteps();
}

function updateMemoryActivityDisplay() {
    // 동적으로 renderSteps에서 표시되므로 다시 렌더링
    renderSteps();
    
    // 완료 체크
    checkMemoryActivityCompletion();
}

function incrementCounter() {
    if (!state.memoryActivity.isActive || state.memoryActivity.type !== 'counter') return;
    
    state.memoryActivity.counter++;
    updateMemoryActivityDisplay();
    
    if (state.memoryActivity.counter >= state.memoryActivity.targetCount) {
        completeMemoryActivity();
    }
}

function startMemoryTimer() {
    if (state.memoryActivity.timerInterval) {
        clearInterval(state.memoryActivity.timerInterval);
    }
    
    state.memoryActivity.timerInterval = setInterval(() => {
        if (state.memoryActivity.timerSeconds > 0) {
            state.memoryActivity.timerSeconds--;
            updateMemoryActivityDisplay();
        } else {
            clearInterval(state.memoryActivity.timerInterval);
            completeMemoryActivity();
        }
    }, 1000);
}

function resetMemoryTimer() {
    const personaType = state.currentPersonaType || 'checker';
    const activity = personaActivityMap[personaType];
    
    if (activity && activity.type === 'timer') {
        state.memoryActivity.timerSeconds = activity.target;
        updateMemoryActivityDisplay();
        startMemoryTimer();
    }
}

function incrementRepeat() {
    if (!state.memoryActivity.isActive || state.memoryActivity.type !== 'repeat') return;
    
    state.memoryActivity.repeatCount++;
    updateMemoryActivityDisplay();
    
    if (state.memoryActivity.repeatCount >= state.memoryActivity.targetRepeat) {
        completeMemoryActivity();
    }
}

function checkMemoryActivityCompletion() {
    const activity = state.memoryActivity;
    
    if (activity.type === 'counter' && activity.counter >= activity.targetCount) {
        return true;
    } else if (activity.type === 'timer' && activity.timerSeconds <= 0) {
        return true;
    } else if (activity.type === 'repeat' && activity.repeatCount >= activity.targetRepeat) {
        return true;
    }
    return false;
}

function completeMemoryActivity() {
    state.memoryActivity.completed = true;
    
    // 완료 애니메이션 실행
    triggerMemoryCompletionAnimation();
    
    // 서버에 활동 완료 기록
    saveInteraction('memory_activity_complete', {
        persona_type: state.currentPersonaType,
        activity_type: state.memoryActivity.type,
        counter: state.memoryActivity.counter,
        repeat_count: state.memoryActivity.repeatCount
    });
}

// 장기기억 완료 애니메이션
function triggerMemoryCompletionAnimation() {
    const container = document.getElementById('memoryActivityContainer');
    const completionEl = document.getElementById('memoryCompletion');
    
    // 완료 표시
    if (completionEl) {
        completionEl.classList.remove('hidden');
    }
    
    // 컨테이너에 완료 애니메이션 클래스 추가
    if (container) {
        container.classList.add('memory-complete-animation');
    }
    
    // 화면 전체 축하 효과
    showCelebrationOverlay();
    
    // 피드백 메시지
    showFeedback('🎉 장기기억화 완성!! 대단해요! 🧠✨');
    
    // 5단계 완료 표시
    state.steps = state.steps.map(step => 
        step.id === 5 ? { ...step, status: 'completed' } : step
    );
    renderSteps();
}

// 축하 오버레이 효과
function showCelebrationOverlay() {
    // 기존 오버레이 제거
    const existing = document.getElementById('celebrationOverlay');
    if (existing) existing.remove();
    
    // 새 오버레이 생성
    const overlay = document.createElement('div');
    overlay.id = 'celebrationOverlay';
    overlay.className = 'celebration-overlay';
    overlay.innerHTML = `
        <div class="celebration-content">
            <div class="celebration-emoji">🎉</div>
            <div class="celebration-text">장기기억화 완성!</div>
            <div class="celebration-subtext">훌륭해요! 이제 오래 기억될 거예요 🧠</div>
            <div class="confetti-container">
                ${Array(20).fill().map((_, i) => `<div class="confetti confetti-${i % 5}"></div>`).join('')}
            </div>
        </div>
    `;
    
    document.body.appendChild(overlay);
    
    // 3초 후 자동 제거
    setTimeout(() => {
        overlay.classList.add('fade-out');
        setTimeout(() => overlay.remove(), 500);
    }, 2500);
    
    // 클릭으로 닫기
    overlay.addEventListener('click', () => {
        overlay.classList.add('fade-out');
        setTimeout(() => overlay.remove(), 500);
    });
}

// ========== 클릭 외부 영역 닫기 ==========
document.addEventListener('click', function(e) {
    // 감정 피커 닫기
    const emotionPicker = document.getElementById('emotionPicker');
    const emotionBtn = document.getElementById('emotionBtn');
    
    if (emotionPicker && emotionBtn && !emotionPicker.contains(e.target) && !emotionBtn.contains(e.target)) {
        emotionPicker.classList.add('hidden');
        emotionBtn.classList.remove('open');
    }
    
    // 페르소나 피커 닫기 (오버레이 포함)
    const personaPicker = document.getElementById('personaPicker');
    const personaBtn = document.getElementById('personaBtn');
    const personaOverlay = document.getElementById('personaPickerOverlay');
    
    if (personaPicker && personaBtn && !personaPicker.contains(e.target) && !personaBtn.contains(e.target)) {
        personaPicker.classList.add('hidden');
        if (personaOverlay) personaOverlay.classList.add('hidden');
        personaBtn.classList.remove('open');
    }
});

// ========== 문항 분석 (OpenAI Vision) ==========
async function analyzeQuestionIfNeeded() {
    if (!window.NEEDS_ANALYSIS) return;
    
    try {
        const params = new URLSearchParams({
            wboard_id: window.WBOARD_ID || window.ANALYSIS_ID,
            student_id: window.STUDENT_ID,
            question_image: window.QUESTION_IMAGE || '',
            solution_image: window.SOLUTION_IMAGE || ''
        });
        
        showFeedback('🔍 AI가 문제를 분석하고 있어요...');
        
        const response = await fetch('/moodle/local/augmented_teacher/alt42/teachingsupport/AItutor/api/analyze_question.php?' + params);
        const result = await response.json();
        
        if (result.success) {
            window.ANALYSIS_DATA = result.data;
            window.ITEM_PERSONAS = result.data.persona || [];
            window.MASTERY_RECOMMENDATIONS = result.data.mastery_recommendations || [];
            
            // UI 업데이트
            updatePersonasFromAnalysis(result.data.persona);
            updateMasteryRecommendations(result.data.mastery_recommendations);
            
            showFeedback('✅ 분석 완료! 맞춤형 학습 준비됐어요');
        } else {
            console.error('분석 실패:', result.error);
            showFeedback('⚠️ 분석 중 문제 발생. 기본 모드로 진행해요');
        }
    } catch (error) {
        console.error('분석 API 호출 오류:', error);
    }
}

function updatePersonasFromAnalysis(personas) {
    if (!personas || personas.length === 0) return;
    
    window.ITEM_PERSONAS = personas;
    
    // 첫 번째 페르소나 적용
    const firstPersona = personas[0];
    if (firstPersona) {
        state.currentItemId = firstPersona.item_id;
        state.currentPersonaType = firstPersona.recommended_persona;
        state.personaSource = 'ai';
        updatePersonaDisplay();
    }
    
    // TODO: 타로 카드 UI 동적 업데이트 (필요시)
}

function updateMasteryRecommendations(recommendations) {
    if (!recommendations || recommendations.length === 0) return;
    
    window.MASTERY_RECOMMENDATIONS = recommendations;
    
    const container = document.getElementById('masteryRecommendations');
    if (!container) return;
    
    container.innerHTML = recommendations.map(rec => `
        <div class="mastery-item ${rec.completed ? 'completed' : ''}" 
             data-id="${rec.id}"
             onclick="showMasteryDetail(${rec.id})">
            <span class="mastery-check">${rec.completed ? '✅' : '⬜'}</span>
            <div class="mastery-content">
                <span class="mastery-concept">${escapeHtml(rec.concept)}</span>
                <span class="mastery-importance ${rec.importance}">${(rec.importance || 'medium').toUpperCase()}</span>
            </div>
            <span class="mastery-arrow">→</span>
        </div>
    `).join('');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ========== 장기기억 도달 시 집중숙련 표시 ==========
function showMasterySection() {
    const section = document.getElementById('masterySection');
    if (section) {
        section.classList.remove('hidden');
    }
}

function hideMasterySection() {
    const section = document.getElementById('masterySection');
    if (section) {
        section.classList.add('hidden');
    }
}

// 5단계 완료 시 집중숙련 섹션 표시
function onLongTermMemoryReached() {
    showMasterySection();
    showFeedback('🏆 장기기억화 단계 도달! 집중숙련을 시작해보세요');
}

// ========== 집중숙련 상세 모달 ==========
let currentMasteryId = null;
let masteryCanvas = null;
let masteryCtx = null;
let masteryRepCompleted = 0;

function showMasteryDetail(id) {
    const recommendations = window.MASTERY_RECOMMENDATIONS || [];
    const rec = recommendations.find(r => r.id === id);
    
    if (!rec) return;
    
    currentMasteryId = id;
    masteryRepCompleted = rec.repetition_completed || 0;
    
    // 모달 내용 업데이트
    document.getElementById('masteryModalTitle').textContent = `집중숙련 #${id}`;
    document.getElementById('masteryModalConcept').textContent = rec.concept;
    document.getElementById('masteryPracticeContent').innerHTML = formatPracticeContent(rec.practice_content);
    document.getElementById('masteryRepCompleted').textContent = masteryRepCompleted;
    document.getElementById('masteryRepTarget').textContent = rec.repetition_count || 3;
    
    // 캔버스 초기화
    initMasteryCanvas();
    
    // 모달 표시
    document.getElementById('masteryModal').classList.remove('hidden');
}

function formatPracticeContent(content) {
    if (!content) return '';
    // 줄바꿈 처리 및 하이라이트
    return content
        .replace(/\n/g, '<br>')
        .replace(/\[([^\]]+)\]/g, '<span style="color:#fbbf24;font-weight:bold;">[$1]</span>')
        .replace(/(___+)/g, '<span style="border-bottom:2px solid #6366f1;min-width:100px;display:inline-block;">&nbsp;</span>');
}

function closeMasteryModal() {
    document.getElementById('masteryModal').classList.add('hidden');
    currentMasteryId = null;
}

function initMasteryCanvas() {
    masteryCanvas = document.getElementById('masteryCanvas');
    if (!masteryCanvas) return;
    
    masteryCtx = masteryCanvas.getContext('2d');
    masteryCtx.lineWidth = 2;
    masteryCtx.lineCap = 'round';
    masteryCtx.strokeStyle = '#1f2937';
    
    // 캔버스 초기화
    clearMasteryCanvas();
    
    // 이벤트 리스너
    let isDrawing = false;
    let lastX = 0;
    let lastY = 0;
    
    masteryCanvas.addEventListener('mousedown', (e) => {
        isDrawing = true;
        [lastX, lastY] = [e.offsetX, e.offsetY];
    });
    
    masteryCanvas.addEventListener('mousemove', (e) => {
        if (!isDrawing) return;
        masteryCtx.beginPath();
        masteryCtx.moveTo(lastX, lastY);
        masteryCtx.lineTo(e.offsetX, e.offsetY);
        masteryCtx.stroke();
        [lastX, lastY] = [e.offsetX, e.offsetY];
    });
    
    masteryCanvas.addEventListener('mouseup', () => isDrawing = false);
    masteryCanvas.addEventListener('mouseout', () => isDrawing = false);
    
    // 터치 지원
    masteryCanvas.addEventListener('touchstart', (e) => {
        e.preventDefault();
        const touch = e.touches[0];
        const rect = masteryCanvas.getBoundingClientRect();
        isDrawing = true;
        [lastX, lastY] = [touch.clientX - rect.left, touch.clientY - rect.top];
    });
    
    masteryCanvas.addEventListener('touchmove', (e) => {
        if (!isDrawing) return;
        e.preventDefault();
        const touch = e.touches[0];
        const rect = masteryCanvas.getBoundingClientRect();
        const x = touch.clientX - rect.left;
        const y = touch.clientY - rect.top;
        masteryCtx.beginPath();
        masteryCtx.moveTo(lastX, lastY);
        masteryCtx.lineTo(x, y);
        masteryCtx.stroke();
        [lastX, lastY] = [x, y];
    });
    
    masteryCanvas.addEventListener('touchend', () => isDrawing = false);
}

function clearMasteryCanvas() {
    if (!masteryCanvas || !masteryCtx) return;
    masteryCtx.fillStyle = '#ffffff';
    masteryCtx.fillRect(0, 0, masteryCanvas.width, masteryCanvas.height);
}

async function completeMasteryRep() {
    if (!currentMasteryId) return;
    
    masteryRepCompleted++;
    document.getElementById('masteryRepCompleted').textContent = masteryRepCompleted;
    
    const targetRep = parseInt(document.getElementById('masteryRepTarget').textContent) || 3;
    
    if (masteryRepCompleted >= targetRep) {
        // 완료!
        showFeedback('✅ 집중숙련 완료! 훌륭해요!');
        
        // 서버에 완료 기록
        await saveMasteryCompletion(currentMasteryId);
        
        // UI 업데이트
        const item = document.querySelector(`.mastery-item[data-id="${currentMasteryId}"]`);
        if (item) {
            item.classList.add('completed');
            item.querySelector('.mastery-check').textContent = '✅';
        }
        
        // 모달 닫기
        setTimeout(() => closeMasteryModal(), 1000);
    } else {
        showFeedback(`✍️ ${masteryRepCompleted}/${targetRep} 완료! 계속해봐요`);
        clearMasteryCanvas();
    }
}

async function saveMasteryCompletion(recommendationId) {
    try {
        const params = new URLSearchParams({
            wboard_id: window.WBOARD_ID || window.ANALYSIS_ID,
            student_id: window.STUDENT_ID,
            recommendation_id: recommendationId
        });
        
        await fetch('/moodle/local/augmented_teacher/alt42/teachingsupport/AItutor/api/mastery_complete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params
        });
    } catch (error) {
        console.error('집중숙련 완료 저장 오류:', error);
    }
}

// 페이지 로드 시 분석 필요 여부 확인
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        if (window.NEEDS_ANALYSIS) {
            analyzeQuestionIfNeeded();
        }
    }, 1000);
});

// ========== 필기 지연 감지 및 분석 시스템 ==========

/**
 * 필기 지연 감지 초기화
 */
function initWritingDetection() {
    // 화이트보드 iframe에서 오는 메시지 수신
    window.addEventListener('message', handleWhiteboardMessage);
    
    console.log('✏️ 필기 지연 감지 시스템 초기화됨');
}

/**
 * 화이트보드 메시지 핸들러
 */
function handleWhiteboardMessage(event) {
    const data = event.data;
    
    if (!data || !data.type) return;
    
    switch (data.type) {
        case 'whiteboard_writing':
            handleWritingEvent(data);
            break;
        case 'whiteboard_capture':
            handleCaptureResponse(data);
            break;
    }
}

/**
 * 필기 이벤트 처리
 */
function handleWritingEvent(data) {
    const detection = state.writingDetection;
    
    if (data.event === 'stroke_start') {
        // 필기 시작 - 타이머 리셋
        clearPauseTimer();
    } else if (data.event === 'stroke_end') {
        // 필기 종료 - 지연 감지 타이머 시작
        detection.lastStrokeTime = data.timestamp || Date.now();
        detection.strokeCount = data.strokeCount || detection.strokeCount + 1;
        
        startPauseTimer();
    }
}

/**
 * 지연 감지 타이머 시작
 */
function startPauseTimer() {
    const detection = state.writingDetection;
    
    // 기존 타이머 정리
    clearPauseTimer();
    
    // 분석 횟수 제한 확인
    if (detection.analysisCount >= detection.maxAnalysisPerSession) {
        console.log('📊 세션 분석 횟수 한도 도달');
        return;
    }
    
    // 이미 분석 중이면 스킵
    if (detection.isAnalyzing) return;
    
    // 지연 타이머 설정
    detection.pauseTimer = setTimeout(() => {
        triggerWritingAnalysis();
    }, detection.pauseThreshold);
}

/**
 * 지연 타이머 정리
 */
function clearPauseTimer() {
    if (state.writingDetection.pauseTimer) {
        clearTimeout(state.writingDetection.pauseTimer);
        state.writingDetection.pauseTimer = null;
    }
}

/**
 * 필기 분석 트리거
 */
function triggerWritingAnalysis() {
    const detection = state.writingDetection;
    
    // 중복 분석 방지
    if (detection.isAnalyzing) return;
    
    detection.isAnalyzing = true;
    
    // 화이트보드에 캡처 요청
    requestWhiteboardCapture();
    
    console.log('🔍 필기 지연 감지 - 분석 시작');
}

/**
 * 화이트보드 캡처 요청
 */
function requestWhiteboardCapture() {
    const iframe = document.getElementById('whiteboardFrame');
    
    if (iframe && iframe.contentWindow) {
        try {
            iframe.contentWindow.postMessage({
                type: 'capture_whiteboard'
            }, '*');
        } catch (e) {
            console.error('화이트보드 캡처 요청 실패:', e);
            state.writingDetection.isAnalyzing = false;
        }
    } else {
        // iframe 접근 불가 시 직접 캡처 시도
        captureWhiteboardDirect();
    }
}

/**
 * 화이트보드 직접 캡처 (같은 도메인인 경우)
 */
function captureWhiteboardDirect() {
    const iframe = document.getElementById('whiteboardFrame');
    
    try {
        const canvas = iframe.contentDocument.getElementById('canvas');
        if (canvas) {
            const dataUrl = canvas.toDataURL('image/png');
            handleCaptureResponse({
                type: 'whiteboard_capture',
                imageData: dataUrl,
                timestamp: Date.now()
            });
        }
    } catch (e) {
        console.error('직접 캡처 실패:', e);
        state.writingDetection.isAnalyzing = false;
    }
}

/**
 * 캡처 응답 처리
 */
function handleCaptureResponse(data) {
    const detection = state.writingDetection;
    
    if (!data.imageData) {
        detection.isAnalyzing = false;
        return;
    }
    
    detection.capturedImage = data.imageData;
    
    // OpenAI 분석 API 호출
    analyzeWritingWithAI(data.imageData);
}

/**
 * OpenAI를 통한 필기 분석
 */
async function analyzeWritingWithAI(whiteboardImage) {
    const detection = state.writingDetection;
    const currentStep = state.steps.find(s => s.status === 'current');
    const pauseDuration = Math.round((Date.now() - detection.lastStrokeTime) / 1000);
    
    // 분석 중 표시
    showFeedback('🔍 AI가 필기를 분석하고 있어요...');
    
    try {
        const response = await fetch('/moodle/local/augmented_teacher/alt42/teachingsupport/AItutor/api/analyze_writing.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                whiteboard_image: whiteboardImage,
                question_image: window.QUESTION_IMAGE,
                solution_image: window.SOLUTION_IMAGE,
                student_id: window.STUDENT_ID,
                content_id: window.CONTENT_ID,
                current_step: currentStep?.id || 1,
                current_emotion: state.emotion.type,
                persona_type: state.currentPersonaType,
                pause_duration: pauseDuration
            })
        });
        
        const result = await response.json();
        
        if (result.success && result.data) {
            handleWritingAnalysisResult(result.data);
            detection.analysisCount++;
        } else {
            console.error('분석 실패:', result.error);
            showFeedback('🤔 분석 중 문제가 발생했어요. 계속 풀어봐!');
        }
    } catch (error) {
        console.error('분석 API 호출 오류:', error);
        showFeedback('📝 잘하고 있어! 천천히 생각해봐');
    } finally {
        detection.isAnalyzing = false;
    }
}

/**
 * 분석 결과 처리 및 피드백 표시
 */
function handleWritingAnalysisResult(analysisData) {
    const feedback = analysisData.feedback;
    const nextAction = analysisData.next_action;
    const writingAnalysis = analysisData.writing_analysis;
    
    // 피드백 메시지 표시
    if (feedback && feedback.message) {
        // 피드백 타입에 따른 이모지 추가
        const feedbackEmojis = {
            encouragement: '💪',
            hint: '💡',
            correction: '✏️',
            guidance: '🎯',
            praise: '🌟'
        };
        const emoji = feedbackEmojis[feedback.type] || '📝';
        showFeedback(`${emoji} ${feedback.message}`);
        
        // 상세 힌트가 있으면 3초 후 추가 표시
        if (feedback.detailed_hint) {
            setTimeout(() => {
                showFeedback(`💡 힌트: ${feedback.detailed_hint}`);
            }, 3500);
        }
    }
    
    // 다음 단계 유도
    if (nextAction) {
        handleNextActionSuggestion(nextAction, writingAnalysis);
    }
    
    // 서버에 분석 결과 저장
    saveInteraction('writing_analysis', {
        analysis_result: analysisData,
        current_step: state.steps.find(s => s.status === 'current')?.id,
        emotion: state.emotion.type
    });
    
    // 진행률 업데이트 (분석 결과 기반)
    if (writingAnalysis && writingAnalysis.progress_percent) {
        updateProgressFromAnalysis(writingAnalysis.progress_percent);
    }
}

/**
 * 다음 단계 유도 처리
 */
function handleNextActionSuggestion(nextAction, writingAnalysis) {
    const currentStep = state.steps.find(s => s.status === 'current');
    
    switch (nextAction.type) {
        case 'proceed':
            // 다음 단계로 진행 제안
            if (nextAction.confidence >= 0.8 && currentStep) {
                setTimeout(() => {
                    showFeedback(`✅ 잘했어! ${currentStep.label} 완료! 다음 단계로 넘어갈까?`);
                    // 자동으로 다음 단계로 이동 제안 (제스처로 확인)
                }, 4000);
            }
            break;
            
        case 'review':
            // 검토 제안
            showFeedback('🔍 한번 더 확인해볼까? 실수가 있을 수 있어');
            break;
            
        case 'explain':
            // 추가 설명 필요
            if (writingAnalysis && writingAnalysis.next_step_needed) {
                setTimeout(() => {
                    showFeedback(`📖 ${writingAnalysis.next_step_needed}`);
                }, 4000);
            }
            break;
            
        case 'encourage':
            // 격려
            const encouragements = [
                '💪 잘하고 있어! 조금만 더 힘내!',
                '🌟 어려운 문제지만 할 수 있어!',
                '🎯 집중해서 한 단계씩 해보자!'
            ];
            const randomEncouragement = encouragements[Math.floor(Math.random() * encouragements.length)];
            setTimeout(() => {
                showFeedback(randomEncouragement);
            }, 4000);
            break;
            
        case 'wait':
        default:
            // 기다리기 - 추가 행동 없음
            break;
    }
}

/**
 * 분석 결과 기반 진행률 업데이트
 */
function updateProgressFromAnalysis(progressPercent) {
    // 현재 단계와 전체 진행률 조합
    const currentStep = state.steps.find(s => s.status === 'current');
    if (!currentStep) return;
    
    const stepIndex = state.steps.findIndex(s => s.id === currentStep.id);
    const stepsCompleted = stepIndex;
    const stepProgress = progressPercent / 100;
    
    // 전체 진행률 = (완료 단계 + 현재 단계 진행률) / 전체 단계
    const overallProgress = Math.round(((stepsCompleted + stepProgress) / state.steps.length) * 100);
    
    // UI 업데이트 (부드러운 전환)
    const percentEl = document.getElementById('progressPercent');
    const fillEl = document.getElementById('progressFill');
    
    if (percentEl && fillEl) {
        percentEl.textContent = `${overallProgress}%`;
        fillEl.style.transition = 'width 0.5s ease';
        fillEl.style.width = `${overallProgress}%`;
    }
}

/**
 * 필기 감지 설정 변경
 */
function setWritingDetectionThreshold(seconds) {
    state.writingDetection.pauseThreshold = seconds * 1000;
    console.log(`⏱️ 필기 지연 감지 임계값: ${seconds}초`);
}

/**
 * 필기 분석 수동 트리거
 */
function manualWritingAnalysis() {
    clearPauseTimer();
    triggerWritingAnalysis();
}

// ========== 추천 페르소나 시스템 ==========

/**
 * 추천 페르소나 초기화
 */
function initRecommendedPersona() {
    console.log('[learning_interface.js] 추천 페르소나 시스템 초기화');
    
    // 데이터 확인
    if (!window.PERSONAS_60 || window.PERSONAS_60.length === 0) {
        console.warn('[learning_interface.js:initRecommendedPersona] PERSONAS_60 데이터 없음');
    }
    if (!window.SOLVING_STAGES) {
        console.warn('[learning_interface.js:initRecommendedPersona] SOLVING_STAGES 데이터 없음');
    }
    
    // 현재 단계에 맞는 페르소나 추천
    updateRecommendedPersonaForStep();
    
    // 오디오 리스너 초기화
    initModalAudioListeners();
    
    // 저장된 극복 히스토리 로드
    loadOvercomeHistory();
}

/**
 * 현재 단계에 맞는 추천 페르소나 업데이트
 */
function updateRecommendedPersonaForStep() {
    const currentStep = state.steps.find(s => s.status === 'current');
    if (!currentStep) return;
    
    const stepLabel = currentStep.label;
    const stages = window.SOLVING_STAGES;
    const personas = window.PERSONAS_60;
    
    // 단계 이름 업데이트
    const stepNameEl = document.getElementById('currentStepName');
    if (stepNameEl) {
        stepNameEl.textContent = stepLabel;
    }
    
    let recommendedPersona = null;
    
    // 데이터가 있으면 해당 단계의 페르소나 찾기
    if (stages && personas && personas.length > 0) {
        const stageData = stages[stepLabel];
        
        if (stageData && stageData.ids && stageData.ids.length > 0) {
            // 해당 단계의 페르소나들
            const stagePersonas = stageData.ids
                .map(id => personas.find(p => p.id === id))
                .filter(Boolean);
            
            // 우선순위가 'high'인 것 우선, 없으면 첫 번째
            recommendedPersona = stagePersonas.find(p => p.priority === 'high') 
                || stagePersonas[0];
            
            // 분석 결과가 있으면 그것을 사용
            if (window.ANALYSIS_DATA && window.ANALYSIS_DATA.persona) {
                const analysisPersona = window.ANALYSIS_DATA.persona.find(p => 
                    stageData.ids.includes(p.id) || stageData.ids.includes(parseInt(p.recommended_persona?.replace('persona_', '')))
                );
                if (analysisPersona) {
                    const matchingPersona = personas.find(p => p.id === analysisPersona.id);
                    if (matchingPersona) {
                        recommendedPersona = matchingPersona;
                    }
                }
            }
        }
    }
    
    // 페르소나를 찾지 못한 경우 기본값 사용
    if (!recommendedPersona) {
        // 기본 페르소나 (단계별)
        const defaultPersonas = {
            '문제해석': { id: 15, name: '조건 회피-추론 생략형', icon: '👁️', category: '검증/확인 부재', priority: 'high', desc: '복잡한 조건을 시야 밖으로 밀어두고 직감만으로 추론을 강행하는 패턴입니다.' },
            '식세우기': { id: 2, name: '3초 패배 예감형', icon: '😰', category: '자신감 왜곡', priority: 'high', desc: '못 풀 것 같다는 느낌이 3초 만에 뇌를 잠그고, 관련 개념 연결이 끊어지는 패턴입니다.' },
            '풀이과정': { id: 4, name: '무의식 연쇄 실수형', icon: '⚡', category: '실수 패턴', priority: 'high', desc: '손이 먼저 움직여 사소한 계산 실수가 꼬리를 무는 패턴입니다.' },
            '점검': { id: 52, name: '검산 회피형', icon: '🚫', category: '검증/확인 부재', priority: 'high', desc: '시간 아까워 검산을 건너뛰어 정답률이 흔들리는 패턴입니다.' },
            '장기기억화': { id: 9, name: '연습 회피 관성형', icon: '🏃', category: '학습 습관', priority: 'high', desc: '이해했어 착각으로 반복 연습을 건너뛰고 넘어가는 패턴입니다.' }
        };
        
        recommendedPersona = defaultPersonas[stepLabel] || defaultPersonas['문제해석'];
        console.log('[learning_interface.js:updateRecommendedPersonaForStep] 기본 페르소나 사용:', recommendedPersona.name);
    }
    
    // 상태 업데이트
    state.recommendedPersona.persona = recommendedPersona;
    
    // UI 업데이트
    updateRecommendedPersonaUI(recommendedPersona);
}

/**
 * 추천 페르소나 UI 업데이트
 */
function updateRecommendedPersonaUI(persona) {
    if (!persona) return;
    
    // 아이콘
    const iconEl = document.getElementById('recommendedPersonaIcon');
    if (iconEl) iconEl.textContent = persona.icon || '🎭';
    
    // 이름
    const nameEl = document.getElementById('recommendedPersonaName');
    if (nameEl) nameEl.textContent = persona.name || '페르소나';
    
    // 카테고리
    const categoryEl = document.getElementById('recommendedPersonaCategory');
    if (categoryEl) categoryEl.textContent = persona.category || '-';
    
    // 우선순위 배지
    const priorityEl = document.getElementById('recommendedPersonaPriority');
    if (priorityEl) {
        const priorityLabels = { high: '중요', medium: '보통', low: '낮음' };
        priorityEl.textContent = priorityLabels[persona.priority] || '보통';
        priorityEl.className = `persona-priority-badge ${persona.priority || 'medium'}`;
    }
    
    // 상세 설명
    const descEl = document.getElementById('personaDetailDesc');
    if (descEl) descEl.textContent = persona.desc || '-';
    
    // 음성 URL 설정 (있는 경우)
    updatePersonaAudio(persona);
}

/**
 * 페르소나 상세 모달 열기
 */
function openPersonaDetailModal() {
    const modal = document.getElementById('personaDetailModal');
    const overlay = document.getElementById('personaDetailOverlay');
    
    if (!modal || !overlay) {
        console.error('[learning_interface.js:openPersonaDetailModal] 모달 요소 없음');
        return;
    }
    
    // 현재 추천 페르소나 정보로 모달 업데이트
    const persona = state.recommendedPersona.persona;
    if (persona) {
        updatePersonaModal(persona);
    }
    
    // 현재 단계 업데이트
    const currentStep = state.steps.find(s => s.status === 'current');
    const stepNameEl = document.getElementById('modalCurrentStep');
    if (stepNameEl && currentStep) {
        stepNameEl.textContent = currentStep.label;
    }
    
    // 모달 표시
    overlay.classList.remove('hidden');
    modal.classList.remove('hidden');
    state.recommendedPersona.isDetailOpen = true;
    
    // 극복 히스토리 새로고침
    loadOvercomeHistory();
}

/**
 * 페르소나 상세 모달 닫기
 */
function closePersonaDetailModal() {
    const modal = document.getElementById('personaDetailModal');
    const overlay = document.getElementById('personaDetailOverlay');
    
    if (modal) modal.classList.add('hidden');
    if (overlay) overlay.classList.add('hidden');
    
    state.recommendedPersona.isDetailOpen = false;
    
    // 음성 재생 중이면 정지
    stopModalAudio();
}

/**
 * 페르소나 모달 UI 업데이트
 */
function updatePersonaModal(persona) {
    if (!persona) return;
    
    // 아이콘
    const iconEl = document.getElementById('modalPersonaIcon');
    if (iconEl) iconEl.textContent = persona.icon || '🎭';
    
    // 이름
    const nameEl = document.getElementById('modalPersonaName');
    if (nameEl) nameEl.textContent = persona.name || '페르소나';
    
    // 카테고리
    const categoryEl = document.getElementById('modalPersonaCategory');
    if (categoryEl) categoryEl.textContent = persona.category || '-';
    
    // 우선순위 배지
    const priorityEl = document.getElementById('modalPersonaPriority');
    if (priorityEl) {
        const priorityLabels = { high: '중요', medium: '보통', low: '낮음' };
        priorityEl.textContent = priorityLabels[persona.priority] || '보통';
        priorityEl.className = `modal-priority-badge ${persona.priority || 'medium'}`;
    }
    
    // 상세 설명
    const descEl = document.getElementById('modalPersonaDesc');
    if (descEl) descEl.textContent = persona.desc || '-';
    
    // 음성 URL 설정
    updatePersonaAudio(persona);
}

/**
 * 페르소나 음성 업데이트
 */
function updatePersonaAudio(persona) {
    const audioEl = document.getElementById('modalAudioElement');
    const timeDisplay = document.getElementById('modalAudioTime');
    const progressFill = document.getElementById('modalAudioProgressFill');
    const errorMsg = document.getElementById('modalAudioError');
    
    if (!audioEl) return;
    
    // 이전 재생 정지
    stopModalAudio();
    
    // 음성 파일 경로 설정 (math-persona-system과 동일)
    const audioUrl = `https://mathking.kr/Contents/personas/인지관성 유형분석/${persona.id}.wav`;
    audioEl.src = audioUrl;
    
    // 초기화
    if (timeDisplay) timeDisplay.textContent = '0:00 / 0:00';
    if (progressFill) progressFill.style.width = '0%';
    if (errorMsg) errorMsg.style.display = 'none';
}

/**
 * 시간 포맷팅
 */
function formatAudioTime(seconds) {
    if (isNaN(seconds) || !isFinite(seconds)) return '0:00';
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${secs.toString().padStart(2, '0')}`;
}

/**
 * 모달 오디오 재생/일시정지 토글
 */
function toggleModalAudio() {
    const audioEl = document.getElementById('modalAudioElement');
    const playBtn = document.getElementById('modalAudioPlayBtn');
    const visualizer = document.getElementById('modalAiVisualizer');
    const audioPlayer = document.getElementById('modalAudioPlayer');
    const timeDisplay = document.getElementById('modalAudioTime');
    const errorMsg = document.getElementById('modalAudioError');
    
    // 이미 재생 중이면 정지
    if (state.recommendedPersona.audioPlaying) {
        stopModalAudio();
        return;
    }
    
    // 오디오 파일 재생 시도
    if (audioEl && audioEl.src) {
        audioEl.play().then(() => {
            if (playBtn) {
                playBtn.textContent = '⏸';
                playBtn.classList.add('playing');
            }
            if (visualizer) visualizer.classList.add('playing');
            if (audioPlayer) audioPlayer.classList.add('playing');
            if (errorMsg) errorMsg.style.display = 'none';
            state.recommendedPersona.audioPlaying = true;
        }).catch(err => {
            console.warn('[learning_interface.js:toggleModalAudio] 오디오 재생 실패, TTS 사용:', err.message);
            if (errorMsg) {
                errorMsg.textContent = '재생 실패 - TTS로 대체합니다';
                errorMsg.style.display = 'block';
            }
            // 약간 지연 후 TTS 시작 (UI 업데이트 위해)
            setTimeout(() => {
                speakPersonaWithVisualizer();
            }, 300);
        });
    } else {
        // 오디오 파일이 없으면 바로 TTS
        if (errorMsg) {
            errorMsg.textContent = 'TTS로 재생합니다';
            errorMsg.style.display = 'block';
        }
        setTimeout(() => {
            speakPersonaWithVisualizer();
        }, 100);
    }
}

/**
 * 모달 오디오 정지
 */
function stopModalAudio() {
    const audioEl = document.getElementById('modalAudioElement');
    const playBtn = document.getElementById('modalAudioPlayBtn');
    const visualizer = document.getElementById('modalAiVisualizer');
    const audioPlayer = document.getElementById('modalAudioPlayer');
    const timeDisplay = document.getElementById('modalAudioTime');
    const progressFill = document.getElementById('modalAudioProgressFill');
    
    if (audioEl) {
        audioEl.pause();
        audioEl.currentTime = 0;
    }
    
    // TTS도 정지
    if ('speechSynthesis' in window) {
        speechSynthesis.cancel();
    }
    
    if (playBtn) {
        playBtn.textContent = '▶';
        playBtn.classList.remove('playing');
    }
    if (visualizer) visualizer.classList.remove('playing');
    if (audioPlayer) audioPlayer.classList.remove('playing');
    if (progressFill) progressFill.style.width = '0%';
    
    state.recommendedPersona.audioPlaying = false;
}

/**
 * 오디오 프로그레스 업데이트
 */
function updateModalAudioProgress() {
    const audioEl = document.getElementById('modalAudioElement');
    const progressFill = document.getElementById('modalAudioProgressFill');
    const timeDisplay = document.getElementById('modalAudioTime');
    
    if (audioEl && audioEl.duration && !isNaN(audioEl.duration)) {
        const progress = (audioEl.currentTime / audioEl.duration) * 100;
        if (progressFill) progressFill.style.width = `${progress}%`;
        if (timeDisplay) {
            timeDisplay.textContent = `${formatAudioTime(audioEl.currentTime)} / ${formatAudioTime(audioEl.duration)}`;
        }
    }
}

/**
 * TTS로 페르소나 설명 읽기 (비주얼라이저 연동)
 */
function speakPersonaWithVisualizer() {
    const persona = state.recommendedPersona.persona;
    if (!persona) {
        console.warn('[learning_interface.js:speakPersonaWithVisualizer] 페르소나 데이터 없음');
        return;
    }
    
    if (!('speechSynthesis' in window)) {
        const errorMsg = document.getElementById('modalAudioError');
        if (errorMsg) {
            errorMsg.textContent = 'TTS 미지원 브라우저입니다';
            errorMsg.style.display = 'block';
        }
        return;
    }
    
    // 이전 TTS 취소
    speechSynthesis.cancel();
    
    const utterance = new SpeechSynthesisUtterance();
    utterance.text = `${persona.name}. ${persona.desc}`;
    utterance.lang = 'ko-KR';
    utterance.rate = 0.9;
    utterance.pitch = 1.0;
    utterance.volume = 1.0;
    
    const playBtn = document.getElementById('modalAudioPlayBtn');
    const visualizer = document.getElementById('modalAiVisualizer');
    const audioPlayer = document.getElementById('modalAudioPlayer');
    const timeDisplay = document.getElementById('modalAudioTime');
    const progressFill = document.getElementById('modalAudioProgressFill');
    const errorMsg = document.getElementById('modalAudioError');
    
    // 예상 재생 시간 (대략 8자 = 1초, 한국어 기준)
    const estimatedDuration = Math.max((utterance.text.length / 8) * 1000, 3000);
    let startTime = 0;
    let progressInterval = null;
    
    // 즉시 UI 업데이트 (onstart 전에)
    if (playBtn) {
        playBtn.textContent = '⏸';
        playBtn.classList.add('playing');
    }
    if (visualizer) visualizer.classList.add('playing');
    if (audioPlayer) audioPlayer.classList.add('playing');
    if (errorMsg) errorMsg.style.display = 'none';
    state.recommendedPersona.audioPlaying = true;
    
    // 프로그레스 바 시뮬레이션 즉시 시작
    startTime = Date.now();
    progressInterval = setInterval(() => {
        const elapsed = Date.now() - startTime;
        const percent = Math.min((elapsed / estimatedDuration) * 100, 95);
        if (progressFill) progressFill.style.width = percent + '%';
        if (timeDisplay) {
            timeDisplay.textContent = `${formatAudioTime(elapsed / 1000)} / ${formatAudioTime(estimatedDuration / 1000)}`;
        }
    }, 100);
    
    utterance.onstart = () => {
        console.log('[learning_interface.js:speakPersonaWithVisualizer] TTS 시작');
    };
    
    utterance.onend = () => {
        console.log('[learning_interface.js:speakPersonaWithVisualizer] TTS 완료');
        if (playBtn) {
            playBtn.textContent = '▶';
            playBtn.classList.remove('playing');
        }
        if (visualizer) visualizer.classList.remove('playing');
        if (audioPlayer) audioPlayer.classList.remove('playing');
        if (progressFill) progressFill.style.width = '100%';
        state.recommendedPersona.audioPlaying = false;
        
        if (progressInterval) clearInterval(progressInterval);
        
        // 잠시 후 프로그레스 바 리셋
        setTimeout(() => {
            if (progressFill) progressFill.style.width = '0%';
            if (timeDisplay) timeDisplay.textContent = '0:00 / 0:00';
        }, 1500);
    };
    
    utterance.onerror = (event) => {
        console.error('[learning_interface.js:speakPersonaWithVisualizer] TTS 오류:', event.error);
        if (progressInterval) clearInterval(progressInterval);
        if (playBtn) {
            playBtn.textContent = '▶';
            playBtn.classList.remove('playing');
        }
        if (visualizer) visualizer.classList.remove('playing');
        if (audioPlayer) audioPlayer.classList.remove('playing');
        
        if (errorMsg) {
            errorMsg.textContent = `TTS 오류: ${event.error || '알 수 없음'}`;
            errorMsg.style.display = 'block';
        }
        state.recommendedPersona.audioPlaying = false;
    };
    
    // 약간의 지연 후 재생 (브라우저 호환성)
    setTimeout(() => {
        try {
            speechSynthesis.speak(utterance);
        } catch (e) {
            console.error('[learning_interface.js:speakPersonaWithVisualizer] TTS 실행 오류:', e);
            if (progressInterval) clearInterval(progressInterval);
            if (playBtn) {
                playBtn.textContent = '▶';
                playBtn.classList.remove('playing');
            }
            if (visualizer) visualizer.classList.remove('playing');
            if (audioPlayer) audioPlayer.classList.remove('playing');
            state.recommendedPersona.audioPlaying = false;
        }
    }, 50);
}

// 오디오 이벤트 리스너 초기화
function initModalAudioListeners() {
    const audioEl = document.getElementById('modalAudioElement');
    const progressBar = document.getElementById('modalAudioProgressBar');
    
    if (audioEl) {
        // 시간 업데이트
        audioEl.addEventListener('timeupdate', updateModalAudioProgress);
        
        // 메타데이터 로드
        audioEl.addEventListener('loadedmetadata', () => {
            const timeDisplay = document.getElementById('modalAudioTime');
            if (timeDisplay) {
                timeDisplay.textContent = `0:00 / ${formatAudioTime(audioEl.duration)}`;
            }
        });
        
        // 재생 완료
        audioEl.addEventListener('ended', () => {
            const playBtn = document.getElementById('modalAudioPlayBtn');
            const visualizer = document.getElementById('modalAiVisualizer');
            const audioPlayer = document.getElementById('modalAudioPlayer');
            const progressFill = document.getElementById('modalAudioProgressFill');
            
            if (playBtn) {
                playBtn.textContent = '▶';
                playBtn.classList.remove('playing');
            }
            if (visualizer) visualizer.classList.remove('playing');
            if (audioPlayer) audioPlayer.classList.remove('playing');
            if (progressFill) progressFill.style.width = '100%';
            state.recommendedPersona.audioPlaying = false;
        });
    }
    
    // 프로그레스 바 클릭으로 이동
    if (progressBar) {
        progressBar.addEventListener('click', (e) => {
            const audioEl = document.getElementById('modalAudioElement');
            if (audioEl && audioEl.duration && !isNaN(audioEl.duration)) {
                const rect = progressBar.getBoundingClientRect();
                const clickPosition = (e.clientX - rect.left) / rect.width;
                audioEl.currentTime = clickPosition * audioEl.duration;
            }
        });
    }
}

// 기존 함수 호환성 유지
function playPersonaAudio() {
    toggleModalAudio();
}

/**
 * 극복 레벨 설정
 */
function setOvercomeLevel(level) {
    state.recommendedPersona.overcomeLevel = level;
    
    // 버튼 상태 업데이트 (모달용)
    const buttons = document.querySelectorAll('.overcome-level-btn');
    buttons.forEach(btn => {
        const btnLevel = parseInt(btn.dataset.level);
        btn.classList.toggle('selected', btnLevel === level);
    });
    
    // 피드백 표시
    const levelMessages = {
        1: '😰 시작 전 - 아직 인식 단계예요',
        2: '🤔 인식함 - 패턴을 알아챘어요!',
        3: '💪 노력 중 - 열심히 개선하고 있어요',
        4: '😊 개선됨 - 많이 좋아졌어요!',
        5: '🌟 극복 완료 - 대단해요!'
    };
    
    showFeedback(levelMessages[level] || '레벨 선택됨');
}

/**
 * 극복 상태 저장
 */
async function saveOvercomeStatus() {
    const persona = state.recommendedPersona.persona;
    if (!persona) {
        showFeedback('⚠️ 페르소나가 선택되지 않았어요');
        return;
    }
    
    const level = state.recommendedPersona.overcomeLevel;
    if (level === 0) {
        showFeedback('⚠️ 현재 상태를 선택해주세요');
        return;
    }
    
    const notes = document.getElementById('overcomeNotes')?.value || '';
    
    const record = {
        persona_id: persona.id,
        persona_name: persona.name,
        level: level,
        notes: notes,
        timestamp: new Date().toISOString(),
        step: state.steps.find(s => s.status === 'current')?.label || '-'
    };
    
    // 로컬 히스토리에 추가
    state.recommendedPersona.overcomeHistory.unshift(record);
    
    // UI 업데이트
    updateOvercomeHistoryUI();
    
    // 입력 필드 초기화
    const notesEl = document.getElementById('overcomeNotes');
    if (notesEl) notesEl.value = '';
    
    // 레벨 버튼 초기화
    state.recommendedPersona.overcomeLevel = 0;
    document.querySelectorAll('.overcome-level-btn').forEach(btn => btn.classList.remove('selected'));
    
    // 서버에 저장
    try {
        await fetch('/moodle/local/augmented_teacher/alt42/teachingsupport/AItutor/api/save_overcome.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                student_id: window.STUDENT_ID,
                content_id: window.CONTENT_ID,
                analysis_id: window.ANALYSIS_ID,
                ...record
            })
        });
        
        showFeedback('✅ 극복 상태가 저장되었어요!');
    } catch (error) {
        console.error('[learning_interface.js:saveOvercomeStatus] 저장 오류:', error);
        showFeedback('💾 로컬에 저장됨 (서버 동기화 보류)');
    }
    
    // 상호작용 기록
    saveInteraction('overcome_status_save', record);
}

/**
 * 극복 히스토리 UI 업데이트
 */
function updateOvercomeHistoryUI() {
    const container = document.getElementById('overcomeHistory');
    if (!container) return;
    
    const history = state.recommendedPersona.overcomeHistory;
    
    if (history.length === 0) {
        container.innerHTML = '<p class="history-empty-text">아직 기록이 없습니다. 첫 번째 기록을 남겨보세요!</p>';
        return;
    }
    
    const levelEmojis = {
        1: '😰',
        2: '🤔',
        3: '💪',
        4: '😊',
        5: '🌟'
    };
    
    container.innerHTML = history.slice(0, 10).map(record => {
        const date = new Date(record.timestamp);
        const dateStr = `${date.getMonth() + 1}/${date.getDate()} ${date.getHours()}:${String(date.getMinutes()).padStart(2, '0')}`;
        
        return `
            <div class="history-item">
                <span class="history-emoji">${levelEmojis[record.level] || '📝'}</span>
                <div class="history-content">
                    <span class="history-date">${dateStr} · ${record.step || '-'}</span>
                    <p class="history-note">${record.notes || '(메모 없음)'}</p>
                </div>
            </div>
        `;
    }).join('');
}

/**
 * 극복 히스토리 로드
 */
async function loadOvercomeHistory() {
    try {
        const response = await fetch(`/moodle/local/augmented_teacher/alt42/teachingsupport/AItutor/api/get_overcome.php?student_id=${window.STUDENT_ID}&content_id=${window.CONTENT_ID}`);
        const result = await response.json();
        
        if (result.success && result.data) {
            state.recommendedPersona.overcomeHistory = result.data;
            updateOvercomeHistoryUI();
        }
    } catch (error) {
        console.warn('[learning_interface.js:loadOvercomeHistory] 히스토리 로드 실패:', error);
    }
}

/**
 * 단계 변경 시 추천 페르소나 갱신
 */
function onStepChange() {
    updateRecommendedPersonaForStep();
}

// 기존 handleStepClick 함수 확장 (단계 변경 시 호출)
const originalHandleStepClick = handleStepClick;
handleStepClick = function(stepId) {
    originalHandleStepClick(stepId);
    
    // 추천 페르소나 갱신
    setTimeout(() => {
        onStepChange();
    }, 100);
};

// ========== AI 분석 및 TTS 생성 기능 ==========

/**
 * TTS 상태 초기화 (페이지 로드 시)
 */
function initTtsState() {
    const config = window.TTS_CONFIG || {};
    
    console.log('[learning_interface.js:initTtsState] TTS_CONFIG:', {
        contentId: config.contentId,
        contentsType: config.contentsType,
        existingTtsId: config.existingTtsId,
        existingAudioUrl: config.existingAudioUrl,
        hasTts: config.hasTts
    });
    
    // 기존 TTS가 있는 경우 상태 반영 (contentsid+contentstype으로 찾은 audio_url 기반)
    if (config.hasTts && config.existingTtsId) {
        console.log('[learning_interface.js:initTtsState] 기존 TTS 발견 - ID:', config.existingTtsId, 'AudioUrl:', config.existingAudioUrl);
        
        state.tts.hasGenerated = true;
        state.tts.interactionId = config.existingTtsId;
        
        // 버튼 상태 업데이트
        const btn = document.getElementById('ttsGenerateBtn');
        const iconEl = document.getElementById('ttsBtnIcon');
        const textEl = document.getElementById('ttsBtnText');
        
        if (btn) btn.classList.add('completed');
        if (iconEl) iconEl.textContent = '✅';
        if (textEl) textEl.textContent = 'TTS 완료 (클릭: 듣기/재생성)';
        
        console.log('[learning_interface.js:initTtsState] TTS 버튼 상태 업데이트됨');
        
        // 🔥 새로고침 후에도 플레이어 자동 표시 (contentsid+contentstype 기반)
        setTimeout(() => {
            loadTtsSectionsAndShowPlayer(config.existingTtsId);
        }, 500);
    } else {
        console.log('[learning_interface.js:initTtsState] 기존 TTS 없음 - contentId:', config.contentId, ', contentsType:', config.contentsType);
    }
}

/**
 * 기존 TTS 섹션 로드 및 플레이어 표시 (새로고침 시)
 */
async function loadTtsSectionsAndShowPlayer(interactionId) {
    console.log('[learning_interface.js:loadTtsSectionsAndShowPlayer] 기존 TTS 로드 시작 - ID:', interactionId);
    
    const player = document.getElementById('headerTtsPlayer');
    
    state.tts.interactionId = interactionId;
    state.tts.currentSectionIndex = 0;
    state.tts.autoPlay = false;  // 새로고침 시에는 자동재생 안함
    state.tts.speed = 1.0;
    
    try {
        const config = window.TTS_CONFIG || {};

        // ✅ 새로고침/초기 로드에서도 interactionId가 있으면 id 우선 조회
        // (contentsid+contentstype 조회는 audio_url 조건 때문에 "텍스트만 있고 오디오가 아직 없는" 상태에서 실패할 수 있음)
        let result = null;
        if (interactionId) {
            const apiUrlById = `${config.sectionDataUrl}?format=section&id=${interactionId}`;
            console.log('[learning_interface.js:loadTtsSectionsAndShowPlayer] id로 조회(우선):', interactionId);
            const responseById = await fetch(apiUrlById);
            result = await responseById.json();
        }

        // fallback: contentsid(+contentstype)로 조회
        if (!result || !result.success) {
        let apiUrl = `${config.sectionDataUrl}?format=section`;
        if (config.contentId && config.contentsType !== null && config.contentsType !== undefined) {
            apiUrl += `&contentsid=${config.contentId}&contentstype=${config.contentsType}`;
                console.log('[learning_interface.js:loadTtsSectionsAndShowPlayer] contentsid+contentstype으로 조회(fallback):', config.contentId, config.contentsType);
        } else if (config.contentId) {
            apiUrl += `&contentsid=${config.contentId}`;
                console.log('[learning_interface.js:loadTtsSectionsAndShowPlayer] contentsid로만 조회(fallback):', config.contentId);
            } else if (interactionId) {
            apiUrl += `&id=${interactionId}`;
                console.log('[learning_interface.js:loadTtsSectionsAndShowPlayer] id로 조회(fallback2):', interactionId);
        }
        const response = await fetch(apiUrl);
            result = await response.json();
        }
        
        console.log('[learning_interface.js:loadTtsSectionsAndShowPlayer] API 응답:', result);
        
        const data = result.data || result;
        const sections = data.sections || [];
        const textSections = data.text_sections || [];
        const faqtext = data.faqtext || null;
        
        if (result.success && sections.length > 0) {
            state.tts.sections = sections;
            state.tts.textSections = textSections;
            
            // faqtext 데이터도 함께 로드
            if (faqtext) {
                try {
                    const faqData = typeof faqtext === 'string' ? JSON.parse(faqtext) : faqtext;
                    state.faq.data = faqData;
                    console.log('[learning_interface.js:loadTtsSectionsAndShowPlayer] faqtext 로드 완료:', faqData.length, '개 단계');
                } catch (e) {
                    console.warn('[learning_interface.js:loadTtsSectionsAndShowPlayer] faqtext 파싱 실패:', e);
                }
            }
            
            console.log('[learning_interface.js:loadTtsSectionsAndShowPlayer] 섹션 로드 완료:', sections.length);
            
            // 우측 상단 플레이어 표시
            if (player) {
                renderStepDots();
                updateCurrentStepDisplay();
                updateNavButtons();
                player.classList.remove('hidden');
                console.log('[learning_interface.js:loadTtsSectionsAndShowPlayer] 플레이어 표시됨');
            }
        } else {
            console.log('[learning_interface.js:loadTtsSectionsAndShowPlayer] 섹션 데이터 없음');
        }
    } catch (error) {
        console.error('[learning_interface.js:loadTtsSectionsAndShowPlayer] 로드 실패:', error);
    }
}

/**
 * AI 분석 시작
 */
async function startAiAnalysis() {
    const btn = document.getElementById('aiAnalysisBtn');
    const iconEl = document.getElementById('aiAnalysisBtnIcon');
    const textEl = document.getElementById('aiAnalysisBtnText');
    const spinner = document.getElementById('aiAnalysisSpinner');
    
    // 🔮 양자 붕괴 학습 미로 새 창으로 열기
    const config = window.TTS_CONFIG || {};
    const contentsId = config.contentsId || new URLSearchParams(window.location.search).get('id');
    if (contentsId) {
        const quantumUrl = `/moodle/local/augmented_teacher/alt42/teachingsupport/AItutor/ui/quantum_modeling.php?id=${encodeURIComponent(contentsId)}`;
        window.open(quantumUrl, 'quantum_maze', 'width=1200,height=800,resizable=yes,scrollbars=yes');
        console.log('[learning_interface.js:startAiAnalysis] 양자 미로 열기:', quantumUrl);
    }
    
    if (!btn || btn.classList.contains('completed')) {
        console.log('[learning_interface.js:startAiAnalysis] 이미 분석 완료됨');
        return;
    }
    
    if (btn.disabled) {
        console.log('[learning_interface.js:startAiAnalysis] 분석 불가능 상태');
        return;
    }
    
    console.log('[learning_interface.js:startAiAnalysis] AI 분석 시작');
    
    // 버튼 상태 업데이트
    btn.disabled = true;
    if (iconEl) iconEl.style.display = 'none';
    if (textEl) textEl.textContent = '분석 중...';
    if (spinner) spinner.classList.remove('hidden');
    
    try {
        const config = window.TTS_CONFIG || {};
        const response = await fetch(`/moodle/local/augmented_teacher/alt42/teachingsupport/AItutor/api/analyze_question.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                analysis_id: config.analysisId,
                student_id: config.studentId,
                content_id: config.contentId,
                question_image: config.questionImage
            })
        });
        
        const result = await response.json();
        console.log('[learning_interface.js:startAiAnalysis] 분석 결과:', result);
        
        if (result.success) {
            // 분석 완료 상태로 업데이트
            btn.classList.add('completed');
            btn.disabled = false;
            if (iconEl) {
                iconEl.style.display = '';
                iconEl.textContent = '✅';
            }
            if (textEl) textEl.textContent = 'AI 분석 완료';
            if (spinner) spinner.classList.add('hidden');
            
            // 분석 결과로 페르소나 업데이트
            if (result.data && result.data.persona) {
                window.ITEM_PERSONAS = result.data.persona;
                updateRecommendedPersonaForStep();
            }
            
            showFeedback('AI 분석이 완료되었습니다! 🎉');
        } else {
            throw new Error(result.error || '분석 실패');
        }
    } catch (error) {
        console.error('[learning_interface.js:startAiAnalysis] 분석 오류:', error);
        
        // 오류 상태로 복원
        btn.disabled = false;
        if (iconEl) {
            iconEl.style.display = '';
            iconEl.textContent = '🔬';
        }
        if (textEl) textEl.textContent = 'AI 분석';
        if (spinner) spinner.classList.add('hidden');
        
        showFeedback('분석 중 오류가 발생했습니다. 다시 시도해주세요.');
    }
}

/**
 * TTS 버튼 클릭 핸들러
 * - 완료 상태면 다시 생성할지 확인
 * - 미생성 상태면 바로 생성
 */
function handleTtsButtonClick() {
    const btn = document.getElementById('ttsGenerateBtn');
    
    // 이미 완료된 경우 - 재생성 여부 확인
    if (state.tts.hasGenerated) {
        showTtsRegenerateConfirm();
        return;
    }
    
    // 아직 생성 안 된 경우 - 바로 생성
    startTtsGeneration();
}

/**
 * TTS 재생성 확인 모달 표시
 */
function showTtsRegenerateConfirm() {
    // 기존 모달 제거
    const existing = document.getElementById('ttsRegenerateModal');
    if (existing) existing.remove();
    
    const modal = document.createElement('div');
    modal.id = 'ttsRegenerateModal';
    modal.className = 'tts-regenerate-modal-overlay';
    modal.innerHTML = `
        <div class="tts-regenerate-modal">
            <div class="tts-regenerate-icon">🔊</div>
            <h3>TTS 다시 생성하시겠습니까?</h3>
            <p>새로운 단계별 풀이 설명이 생성됩니다.</p>
            <div class="tts-regenerate-buttons">
                <button class="tts-regenerate-btn tts-regenerate-yes" onclick="confirmTtsRegenerate()">
                    ✅ 네, 다시 생성
                </button>
                <button class="tts-regenerate-btn tts-regenerate-listen" onclick="openExistingTts()">
                    🎧 기존 TTS 듣기
                </button>
                <button class="tts-regenerate-btn tts-regenerate-faq" onclick="generateFaqtext()" style="background: linear-gradient(135deg, #9b59b6, #8e44ad); color: white;">
                    📚 점층상호작용 생성
                </button>
                <button class="tts-regenerate-btn tts-regenerate-no" onclick="closeTtsRegenerateModal()">
                    ❌ 취소
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // 배경 클릭 시 닫기
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeTtsRegenerateModal();
    });
}

/**
 * TTS 재생성 확인
 */
function confirmTtsRegenerate() {
    console.log('[learning_interface.js:confirmTtsRegenerate] 재생성 확인 버튼 클릭');
    
    closeTtsRegenerateModal();
    
    // 상태 초기화
    state.tts.hasGenerated = false;
    state.tts.interactionId = null;
    state.tts.sections = [];
    state.tts.textSections = [];
    state.tts.currentSectionIndex = 0;
    
    // 버튼 상태 초기화
    const btn = document.getElementById('ttsGenerateBtn');
    const iconEl = document.getElementById('ttsBtnIcon');
    const textEl = document.getElementById('ttsBtnText');
    
    console.log('[learning_interface.js:confirmTtsRegenerate] 버튼 요소:', { btn: !!btn, iconEl: !!iconEl, textEl: !!textEl });
    
    if (iconEl) iconEl.textContent = '🔊';
    if (textEl) textEl.textContent = 'TTS 재생성 중...';
    if (btn) btn.classList.remove('completed');
    
    // 재생성 시작 (force_regenerate=true)
    console.log('[learning_interface.js:confirmTtsRegenerate] startTtsGeneration(true) 호출');
    startTtsGeneration(true);
}

/**
 * 기존 TTS 열기
 */
function openExistingTts() {
    closeTtsRegenerateModal();
    
    if (state.tts.interactionId && typeof StepPlayer !== 'undefined' && StepPlayer.open) {
        StepPlayer.open(state.tts.interactionId);
    } else {
        showFeedback('TTS 데이터를 불러올 수 없습니다.');
    }
}

/**
 * 재생성 확인 모달 닫기
 */
function closeTtsRegenerateModal() {
    const modal = document.getElementById('ttsRegenerateModal');
    if (modal) modal.remove();
}

/**
 * 점층상호작용 (faqtext) 생성
 * narration_text에서 @로 구분된 각 단계별로 6가지 점층적 표현을 생성
 */
async function generateFaqtext() {
    console.log('[learning_interface.js:generateFaqtext] ========== 점층상호작용 생성 시작 ==========');
    
    closeTtsRegenerateModal();
    
    const interactionId = state.tts.interactionId;
    if (!interactionId) {
        showFeedback('❌ TTS가 먼저 생성되어야 합니다.');
        return;
    }
    
    // 로딩 모달 표시
    showFaqGeneratingModal();
    
    try {
        const config = window.TTS_CONFIG || {};
        
        const requestBody = {
            action: 'generate_faqtext',
            interaction_id: interactionId,
            content_id: config.contentId,
            student_id: config.studentId
        };
        
        console.log('[learning_interface.js:generateFaqtext] API 호출:', requestBody);
        showFeedback('📚 점층상호작용 생성 중... AI가 6단계 반복 강조 멘트를 만들고 있어요');
        
        const response = await fetch('/moodle/local/augmented_teacher/alt42/teachingsupport/api/generate_faqtext.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(requestBody)
        });
        
        const result = await response.json();
        console.log('[learning_interface.js:generateFaqtext] 결과:', result);
        
        closeFaqGeneratingModal();
        
        if (result.success) {
            showFeedback('✅ 점층상호작용 생성 완료! ' + result.sections_count + '개 단계의 강조 멘트가 생성되었습니다.');
            
            // 결과 미리보기 모달 표시 (선택사항)
            if (result.faqtext_preview) {
                showFaqPreviewModal(result.faqtext_preview);
            }
        } else {
            showFeedback('❌ 점층상호작용 생성 실패: ' + (result.error || '알 수 없는 오류'));
        }
    } catch (error) {
        console.error('[learning_interface.js:generateFaqtext] 오류:', error);
        closeFaqGeneratingModal();
        showFeedback('❌ 점층상호작용 생성 중 오류 발생: ' + error.message);
    }
}

/**
 * FAQ 생성 중 로딩 모달 표시
 */
function showFaqGeneratingModal() {
    const existing = document.getElementById('faqGeneratingModal');
    if (existing) existing.remove();
    
    const modal = document.createElement('div');
    modal.id = 'faqGeneratingModal';
    modal.className = 'tts-regenerate-modal-overlay';
    modal.innerHTML = `
        <div class="tts-regenerate-modal" style="text-align: center;">
            <div class="tts-regenerate-icon" style="font-size: 48px; animation: pulse 1.5s infinite;">📚</div>
            <h3>점층상호작용 생성 중...</h3>
            <p style="color: #666;">각 단계별 6가지 반복 강조 멘트를 AI가 만들고 있어요</p>
            <div class="faq-progress-dots" style="margin-top: 15px;">
                <span style="animation: bounce 0.6s infinite 0s;">●</span>
                <span style="animation: bounce 0.6s infinite 0.1s;">●</span>
                <span style="animation: bounce 0.6s infinite 0.2s;">●</span>
            </div>
            <style>
                @keyframes pulse {
                    0%, 100% { transform: scale(1); }
                    50% { transform: scale(1.1); }
                }
                @keyframes bounce {
                    0%, 100% { transform: translateY(0); opacity: 0.4; }
                    50% { transform: translateY(-5px); opacity: 1; }
                }
                .faq-progress-dots span {
                    display: inline-block;
                    margin: 0 3px;
                    font-size: 14px;
                    color: #9b59b6;
                }
            </style>
        </div>
    `;
    
    document.body.appendChild(modal);
}

/**
 * FAQ 생성 중 로딩 모달 닫기
 */
function closeFaqGeneratingModal() {
    const modal = document.getElementById('faqGeneratingModal');
    if (modal) modal.remove();
}

/**
 * FAQ 미리보기 모달 표시
 */
function showFaqPreviewModal(previewData) {
    const existing = document.getElementById('faqPreviewModal');
    if (existing) existing.remove();
    
    // 미리보기 데이터에서 첫 번째 단계만 표시
    let previewHtml = '';
    if (previewData && previewData.length > 0) {
        const firstStep = previewData[0];
        previewHtml = `
            <div style="background: #f8f9fa; padding: 12px; border-radius: 8px; margin-top: 10px; text-align: left;">
                <div style="font-weight: bold; color: #9b59b6; margin-bottom: 8px;">📖 ${firstStep.step_label || '1단계'}</div>
                <div style="font-size: 12px; color: #666; margin-bottom: 8px;">원문: ${(firstStep.original || '').substring(0, 80)}...</div>
                <div style="font-size: 11px;">
                    ${firstStep.faqtext ? firstStep.faqtext.slice(0, 3).map((text, i) => 
                        `<div style="margin: 4px 0; padding: 4px 8px; background: ${['#e8f5e9', '#fff3e0', '#e3f2fd'][i]}; border-radius: 4px;">
                            ${['🔹', '🔸', '🔷'][i]} ${text.substring(0, 60)}...
                        </div>`
                    ).join('') : ''}
                </div>
            </div>
        `;
    }
    
    const modal = document.createElement('div');
    modal.id = 'faqPreviewModal';
    modal.className = 'tts-regenerate-modal-overlay';
    modal.innerHTML = `
        <div class="tts-regenerate-modal">
            <div class="tts-regenerate-icon" style="font-size: 36px;">✅</div>
            <h3>점층상호작용 생성 완료!</h3>
            <p>각 단계별로 6가지 점층적 강조 표현이 생성되었습니다.</p>
            ${previewHtml}
            <div class="tts-regenerate-buttons" style="margin-top: 15px;">
                <button class="tts-regenerate-btn tts-regenerate-yes" onclick="closeFaqPreviewModal()" style="background: #9b59b6;">
                    확인
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // 배경 클릭 시 닫기
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeFaqPreviewModal();
    });
}

/**
 * FAQ 미리보기 모달 닫기
 */
function closeFaqPreviewModal() {
    const modal = document.getElementById('faqPreviewModal');
    if (modal) modal.remove();
}

/**
 * TTS 생성 시작
 * @param {boolean} forceRegenerate - 강제 재생성 여부
 */
async function startTtsGeneration(forceRegenerate = false) {
    console.log('[learning_interface.js:startTtsGeneration] ========== TTS 생성 시작 ==========');
    console.log('[learning_interface.js:startTtsGeneration] forceRegenerate:', forceRegenerate);
    
    const btn = document.getElementById('ttsGenerateBtn');
    const iconEl = document.getElementById('ttsBtnIcon');
    const textEl = document.getElementById('ttsBtnText');
    const spinner = document.getElementById('ttsSpinner');
    
    console.log('[learning_interface.js:startTtsGeneration] DOM 요소:', {
        btn: !!btn,
        iconEl: !!iconEl,
        textEl: !!textEl,
        spinner: !!spinner
    });
    
    if (state.tts.isGenerating) {
        console.log('[learning_interface.js:startTtsGeneration] 이미 생성 중 - 종료');
        return;
    }
    
    state.tts.isGenerating = true;
    
    // 버튼 상태 업데이트 (클릭 시에만 스피너 표시)
    if (btn) btn.disabled = true;
    if (iconEl) iconEl.style.display = 'none';
    if (textEl) textEl.textContent = forceRegenerate ? 'TTS 재생성 중...' : 'TTS 생성 중...';
    if (spinner) spinner.classList.remove('hidden');
    
    try {
        const config = window.TTS_CONFIG || {};
        
        console.log('[learning_interface.js:startTtsGeneration] TTS_CONFIG:', {
            studentId: config.studentId,
            contentId: config.contentId,
            whiteboardId: config.whiteboardId,
            hasQuestionImage: !!config.questionImage,
            hasSolutionImage: !!config.solutionImage
        });
        
        const requestBody = {
            student_id: config.studentId,
            content_id: config.contentId,
            analysis_id: config.analysisId,
            whiteboard_id: config.whiteboardId,
            question_image: config.questionImage,
            solution_image: config.solutionImage,
            generate_audio: true,
            force_regenerate: forceRegenerate
        };
        
        console.log('[learning_interface.js:startTtsGeneration] API 호출 시작...');
        showFeedback(forceRegenerate ? '🔄 TTS 재생성 중... AI가 새 설명을 준비하고 있어요' : '🎙️ TTS 생성 중... 잠시만 기다려주세요');
        
        // teachingagent.php와 동일한 동작 수행
        const response = await fetch('/moodle/local/augmented_teacher/alt42/teachingsupport/api/create_teaching_interaction.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(requestBody)
        });
        
        console.log('[learning_interface.js:startTtsGeneration] API 응답 HTTP 상태:', response.status);
        
        const result = await response.json();
        console.log('[learning_interface.js:startTtsGeneration] TTS 생성 결과:', result);
        
        if (result.success) {
            state.tts.interactionId = result.interaction_id;
            state.tts.hasGenerated = true;
            
            // 버튼 상태 업데이트 - 완료 상태
            btn.disabled = false;
            btn.classList.add('completed');
            if (iconEl) {
                iconEl.style.display = '';
                iconEl.textContent = '✅';
            }
            if (textEl) textEl.textContent = 'TTS 완료 (클릭: 재생/재생성)';
            if (spinner) spinner.classList.add('hidden');
            
            // 섹션 데이터 로드 및 단계별 플레이어 표시
            await loadTtsSectionsAndShow(result.interaction_id);
            
            showFeedback('단계별 풀이가 생성되었습니다! 🔊 클릭하여 단계별로 공부하세요!');
        } else {
            throw new Error(result.error || 'TTS 생성 실패');
        }
    } catch (error) {
        console.error('[learning_interface.js:startTtsGeneration] TTS 생성 오류:', error);
        
        // 오류 상태로 복원
        btn.disabled = false;
        if (iconEl) {
            iconEl.style.display = '';
            iconEl.textContent = '🔊';
        }
        if (textEl) textEl.textContent = 'TTS 생성';
        if (spinner) spinner.classList.add('hidden');
        
        showFeedback('TTS 생성 중 오류가 발생했습니다: ' + (error && error.message ? error.message : '알 수 없는 오류'));
    } finally {
        state.tts.isGenerating = false;
    }
}

/**
 * TTS 섹션 로드 및 플레이어 표시 (teachingagent.php와 동일한 방식)
 */
async function loadTtsSectionsAndShow(interactionId) {
    const player = document.getElementById('headerTtsPlayer');
    
    state.tts.interactionId = interactionId;
    state.tts.currentSectionIndex = 0;
    state.tts.autoPlay = false;  // 자동 진행 비활성화 - 사용자가 직접 단계 이동
    state.tts.speed = 1.0;
    
    try {
        const config = window.TTS_CONFIG || {};

        // ✅ 생성 직후에는 interactionId로 id 조회를 최우선
        // (contentsid+contentstype 조회는 audio_url 조건 때문에 아직 오디오가 없으면 실패 가능)
        let result = null;
        if (interactionId) {
            const apiUrlById = `${config.sectionDataUrl}?format=section&id=${interactionId}`;
            console.log('[learning_interface.js:loadTtsSectionsAndShow] id로 조회(우선):', interactionId);
            const responseById = await fetch(apiUrlById);
            result = await responseById.json();
        }

        // fallback: contentsid(+contentstype)
        if (!result || !result.success) {
        let apiUrl = `${config.sectionDataUrl}?format=section`;
        if (config.contentId && config.contentsType !== null && config.contentsType !== undefined) {
            apiUrl += `&contentsid=${config.contentId}&contentstype=${config.contentsType}`;
                console.log('[learning_interface.js:loadTtsSectionsAndShow] contentsid+contentstype으로 조회(fallback):', config.contentId, config.contentsType);
        } else if (config.contentId) {
            apiUrl += `&contentsid=${config.contentId}`;
                console.log('[learning_interface.js:loadTtsSectionsAndShow] contentsid로만 조회(fallback):', config.contentId);
            } else if (interactionId) {
            apiUrl += `&id=${interactionId}`;
                console.log('[learning_interface.js:loadTtsSectionsAndShow] id로 조회(fallback2):', interactionId);
        }
        const response = await fetch(apiUrl);
            result = await response.json();
        }
        
        console.log('[learning_interface.js:loadTtsSectionsAndShow] API 응답:', result);
        
        // 응답 형식 처리 (result.data.sections 또는 result.sections)
        const data = result.data || result;
        const sections = data.sections || [];
        const textSections = data.text_sections || [];
        const faqtext = data.faqtext || null;
        
        if (result.success && sections.length > 0) {
            state.tts.sections = sections;
            state.tts.textSections = textSections;
            
            // faqtext 데이터도 함께 로드
            if (faqtext) {
                try {
                    const faqData = typeof faqtext === 'string' ? JSON.parse(faqtext) : faqtext;
                    state.faq.data = faqData;
                    console.log('[learning_interface.js:loadTtsSectionsAndShow] faqtext 로드 완료:', faqData.length, '개 단계');
                } catch (e) {
                    console.warn('[learning_interface.js:loadTtsSectionsAndShow] faqtext 파싱 실패:', e);
                }
            }
            
            console.log('[learning_interface.js:loadTtsSectionsAndShow] 섹션 로드 완료:', sections.length);
            
            // 우측 상단 TTS 플레이어 표시
            if (player) {
                renderStepDots();
                updateCurrentStepDisplay();
                updateNavButtons();
                player.classList.remove('hidden');
                console.log('[learning_interface.js:loadTtsSectionsAndShow] 헤더 플레이어 표시됨');
            }
            
            // StepPlayer 모달은 "오디오 URL이 있는 경우"에만 자동으로 열기
            // (오디오 생성 실패/지연인 경우 StepPlayer는 audio-first라 오류가 날 수 있음)
            const hasAnyAudioUrl = Array.isArray(sections) && sections.some(s => {
                if (typeof s === 'string') return !!String(s).trim();
                if (s && typeof s === 'object') return !!(s.audio_url || s.url || s.src || s.path);
                return false;
            });

            if (hasAnyAudioUrl && typeof StepPlayer !== 'undefined' && StepPlayer.open) {
                StepPlayer.open(interactionId);
                console.log('[learning_interface.js:loadTtsSectionsAndShow] StepPlayer 모달 열림');
            } else {
                console.warn('[learning_interface.js:loadTtsSectionsAndShow] StepPlayer 자동 열기 스킵 (오디오 없음/미로딩) - 헤더 플레이어 사용');
                // StepPlayer가 없으면 헤더 플레이어로 자동재생
                if (state.tts.autoPlay && state.tts.sections.length > 0) {
                    playTtsSection(0);
                }
            }
        }
    } catch (error) {
        console.error('[learning_interface.js:loadTtsSectionsAndShow] 섹션 로드 실패:', error);
    }
}

/**
 * 단계 닷 렌더링
 */
function renderStepDots() {
    const container = document.getElementById('ttsStepDots');
    if (!container) return;
    
    const sections = state.tts.sections || [];
    container.innerHTML = sections.map((_, idx) => {
        const isActive = idx === state.tts.currentSectionIndex;
        const isCompleted = idx < state.tts.currentSectionIndex;
        return `<button class="step-dot ${isActive ? 'active' : ''} ${isCompleted ? 'completed' : ''}" 
                        onclick="playTtsSection(${idx})" 
                        title="단계 ${idx + 1}"></button>`;
    }).join('');
}

/**
 * 특정 섹션 재생
 */
function playTtsSection(index) {
    const sections = state.tts.sections;
    if (!sections || index < 0 || index >= sections.length) return;
    
    // 기존 재생 중지
    stopCurrentTts();
    
    // 🔥 섹션이 변경되면 FAQ 세션 카운트 리셋
    if (state.tts.currentSectionIndex !== index) {
        state.faq.sessionGestureCount = 0;
        state.faq.completedOnce = false;
        state.faq.currentStepData = null; // 현재 단계 데이터도 리셋
        console.log('[playTtsSection] FAQ 세션 카운트 리셋 (새 섹션:', index, ')');
    }
    
    state.tts.currentSectionIndex = index;
    state.tts.isPlaying = true;
    
    // UI 업데이트
    renderStepDots();
    updateNavButtons();
    updatePlayPauseButton(true);
    updateCurrentStepDisplay();
    
    // 🔥 TTS 섹션에 맞는 풀이 단계로 자동 이동
    const stepId = mapSectionToStep(index, sections.length);
    updateStepForSection(stepId);
    
    const section = sections[index];
    const textSections = state.tts.textSections || [];
    const text = textSections[index] || (typeof section === 'object' ? section.text : '') || '';
    
    // 오디오 URL 추출 (문자열 또는 객체 형식 모두 지원)
    const audioUrl = typeof section === 'string' ? section : section.audio_url;
    
    // 오디오 URL이 있으면 오디오 재생
    if (audioUrl) {
        playAudioSection(audioUrl, text);
    } else {
        // TTS로 재생
        speakText(text);
    }
}

/**
 * 오디오 섹션 재생
 */
function playAudioSection(audioUrl, fallbackText) {
    const audio = new Audio(audioUrl);
    audio.playbackRate = state.tts.speed || 1.0;
    state.tts.currentAudio = audio;
    
    audio.play().catch(err => {
        console.warn('[learning_interface.js:playAudioSection] 오디오 재생 실패:', err);
        speakText(fallbackText);
    });
    
    audio.onended = () => {
        state.tts.isPlaying = false;
        onSectionComplete();
    };
}

/**
 * TTS로 텍스트 읽기
 */
function speakText(text) {
    if (!text || !('speechSynthesis' in window)) return;
    
    speechSynthesis.cancel();
    
    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = 'ko-KR';
    utterance.rate = state.tts.speed || 1.0;
    
    utterance.onend = () => {
        state.tts.isPlaying = false;
        onSectionComplete();
    };
    
    speechSynthesis.speak(utterance);
}

/**
 * 섹션 완료 시 처리
 */
function onSectionComplete() {
    state.tts.isPlaying = false;
    renderStepDots();
    updatePlayPauseButton(false);
    
    // 자동재생이면 다음 섹션으로
    if (state.tts.autoPlay) {
        const nextIndex = state.tts.currentSectionIndex + 1;
        if (nextIndex < state.tts.sections.length) {
            setTimeout(() => playTtsSection(nextIndex), 500);
        } else {
            // 🔥 모든 TTS 완료 → 장기기억화 단계로 이동
            showFeedback('🎉 모든 단계 설명이 완료되었습니다! 장기기억화로 이동합니다.');
            updatePlayPauseButton(false);
            
            // 점검 단계(4)를 완료하고 장기기억화(5)로 이동
            setTimeout(() => {
                handleStepClick(5);
            }, 1000);
        }
    }
}

/**
 * 현재 재생 중지
 */
function stopCurrentTts() {
    if (state.tts.currentAudio) {
        state.tts.currentAudio.pause();
        state.tts.currentAudio = null;
    }
    if ('speechSynthesis' in window) {
        speechSynthesis.cancel();
    }
    state.tts.isPlaying = false;
}

/**
 * 네비게이션 버튼 상태 업데이트
 */
function updateNavButtons() {
    const prevBtn = document.getElementById('ttsPrevBtn');
    const nextBtn = document.getElementById('ttsNextBtn');
    const sections = state.tts.sections || [];
    const index = state.tts.currentSectionIndex;
    
    if (prevBtn) prevBtn.disabled = index <= 0;
    if (nextBtn) nextBtn.disabled = index >= sections.length - 1;
}

/**
 * 이전 섹션
 */
function ttsPrevSection() {
    const index = state.tts.currentSectionIndex;
    if (index > 0) {
        playTtsSection(index - 1);
    }
}

/**
 * 다음 섹션
 */
function ttsNextSection() {
    const sections = state.tts.sections || [];
    const index = state.tts.currentSectionIndex;
    if (index < sections.length - 1) {
        playTtsSection(index + 1);
    }
}

/**
 * 재생/일시정지 토글
 */
function toggleTtsPlayPause() {
    if (state.tts.isPlaying) {
        // 일시정지
        stopCurrentTts();
        updatePlayPauseButton(false);
    } else {
        // 재생
        const sections = state.tts.sections || [];
        if (sections.length > 0) {
            playTtsSection(state.tts.currentSectionIndex || 0);
        }
    }
}

/**
 * 재생/일시정지 버튼 상태 업데이트
 */
function updatePlayPauseButton(isPlaying) {
    const btn = document.getElementById('ttsPlayPauseBtn');
    const icon = document.getElementById('ttsPlayIcon');
    
    if (btn) {
        btn.classList.toggle('playing', isPlaying);
    }
    if (icon) {
        icon.textContent = isPlaying ? '⏸' : '▶';
    }
}

/**
 * 현재 단계 표시 업데이트
 */
function updateCurrentStepDisplay() {
    const stepEl = document.getElementById('ttsCurrentStep');
    const sections = state.tts.sections || [];
    const current = (state.tts.currentSectionIndex || 0) + 1;
    const total = sections.length;
    
    if (stepEl) {
        stepEl.textContent = `${current}/${total}`;
    }
}

/**
 * 자동재생 토글
 */
function toggleTtsAutoPlay() {
    state.tts.autoPlay = !state.tts.autoPlay;
    const btn = document.getElementById('ttsAutoBtn');
    if (btn) {
        btn.classList.toggle('active', state.tts.autoPlay);
    }
}

/**
 * 재생 속도 순환
 */
function cycleTtsSpeed() {
    const speeds = [0.5, 0.75, 1.0, 1.25, 1.5, 1.75, 2.0];
    const currentIndex = speeds.indexOf(state.tts.speed || 1.0);
    const nextIndex = (currentIndex + 1) % speeds.length;
    state.tts.speed = speeds[nextIndex];
    
    const label = document.getElementById('ttsSpeedLabel');
    if (label) label.textContent = state.tts.speed + 'x';
    
    // 현재 재생 중인 오디오 속도 변경
    if (state.tts.currentAudio) {
        state.tts.currentAudio.playbackRate = state.tts.speed;
    }
}

/**
 * TTS 스텝 모달 열기
 */
function openTtsStepModal() {
    if (typeof StepPlayer !== 'undefined' && state.tts.interactionId) {
        StepPlayer.open(state.tts.interactionId);
    } else {
        console.warn('[learning_interface.js:openTtsStepModal] StepPlayer 없거나 interactionId 없음');
        showFeedback('먼저 TTS를 생성해주세요.');
    }
}

// ========== 기존 TTS 함수 (호환성 유지) ==========

/**
 * 헤더 TTS 재생/일시정지 토글 (레거시)
 */
function toggleHeaderTts() {
    if (state.tts.isPlaying) {
        stopCurrentTts();
    } else if (state.tts.sections && state.tts.sections.length > 0) {
        playTtsSection(state.tts.currentSectionIndex);
    }
}

/**
 * 현재 TTS 섹션 재생 (레거시)
 */
function playCurrentTtsSection() {
    playTtsSection(state.tts.currentSectionIndex);
}

// ========== Realtime 음성 튜터 ==========
let realtimeTutorActive = false;

/**
 * Realtime 음성 튜터 토글
 */
async function toggleRealtimeTutor() {
    const btn = document.getElementById('realtimeTutorBtn');
    const btnText = document.getElementById('realtimeTutorBtnText');
    const spinner = document.getElementById('realtimeTutorSpinner');
    
    if (!realtimeTutorActive) {
        // 시작
        try {
            btn.disabled = true;
            spinner.classList.remove('hidden');
            btnText.textContent = '연결 중...';
            
            // 현재 상태 가져오기
            const currentStep = state.steps.find(s => s.status === 'current');
            const currentStepId = currentStep ? currentStep.id : 1;
            
            // 이미지 URL 가져오기 (learning_interface.php에서 설정됨)
            const questionImage = window.QUESTION_IMAGE || null;
            const solutionImage = window.SOLUTION_IMAGE || null;
            
            await window.startRealtimeTutor({
                studentId: window.STUDENT_ID,
                contentId: window.CONTENT_ID,
                unitName: window.ANALYSIS_DATA?.dialogue_analysis?.unit?.korean || '수학',
                questionImage: questionImage,
                solutionImage: solutionImage,
                currentStep: currentStepId,
                currentEmotion: state.emotion.type || 'neutral'
            });
            
            realtimeTutorActive = true;
            btn.classList.add('active');
            btnText.textContent = '음성 튜터 종료';
            
            // 사이드바 채팅 자동 열기 (선택사항)
            if (typeof toggleSidebarChat !== 'undefined' && !SidebarChatInterface.isActive) {
                toggleSidebarChat();
            }
            
            // 이벤트 리스너 설정
            setupRealtimeTutorListeners();
            
        } catch (error) {
            console.error('Realtime 튜터 시작 실패:', error);
            alert('음성 튜터를 시작할 수 없습니다: ' + error.message);
            btnText.textContent = '음성 튜터';
        } finally {
            btn.disabled = false;
            spinner.classList.add('hidden');
        }
    } else {
        // 종료
        try {
            window.stopRealtimeTutor();
            realtimeTutorActive = false;
            btn.classList.remove('active');
            btnText.textContent = '음성 튜터';
        } catch (error) {
            console.error('Realtime 튜터 종료 실패:', error);
        }
    }
}

/**
 * Realtime 튜터 이벤트 리스너 설정
 */
function setupRealtimeTutorListeners() {
    // 연결 성공
    document.addEventListener('realtime-tutor-connected', () => {
        console.log('[learning_interface.js] Realtime 튜터 연결됨');
        showFeedback('🎤 음성 튜터가 연결되었어요!');
    });
    
    // 연결 끊김
    document.addEventListener('realtime-tutor-dataChannelClose', () => {
        console.log('[learning_interface.js] Realtime 튜터 연결 끊김');
        if (realtimeTutorActive) {
            showFeedback('⚠️ 연결이 끊어졌어요. 재연결을 시도합니다...');
        }
    });
    
    // 오류 발생
    document.addEventListener('realtime-tutor-error', (e) => {
        console.error('[learning_interface.js] Realtime 튜터 오류:', e.detail);
        showFeedback('❌ 오류가 발생했어요: ' + (e.detail.error || '알 수 없는 오류'));
    });
    
    // 세션 타임아웃
    document.addEventListener('realtime-tutor-timeout', () => {
        console.log('[learning_interface.js] Realtime 튜터 세션 타임아웃');
        showFeedback('⏰ 세션 시간이 만료되었어요. 다시 시작해주세요.');
        if (realtimeTutorActive) {
            toggleRealtimeTutor(); // 자동 종료
        }
    });
    
    // 세션 종료
    document.addEventListener('realtime-tutor-stopped', () => {
        console.log('[learning_interface.js] Realtime 튜터 종료됨');
        realtimeTutorActive = false;
        const btn = document.getElementById('realtimeTutorBtn');
        const btnText = document.getElementById('realtimeTutorBtnText');
        if (btn) {
            btn.classList.remove('active');
        }
        if (btnText) {
            btnText.textContent = '음성 튜터';
        }
    });
    
    // 메시지 수신
    document.addEventListener('realtime-tutor-message', (e) => {
        console.log('[learning_interface.js] Realtime 튜터 메시지:', e.detail.text);
        // 메시지는 이미 SidebarChatInterface에서 처리됨
    });
}

