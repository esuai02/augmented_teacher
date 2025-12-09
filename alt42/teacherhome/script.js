/**
 * 교육 AI 시스템 - 메인 JavaScript 파일
 * 작성일: 2024-07-09
 * 설명: 온보딩/메뉴/채팅 탭을 분리한 교육 AI 시스템
 */

// ==================== 모듈 통합 관리 ====================
let currentCategory = null;
let currentMode = 'menu';
let currentTab = null;
let currentItem = null;
let currentStep = 'welcome';
let actionInProgress = false;
let progressInterval = null;

// 인지관성 60개 패턴 데이터
const biasPatterns = [
  { id: 1, name: "아이디어 해방 자동발화형", desc: "번쩍이는 아이디어가 떠오르면 검증 없이 바로 써 내려가 결국 오답을 양산하는 패턴.", category: "인지 과부하", icon: "🧠", priority: "high", audioTime: "2:15" },
  { id: 2, name: "3초 패배 예감형", desc: "'못 풀 것 같다'는 느낌이 3초 만에 뇌를 잠그고, 관련 개념 연결이 끊어지는 패턴.", category: "자신감 왜곡", icon: "😰", priority: "high", audioTime: "1:45" },
  { id: 3, name: "과신-시야 협착형", desc: "과한 자신감으로 숫자·기호의 미세한 차이를 인식하지 못하는 패턴.", category: "자신감 왜곡", icon: "🎯", priority: "medium", audioTime: "2:30" },
  { id: 4, name: "무의식 연쇄 실수형", desc: "손이 먼저 움직여 사소한 계산 실수가 꼬리를 무는 패턴.", category: "실수 패턴", icon: "⚡", priority: "high", audioTime: "1:55" },
  { id: 5, name: "모순 확신-답불가형", desc: "'틀린 곳이 없다'는 집착으로 시야가 좁아져 교정을 못 하는 패턴.", category: "자신감 왜곡", icon: "🔒", priority: "medium", audioTime: "2:10" },
  { id: 6, name: "작업기억 ⅔ 할당형", desc: "다음 일정·잡생각이 머릿속을 스치며 2/3만 집중하는 패턴.", category: "인지 과부하", icon: "🧩", priority: "high", audioTime: "2:25" },
  { id: 7, name: "반(半)포기 창의 탐색형", desc: "'어차피 틀릴 것'이라며 낮은 확률의 창의 풀이만 헤매는 패턴.", category: "접근 전략 오류", icon: "🎨", priority: "medium", audioTime: "2:40" },
  { id: 8, name: "해설지-혼합 착각형", desc: "내 생각과 해설 내용을 섞어 쓰다 근거가 뒤섞이는 패턴.", category: "학습 습관", icon: "📖", priority: "medium", audioTime: "2:05" },
  { id: 9, name: "연습 회피 관성형", desc: "'이해했어' 착각으로 반복 연습을 건너뛰고 넘어가는 패턴.", category: "학습 습관", icon: "🏃", priority: "high", audioTime: "1:35" },
  { id: 10, name: "불확실 강행형", desc: "근거 부족인데도 '일단 적용'해서 오류가 연쇄되는 패턴.", category: "접근 전략 오류", icon: "🎲", priority: "medium", audioTime: "2:20" },
  { id: 11, name: "속도 압박 억제형", desc: "시험 시간이 눈에 들어올 때마다 '빨리 해야 한다'는 압박이 새 아이디어와 기억을 눌러 버리는 패턴.", category: "시간/압박 관리", icon: "⏰", priority: "high", audioTime: "1:50" },
  { id: 12, name: "시험 트라우마 악수형", desc: "과거에 시험을 망친 기억이 문제 순서·전략에 투영돼 '악수'를 두는 패턴.", category: "시간/압박 관리", icon: "💔", priority: "high", audioTime: "2:35" },
  { id: 13, name: "징검다리 난도적형", desc: "청킹 없이 산발적으로 추론해 전역 구조를 놓치는 패턴.", category: "접근 전략 오류", icon: "🪨", priority: "medium", audioTime: "2:45" },
  { id: 14, name: "무의식 재현 루프형", desc: "예전에 성공했던 공식을 맹목적으로 재사용하며 문제 특성을 무시하는 패턴.", category: "학습 습관", icon: "🔄", priority: "low", audioTime: "2:15" },
  { id: 15, name: "조건 회피-추론 생략형", desc: "복잡한 조건을 '시야 밖'으로 밀어두고 직감만으로 추론을 강행하는 패턴.", category: "검증/확인 부재", icon: "👁️", priority: "high", audioTime: "1:40" },
  { id: 16, name: "확률적 답안 던지기형", desc: "근거가 부족한데도 '일단 찍어보자' 식으로 답을 기입해 오류가 연쇄되는 패턴.", category: "접근 전략 오류", icon: "🎯", priority: "medium", audioTime: "1:55" },
  { id: 17, name: "유사 문제 환상형", desc: "비슷해 보이는 문제를 과거 풀이 그대로 복사하듯 접근하는 패턴.", category: "학습 습관", icon: "👻", priority: "medium", audioTime: "2:00" },
  { id: 18, name: "공식 의존 사슬형", desc: "공식 하나에 매달려 모든 문제를 해결하려는 패턴.", category: "접근 전략 오류", icon: "⛓️", priority: "high", audioTime: "2:15" },
  { id: 19, name: "계산 단계 생략형", desc: "암산으로 여러 단계를 한 번에 처리하려다 실수하는 패턴.", category: "실수 패턴", icon: "🔢", priority: "high", audioTime: "1:45" },
  { id: 20, name: "문제 지문 속독형", desc: "문제를 빨리 읽고 넘어가며 핵심 조건을 놓치는 패턴.", category: "검증/확인 부재", icon: "📄", priority: "high", audioTime: "2:00" },
  { id: 21, name: "부분 점수 포기형", desc: "완벽한 답이 아니면 아예 시도하지 않는 패턴.", category: "자신감 왜곡", icon: "🚫", priority: "medium", audioTime: "2:20" },
  { id: 22, name: "오답 노트 회피형", desc: "틀린 문제를 다시 보기 싫어하는 패턴.", category: "학습 습관", icon: "📓", priority: "high", audioTime: "1:55" },
  { id: 23, name: "개념 암기 우선형", desc: "이해보다 암기에 치중하는 패턴.", category: "학습 습관", icon: "🧠", priority: "medium", audioTime: "2:30" },
  { id: 24, name: "풀이 과정 축약형", desc: "풀이 과정을 머릿속으로만 처리하고 쓰지 않는 패턴.", category: "실수 패턴", icon: "✏️", priority: "high", audioTime: "1:40" },
  { id: 25, name: "복습 타이밍 놓침형", desc: "배운 내용을 제때 복습하지 않아 잊어버리는 패턴.", category: "학습 습관", icon: "📚", priority: "high", audioTime: "2:10" },
  { id: 26, name: "질문 회피형", desc: "모르는 것을 질문하기 부끄러워하는 패턴.", category: "학습 습관", icon: "🙊", priority: "medium", audioTime: "1:50" },
  { id: 27, name: "시간 배분 실패형", desc: "문제별 시간 배분을 잘못하는 패턴.", category: "시간/압박 관리", icon: "⏱️", priority: "high", audioTime: "2:25" },
  { id: 28, name: "그래프 해석 생략형", desc: "그래프나 도표를 대충 보고 넘어가는 패턴.", category: "검증/확인 부재", icon: "📊", priority: "medium", audioTime: "2:00" },
  { id: 29, name: "자기긍정 과열형", desc: "'나는 할 수 있다'는 과도한 자신감으로 준비를 소홀히 하는 패턴.", category: "자신감 왜곡", icon: "🔥", priority: "medium", audioTime: "2:15" },
  { id: 30, name: "패턴 인식 실패형", desc: "문제의 유형을 파악하지 못하는 패턴.", category: "접근 전략 오류", icon: "🧩", priority: "high", audioTime: "2:40" },
  { id: 31, name: "단위 무시형", desc: "단위 변환을 잊거나 무시하는 패턴.", category: "실수 패턴", icon: "📏", priority: "high", audioTime: "1:35" },
  { id: 32, name: "예외 케이스 무시형", desc: "특수한 경우를 고려하지 않는 패턴.", category: "검증/확인 부재", icon: "⚠️", priority: "medium", audioTime: "2:20" },
  { id: 33, name: "논리 비약형", desc: "중간 과정을 건너뛰고 결론으로 직행하는 패턴.", category: "접근 전략 오류", icon: "🦘", priority: "high", audioTime: "2:05" },
  { id: 34, name: "역산 회피형", desc: "답을 구한 후 검산하지 않는 패턴.", category: "검증/확인 부재", icon: "🔄", priority: "high", audioTime: "1:45" },
  { id: 35, name: "조건 과잉 해석형", desc: "주어진 조건을 지나치게 복잡하게 해석하는 패턴.", category: "접근 전략 오류", icon: "🔍", priority: "medium", audioTime: "2:30" },
  { id: 36, name: "멀티태스킹 강박형", desc: "여러 문제를 동시에 풀려고 하는 패턴.", category: "인지 과부하", icon: "🎯", priority: "high", audioTime: "2:10" },
  { id: 37, name: "환경 의존형", desc: "특정 환경에서만 공부가 되는 패턴.", category: "학습 습관", icon: "🏠", priority: "low", audioTime: "1:55" },
  { id: 38, name: "반복 학습 기피형", desc: "같은 유형 문제를 반복하기 싫어하는 패턴.", category: "학습 습관", icon: "🔁", priority: "medium", audioTime: "2:00" },
  { id: 39, name: "피드백 무시형", desc: "선생님이나 동료의 조언을 듣지 않는 패턴.", category: "학습 습관", icon: "👂", priority: "high", audioTime: "2:15" },
  { id: 40, name: "목표 설정 회피형", desc: "구체적인 학습 목표를 세우지 않는 패턴.", category: "학습 습관", icon: "🎯", priority: "medium", audioTime: "1:40" },
  { id: 41, name: "스트레스 회피형", desc: "어려운 문제를 만나면 즉시 포기하는 패턴.", category: "자신감 왜곡", icon: "😩", priority: "high", audioTime: "2:25" },
  { id: 42, name: "정답 확인 중독형", desc: "문제를 풀자마자 답을 확인하려는 패턴.", category: "학습 습관", icon: "✅", priority: "medium", audioTime: "1:50" },
  { id: 43, name: "노트 정리 과다형", desc: "노트 정리에만 시간을 쏟는 패턴.", category: "학습 습관", icon: "📝", priority: "low", audioTime: "2:35" },
  { id: 44, name: "암기 카드 의존형", desc: "이해 없이 암기 카드만 사용하는 패턴.", category: "학습 습관", icon: "🃏", priority: "medium", audioTime: "2:00" },
  { id: 45, name: "난이도 회피형", desc: "자신의 수준보다 쉬운 문제만 푸는 패턴.", category: "학습 습관", icon: "📉", priority: "high", audioTime: "2:20" },
  { id: 46, name: "설명 능력 부족형", desc: "알고 있어도 설명하지 못하는 패턴.", category: "학습 습관", icon: "💬", priority: "medium", audioTime: "2:10" },
  { id: 47, name: "반례 무시형", desc: "자신의 풀이에 대한 반례를 생각하지 않는 패턴.", category: "검증/확인 부재", icon: "❌", priority: "high", audioTime: "1:55" },
  { id: 48, name: "조급증 과다형", desc: "빨리 끝내려고 서두르는 패턴.", category: "시간/압박 관리", icon: "🏃", priority: "high", audioTime: "1:45" },
  { id: 49, name: "선입견 고착형", desc: "처음 든 생각을 바꾸지 못하는 패턴.", category: "접근 전략 오류", icon: "🔒", priority: "medium", audioTime: "2:30" },
  { id: 50, name: "메타인지 부족형", desc: "자신이 뭘 모르는지 모르는 패턴.", category: "기타 장애", icon: "❓", priority: "high", audioTime: "2:15" },
  { id: 51, name: "동기 부여 상실형", desc: "목적 없이 공부하는 패턴.", category: "기타 장애", icon: "😔", priority: "high", audioTime: "2:00" },
  { id: 52, name: "검산 회피형", desc: "계산 후 검토를 하지 않는 패턴.", category: "검증/확인 부재", icon: "🔍", priority: "high", audioTime: "1:40" },
  { id: 53, name: "풀이 방법 고집형", desc: "한 가지 방법만 고집하는 패턴.", category: "접근 전략 오류", icon: "🔨", priority: "medium", audioTime: "2:25" },
  { id: 54, name: "기초 개념 누락형", desc: "기초가 부족한 상태로 심화 학습하는 패턴.", category: "학습 습관", icon: "🏗️", priority: "high", audioTime: "2:35" },
  { id: 55, name: "실전 연습 부족형", desc: "이론만 공부하고 실전 연습을 안 하는 패턴.", category: "학습 습관", icon: "⚔️", priority: "high", audioTime: "2:10" },
  { id: 56, name: "협동 학습 거부형", desc: "혼자서만 공부하려는 패턴.", category: "학습 습관", icon: "👤", priority: "low", audioTime: "1:50" },
  { id: 57, name: "체계 없는 학습형", desc: "계획 없이 무작정 공부하는 패턴.", category: "학습 습관", icon: "🌀", priority: "high", audioTime: "2:20" },
  { id: 58, name: "완벽주의 함정형", desc: "100% 이해하지 못하면 넘어가지 못하는 패턴.", category: "기타 장애", icon: "💎", priority: "medium", audioTime: "2:30" },
  { id: 59, name: "주의력 분산형", desc: "집중력이 금방 흐트러지는 패턴.", category: "인지 과부하", icon: "🎪", priority: "high", audioTime: "1:55" },
  { id: 60, name: "자기평가 누적 오류형", desc: "진행 중 정확도 추정이 계속 어긋나 자기효능감이 왜곡되는 패턴.", category: "기타 장애", icon: "📊", priority: "medium", audioTime: "2:00" }
];

// 카테고리별 색상 매핑
const biasCategoryColors = {
  "인지 과부하": "#FF6B6B",
  "자신감 왜곡": "#4ECDC4",
  "실수 패턴": "#45B7D1",
  "접근 전략 오류": "#96CEB4",
  "학습 습관": "#FECA57",
  "검증/확인 부재": "#9B59B6",
  "시간/압박 관리": "#F39C12",
  "기타 장애": "#95A5A6"
};

// 에이전트 플러그인 유형만 정의
const pluginTypes = [
    { id: 'agent', title: '에이전트', icon: '🤖', description: '팝업창에서 멀티턴 작업 실행' }
];

// 기본 카드들을 가져오는 함수
function getDefaultCards() {
    console.log('getDefaultCards called');
    const menuStructure = getMenuStructure();
    console.log('menuStructure:', menuStructure);
    
    const categoryData = menuStructure[currentCategory];
    console.log('categoryData:', categoryData);
    
    const defaultCards = [];
    
    // 현재 화면에 이미 추가된 카드들의 제목 수집
    const existingCardTitles = new Set();
    if (window.ktmPluginClient && currentTab) {
        const tabTitle = typeof currentTab === 'object' ? currentTab.title : currentTab;
        const existingCards = window.ktmPluginClient.getCardSettings(currentCategory, tabTitle) || [];
        existingCards.forEach(card => {
            const config = card.plugin_config || {};
            const originalTitle = config.original_template_title || config.card_title || card.card_title;
            if (originalTitle) {
                existingCardTitles.add(originalTitle);
            }
        });
    }
    console.log('Already added cards:', Array.from(existingCardTitles));
    
    if (categoryData && currentTab) {
        let tab = null;
        
        // currentTab이 객체인 경우
        if (typeof currentTab === 'object' && currentTab.items) {
            console.log('currentTab is object with items');
            tab = currentTab;
        } 
        // currentTab이 ID인 경우
        else if (typeof currentTab === 'string' || (currentTab && currentTab.id)) {
            const tabId = typeof currentTab === 'string' ? currentTab : currentTab.id;
            console.log('Looking for tab with id:', tabId);
            tab = categoryData.tabs.find(t => t.id === tabId);
        }
        
        console.log('Found tab:', tab);
        
        if (tab && tab.items) {
            console.log('Tab items:', tab.items);
            tab.items.forEach(item => {
                // 이미 추가된 카드는 제외
                if (existingCardTitles.has(item.title)) {
                    console.log('Skipping already added card:', item.title);
                    return;
                }
                
                // 저장된 플러그인 타입 매핑 확인
                const mappingKey = `${currentCategory}_${currentTab.title || currentTab}_${item.title}`;
                let pluginType = 'default_card';
                let pluginConfig = {};
                
                if (window.defaultCardTypeMappings && window.defaultCardTypeMappings[mappingKey]) {
                    const mapping = window.defaultCardTypeMappings[mappingKey];
                    pluginType = mapping.pluginType;
                    pluginConfig = mapping.pluginConfig;
                }
                
                defaultCards.push({
                    title: item.title,
                    description: item.description,
                    details: item.details || [],
                    hasLink: item.hasLink || false,
                    link: item.link || '',
                    pluginType: pluginType,
                    pluginConfig: pluginConfig
                });
            });
        }
    }
    
    // 사용자가 저장한 커스텀 기본 카드 추가 (이미 추가되지 않은 것만)
    if (window.customDefaultCards && window.customDefaultCards.length > 0) {
        window.customDefaultCards.forEach(customCard => {
            if (!existingCardTitles.has(customCard.card_title)) {
                defaultCards.push({
                    title: customCard.card_title,
                    description: customCard.description,
                    details: customCard.details || [],
                    hasLink: !!customCard.url,
                    link: customCard.url || ''
                });
            }
        });
    }
    
    console.log('Returning defaultCards (excluding already added):', defaultCards);
    return defaultCards;
}

// 사용자 선택 플러그인 카드 저장
let userSelectedPlugins = [];

// 카드별 플러그인 설정 저장 (카드 제목을 키로 사용)
let cardPluginSettings = {};

// 삭제된 기본 카드 추적 (카테고리_탭_제목 형식으로 저장)
let deletedDefaultCards = new Set();

// 모듈별 데이터를 통합하여 메뉴 구조 생성
function getMenuStructure() {
    return {
        quarterly: window.quarterlyModule ? window.quarterlyModule.getData() : null,
        weekly: window.weeklyModule ? window.weeklyModule.getData() : null,
        daily: window.dailyModule ? window.dailyModule.getData() : null,
        realtime: window.realtimeModule ? window.realtimeModule.getData() : null,
        interaction: window.interactionModule ? window.interactionModule.getData() : null,
        bias: window.biasModule ? window.biasModule.getData() : null,
        development: window.developmentModule ? window.developmentModule.getData() : null,
        viral: getViralMarketingData(),
        consultation: getConsultationData()
    };
}

// ==================== 상태 관리 ====================
const agents = {
    quarterly: { name: '분기 관리자', role: '장기 계획 및 목표 관리', avatar: '📅', status: 'online' },
    weekly: { name: '주간 관리자', role: '주간 활동 및 진도 관리', avatar: '📝', status: 'online' },
    daily: { name: '일일 관리자', role: '오늘의 활동 및 목표 관리', avatar: '⏰', status: 'online' },
    realtime: { name: '실시간 관리자', role: '즉시 모니터링 및 대응', avatar: '📊', status: 'online' },
    interaction: { name: '상호작용 관리자', role: '소통 및 피드백 관리', avatar: '💬', status: 'online' },
    bias: { name: '인지관성 개선 관리자', role: '수학 학습 인지관성 개선 및 연쇄상호작용 관리', avatar: '🧠', status: 'online' },
    development: { name: '개발 관리자', role: '컨텐츠 및 앱 개발', avatar: '🛠️', status: 'online' },
    viral: { name: '바이럴 마케팅 매니저', role: '바이럴 콘텐츠 제작 및 소셜미디어 마케팅', avatar: '💰', status: 'online' },
    consultation: { name: '상담 관리자', role: '학생 상담 및 학부모 소통 관리', avatar: '🤝', status: 'online' }
};

// ==================== UI 업데이트 함수 ====================
function updateCurrentAgent(category) {
    const agent = agents[category];
    if (!agent) return;

    document.getElementById('currentAgentAvatar').textContent = agent.avatar;
    document.getElementById('currentAgentName').textContent = agent.name;
    document.getElementById('currentAgentRole').textContent = agent.role;
}

function updateCategoryStatus(category, status) {
    const statusElement = document.getElementById(`${category}-status`);
    if (statusElement) {
        statusElement.textContent = status === 'active' ? '🟢' : '●';
    }
}

// 온보딩 카드를 메뉴에 자동으로 추가하는 함수
async function autoAddOnboardingCardsToMenu() {
    if (!window.ktmPluginClient) {
        console.log('플러그인 시스템이 초기화되지 않아 온보딩 카드 추가를 건너뜁니다.');
        return;
    }
    
    const menuStructure = getMenuStructure();
    
    // 모든 카테고리를 순회하며 온보딩 카드 추가
    for (const [categoryKey, categoryData] of Object.entries(menuStructure)) {
        if (!categoryData || !categoryData.tabs) continue;
        
        for (const tab of categoryData.tabs) {
            if (!tab.items) continue;
            
            for (const item of tab.items) {
                try {
                    // 이미 추가된 카드인지 확인
                    await window.ktmPluginClient.loadCardSettings(categoryKey, tab.title);
                    const existingCards = window.ktmPluginClient.getCardSettings(categoryKey, tab.title) || [];
                    const alreadyExists = existingCards.some(card => {
                        const config = card.plugin_config || {};
                        return config.plugin_name === item.title || 
                               (config.agent_config && config.agent_config.title === item.title);
                    });
                    
                    if (alreadyExists) {
                        console.log(`카드 "${item.title}"은(는) 이미 ${categoryKey}/${tab.title}에 존재합니다.`);
                        continue;
                    }
                    
                    // 에이전트 플러그인 설정 생성
                    const config = {
                        plugin_name: item.title,
                        agent_type: 'onboarding_item',
                        agent_config: {
                            title: item.title,
                            description: item.description,
                            details: item.details || [],
                            hasChainInteraction: item.hasChainInteraction || false,
                            category: categoryKey,
                            originalTab: tab.title
                        },
                        agent_prompt: `당신은 "${item.title}" 기능을 담당하는 전문 AI 에이전트입니다. 
${item.description}
주요 기능: ${item.details ? item.details.join(', ') : ''}
사용자가 이 기능에 대해 문의하면 친절하고 전문적으로 안내해주세요.`
                    };
                    
                    // 카드 설정 저장
                    await window.ktmPluginClient.saveCardSetting(
                        categoryKey,
                        tab.title,
                        0, // card index
                        'agent', // plugin type
                        config,
                        999 // display order (마지막에 추가)
                    );
                    
                    console.log(`카드 "${item.title}"을(를) ${categoryKey}/${tab.title}에 추가했습니다.`);
                } catch (error) {
                    console.error(`카드 "${item.title}" 추가 실패:`, error);
                }
            }
        }
    }
    
    console.log('모든 온보딩 카드를 메뉴에 추가했습니다.');
}

// ==================== 카테고리 선택 ====================
function selectCategory(category) {
    // 초기 iframe 숨기기
    const initialIframe = document.getElementById('initialIframeContainer');
    if (initialIframe) {
        initialIframe.style.display = 'none';
    }
    
    // UI 요소들 표시
    document.querySelector('.content-header').style.display = 'flex';
    document.querySelector('.input-area').style.display = 'flex';
    document.getElementById('guideMessage').style.display = 'block';
    
    // 온보딩 모드인 경우 채팅 영역 표시
    if (currentMode === 'onboarding') {
        document.getElementById('chatArea').style.display = 'block';
        document.getElementById('menuTabContainer').style.display = 'none';
    } else if (currentMode === 'menu') {
        document.getElementById('chatArea').style.display = 'none';
        document.getElementById('menuTabContainer').style.display = 'block';
    }
    
    // 이전 선택 해제
    if (currentCategory) {
        updateCategoryStatus(currentCategory, 'inactive');
        const prevElement = document.querySelector(`[data-category="${currentCategory}"]`);
        if (prevElement) prevElement.classList.remove('active');
    }

    // 새 카테고리 선택
    currentCategory = category;
    updateCategoryStatus(category, 'active');
    const categoryElement = document.querySelector(`[data-category="${category}"]`);
    if (categoryElement) categoryElement.classList.add('active');

    // 에이전트 정보 업데이트
    updateCurrentAgent(category);

    // 현재 선택된 모드에 따라 내용 업데이트
    if (currentMode === 'onboarding') {
        startCategoryOnboarding(category);
    } else if (currentMode === 'menu') {
        showMenuInterface(category);
    } else if (currentMode === 'chat') {
        showChatInterface();
    }

    // 모듈별 초기화 함수 호출
    initializeModule(category);
}

// ==================== 모듈 초기화 ====================
function initializeModule(category) {
    switch(category) {
        case 'quarterly':
            if (window.quarterlyModule) {
                console.log('분기활동 모듈 초기화');
            }
            break;
        case 'weekly':
            if (window.weeklyModule) {
                console.log('주간활동 모듈 초기화');
            }
            break;
        case 'daily':
            if (window.dailyModule) {
                console.log('오늘활동 모듈 초기화');
            }
            break;
        case 'realtime':
            if (window.realtimeModule) {
                window.realtimeModule.startMonitoring();
            }
            break;
        case 'interaction':
            if (window.interactionModule) {
                window.interactionModule.startConversation();
            }
            break;
        case 'bias':
            if (window.biasModule) {
                window.biasModule.detectBias();
            }
            break;
        case 'development':
            if (window.developmentModule) {
                console.log('개발 모듈 초기화');
            }
            break;
        case 'branding':
            console.log('퍼스널 브랜딩 모듈 초기화');
            break;
    }
}

// ==================== 모드 전환 ====================
function switchMode(mode) {
    // 채팅 모드는 현재 화면을 유지하면서 패널만 열기
    if (mode === 'chat') {
        openChatPanel();
        return; // 다른 UI 변경 없이 종료
    }
    
    currentMode = mode;
    
    // 초기 iframe 숨기기 (카테고리가 선택된 경우에만)
    if (currentCategory) {
        const initialIframe = document.getElementById('initialIframeContainer');
        if (initialIframe) {
            initialIframe.style.display = 'none';
        }
    }
    
    // 모든 모드 버튼 비활성화
    document.querySelectorAll('.mode-button').forEach(btn => btn.classList.remove('active'));
    
    // 선택된 모드 버튼 활성화
    const modeButton = document.querySelector(`[onclick="switchMode('${mode}')"]`);
    if (modeButton) modeButton.classList.add('active');
    
    // UI 요소 표시/숨김
    const menuTabContainer = document.getElementById('menuTabContainer');
    const chatArea = document.getElementById('chatArea');
    const guideMessage = document.getElementById('guideMessage');
    const inputArea = document.querySelector('.input-area');
    
    menuTabContainer.style.display = 'none';
    chatArea.style.display = 'none';
    guideMessage.style.display = 'none';
    
    // 입력 영역 기본 표시
    if (inputArea) {
        inputArea.style.display = 'flex';
    }
    
    if (mode === 'onboarding') {
        chatArea.style.display = 'block';
        guideMessage.style.display = 'block';
        
        if (currentCategory) {
            startCategoryOnboarding(currentCategory);
        } else {
            showWelcomeMessage();
        }
    } else if (mode === 'menu') {
        menuTabContainer.style.display = 'block';
        
        // 메뉴 모드에서는 입력 영역 숨기기
        if (inputArea) {
            inputArea.style.display = 'none';
        }
        
        if (currentCategory) {
            showMenuInterface(currentCategory);
        } else {
            showMenuWelcome();
        }
    }
}

// ==================== 온보딩 모드 ====================
function startCategoryOnboarding(category) {
    const menuStructure = getMenuStructure();
    const categoryData = menuStructure[category];
    
    if (!categoryData) {
        console.error(`카테고리 데이터를 찾을 수 없습니다: ${category}`);
        return;
    }

    // 채팅 초기화
    clearChat();
    
    // 인지관성 개선 카테고리에 특화된 온보딩
    if (category === 'bias') {
        startMathCognitionOnboarding(categoryData);
    } else {
        // 기존 온보딩 방식
        startDefaultOnboarding(category, categoryData);
    }
}

// 수학 인지관성 개선 전용 온보딩
function startMathCognitionOnboarding(categoryData) {
    // 인사 메시지
    setTimeout(() => {
        addMessage('ai', `안녕하세요! 🧠 수학 학습 인지관성 개선 전문가입니다.`);
    }, 500);

    setTimeout(() => {
        addMessage('ai', `수학 공부하면서 이런 고민 해보셨나요? 🤔`);
    }, 1500);

    setTimeout(() => {
        addMessage('ai', `"왜 같은 유형 문제인데 자꾸 틀리지?" "개념은 아는데 문제만 보면 막막해..." "시간이 부족해서 마지막 문제까지 못 풀었어..."`);
    }, 2500);

    setTimeout(() => {
        addMessage('ai', `이런 문제들은 사실 개별 학생의 '인지관성' 패턴과 관련이 있어요. 📊`);
    }, 4000);

    setTimeout(() => {
        addMessage('ai', `저는 각 학생의 수학 학습 패턴을 분석하고, 비슷한 어려움을 겪는 다른 학생들과 연결해서 함께 해결해나가는 '연쇄상호작용' 시스템을 운영합니다! ⛓️✨`);
    }, 5500);

    setTimeout(() => {
        addMessage('ai', `예를 들어, 한 학생이 '포모도르 기법'으로 집중력을 개선했다면, 비슷한 집중력 문제를 가진 다른 학생들에게도 자동으로 맞춤 솔루션을 제공해드려요.`);
    }, 7000);

    setTimeout(() => {
        addMessage('ai', `어떤 영역부터 시작해보시겠어요? 각 영역별로 맞춤형 솔루션과 연쇄상호작용을 체험해보실 수 있습니다! 🚀`);
        showSecondaryMenuCards(categoryData);
    }, 8500);
}

// 기본 온보딩 방식
function startDefaultOnboarding(category, categoryData) {
    // 인사 메시지
    setTimeout(() => {
        const agent = agents[category];
        addMessage('ai', `안녕하세요! ${agent.name}입니다. ${agent.role}를 담당하고 있습니다.`);
    }, 500);

    // 메뉴 설명
    setTimeout(() => {
        addMessage('ai', `${categoryData.title}에 대해 소개해드리겠습니다.`);
    }, 1500);

    setTimeout(() => {
        addMessage('ai', categoryData.description);
    }, 2500);

    // 2차 메뉴 선택 카드 표시
    setTimeout(() => {
        addMessage('ai', '어떤 기능을 자세히 살펴보시겠습니까?');
        showSecondaryMenuCards(categoryData);
    }, 3500);

    currentStep = 'secondary_menu_selection';
}

function showWelcomeMessage() {
    clearChat();
    
    setTimeout(() => {
        addMessage('ai', '안녕하세요! KTM 코파일럿에 오신 것을 환영합니다! 🎉');
    }, 500);
    
    setTimeout(() => {
        addMessage('ai', '저는 여러분의 학습을 도와드리는 AI 어시스턴트입니다.');
    }, 1500);
    
    setTimeout(() => {
        addMessage('ai', '좌측 메뉴에서 원하는 기능을 선택하시면, 해당 분야의 전문 관리자가 자세히 안내해드릴게요!');
    }, 2500);
}

// ==================== 온보딩 카드 기능들 ====================
// 2차 메뉴 카드 표시 (탭 선택)
function showSecondaryMenuCards(categoryData) {
    const chatContainer = document.getElementById('chatContainer');
    const cardContainer = document.createElement('div');
    cardContainer.className = 'chat-selection-cards';
    
    // 탭 카드들
    categoryData.tabs.forEach(tab => {
        const card = document.createElement('div');
        card.className = 'chat-card';
        card.onclick = () => selectTabFromOnboarding(tab);
        card.innerHTML = `
            <div class="chat-card-header">
                <h4>${tab.title}</h4>
            </div>
            <div class="chat-card-body">
                <p>${tab.description}</p>
                <div class="chat-card-count">${tab.items.length}개 세부 기능</div>
            </div>
        `;
        cardContainer.appendChild(card);
    });

    chatContainer.appendChild(cardContainer);
    
    // 이전 메뉴 버튼 추가
    addBackButton('처음으로 돌아가기', () => {
        clearChat();
        showWelcomeMessage();
    });
    
    chatContainer.scrollTop = chatContainer.scrollHeight;
}

function selectTabFromOnboarding(tab) {
    currentTab = tab;
    currentStep = 'tab_selected';
    
    // 선택 카드 제거
    const cards = document.querySelectorAll('.chat-selection-cards');
    cards.forEach(card => card.remove());
    
    // 사용자 선택 메시지 추가
    addMessage('user', `${tab.title}을 선택했습니다.`);
    
    // 인지관성 개선 카테고리의 경우 수학 특화 메시지
    if (currentCategory === 'bias') {
        showMathSpecificTabIntro(tab);
    } else {
        // 기존 방식
        showDefaultTabIntro(tab);
    }
}

// 수학 특화 탭 소개
function showMathSpecificTabIntro(tab) {
    const mathMessages = {
        'concept_study': {
            intro: `좋은 선택이에요! 개념공부는 수학의 기초 체력과 같아요. 💪`,
            context: `많은 학생들이 "개념은 알겠는데 문제가 안 풀려요"라고 하는데, 실제로는 개념을 '아는 것'과 '활용할 수 있는 것' 사이에 큰 차이가 있거든요.`,
            solution: `여기서는 포모도르 기법부터 AI 음성대화까지, 개념을 정말 '내 것'으로 만드는 다양한 방법들을 제공합니다!`
        },
        'problem_solving': {
            intro: `문제풀이! 수학의 꽃이죠! 🌸 하지만 막상 문제를 보면... 어디서부터 시작해야 할지 모르겠죠?`,
            context: `"시작이 반이다"라는 말이 있듯이, 문제풀이도 시작을 어떻게 하느냐가 정말 중요해요. 그리고 과정에서의 점검, 마무리까지...`,
            solution: `체계적인 문제해결 전략과 함께, 비슷한 실수 패턴을 가진 친구들과의 연쇄학습으로 더 효과적으로 개선해나갈 수 있어요!`
        },
        'learning_management': {
            intro: `학습관리! 이거 정말 중요한데 소홀히 하기 쉬운 부분이에요. 📚`,
            context: `"공부는 열심히 하는데 성적이 안 오르네..." 하는 친구들 대부분이 학습관리에서 놓치는 부분들이 있어요.`,
            solution: `내공부방 세팅부터 수학일기 작성까지, 체계적인 관리 시스템으로 공부의 효율을 확실히 높여보세요!`
        },
        'exam_preparation': {
            intro: `시험대비! 가장 스트레스 받지만 가장 중요한 순간이죠! 😤`,
            context: `"시험 기간만 되면 뭘 어떻게 공부해야 할지 모르겠어요..." 이런 고민, 정말 많이 들어봤어요.`,
            solution: `준비상태 진단부터 구간별 최적화, 최종 기억인출 전략까지! 체계적인 시험 대비로 실력 발휘 100% 해보세요!`
        },
        'practical_training': {
            intro: `실전연습! 진짜 실력을 보여주는 순간이에요! ⚡`,
            context: `"평소엔 잘 푸는데 시험만 보면 시간이 부족해요", "실수가 너무 많아요" - 이런 고민들, 모두 실전 경험 부족 때문이에요.`,
            solution: `시간관리부터 실수 조절까지! 실전에서 최고의 퍼포먼스를 낼 수 있도록 도와드릴게요!`
        },
        'attendance': {
            intro: `출결관리! 공부의 연속성을 지키는 중요한 열쇠에요! 🗝️`,
            context: `한 번 빠지면 따라잡기 어려운 수학... 하지만 어쩔 수 없이 빠지는 경우도 있죠.`,
            solution: `사전보강부터 전수보강까지, 학습의 연속성을 잃지 않도록 체계적으로 관리해드려요!`
        }
    };

    const message = mathMessages[tab.id] || {
        intro: `${tab.title}에 대해 자세히 설명드리겠습니다.`,
        context: tab.explanation,
        solution: `다양한 기능들을 통해 도움을 드릴게요!`
    };

    setTimeout(() => {
        addMessage('ai', message.intro);
    }, 500);
    
    setTimeout(() => {
        addMessage('ai', message.context);
    }, 2000);
    
    setTimeout(() => {
        addMessage('ai', message.solution);
    }, 3500);
    
    // 세부 기능 선택 카드 표시
    setTimeout(() => {
        addMessage('ai', '어떤 세부 기능으로 시작해보시겠어요? 🚀');
        showDetailMenuCards(tab.items);
    }, 5000);
}

// 기본 탭 소개
function showDefaultTabIntro(tab) {
    // AI 응답 - 하위 메뉴 소개
    setTimeout(() => {
        addMessage('ai', `${tab.title}에 대해 자세히 설명드리겠습니다.`);
    }, 500);
    
    setTimeout(() => {
        addMessage('ai', tab.explanation);
    }, 1500);
    
    // 세부 기능 선택 카드 표시
    setTimeout(() => {
        addMessage('ai', '어떤 세부 기능을 자세히 알아보시겠습니까?');
        showDetailMenuCards(tab.items);
    }, 2500);
}

// 세부 메뉴 카드 표시 (아이템 선택)
function showDetailMenuCards(items) {
    const chatContainer = document.getElementById('chatContainer');
    const cardContainer = document.createElement('div');
    cardContainer.className = 'chat-selection-cards';
    
    items.forEach(item => {
        const card = document.createElement('div');
        card.className = 'chat-card';
        card.innerHTML = `
            <div class="chat-card-header">
                <h4>${item.title}</h4>
            </div>
            <div class="chat-card-body">
                <p>${item.description}</p>
                <div class="chat-card-count">${item.details.length}개 세부 작업</div>
                <div class="chat-card-actions" style="margin-top: 10px; display: flex; gap: 10px;">
                    <button class="chat-card-btn" onclick='selectItemFromOnboarding(${JSON.stringify(item).replace(/'/g, "&#39;")})'>
                        체험하기
                    </button>
                    <button class="chat-card-btn add-to-menu-btn" 
                            onclick='addOnboardingCardToMenu(${JSON.stringify(item).replace(/'/g, "&#39;")}, "${currentCategory}", "${currentTab ? currentTab.title : ''}")'>
                        메뉴에 추가
                    </button>
                </div>
            </div>
        `;
        cardContainer.appendChild(card);
    });

    chatContainer.appendChild(cardContainer);
    
    // 이전 메뉴 버튼 추가
    addBackButton('이전 메뉴로', () => {
        clearChat();
        const menuStructure = getMenuStructure();
        const categoryData = menuStructure[currentCategory];
        
        setTimeout(() => {
            addMessage('ai', '어떤 기능을 자세히 살펴보시겠습니까?');
            showSecondaryMenuCards(categoryData);
        }, 100);
    });
    
    chatContainer.scrollTop = chatContainer.scrollHeight;
}

function selectItemFromOnboarding(item) {
    currentItem = item;
    
    // 선택 카드 제거
    const cards = document.querySelectorAll('.chat-selection-cards');
    cards.forEach(card => card.remove());
    
    // 사용자 선택 메시지 추가
    addMessage('user', `${item.title}에 대해 자세히 알려주세요.`);
    
    // 인지관성 개선 카테고리의 경우 수학 특화 메시지
    if (currentCategory === 'bias') {
        showMathSpecificItemIntro(item);
    } else {
        showDefaultItemIntro(item);
    }
}

// 수학 특화 세부 기능 소개
function showMathSpecificItemIntro(item) {
    const mathItemMessages = {
        '포모도르설정': {
            intro: `포모도르 기법! 집중력 문제로 고민인가요? 📚⏰`,
            context: `"30분만 앉아있어도 딴 생각이 나요", "핸드폰이 자꾸 신경 쓰여요" - 이런 고민 정말 많죠. 포모도르 기법은 단순히 25분 공부하고 5분 쉬는 게 아니에요!`,
            action: `개인별 최적 집중 시간을 찾고, 수학 문제 유형별로 시간을 조정하는 맞춤형 포모도르를 설정해드릴게요!`
        },
        '개념노트 사용법': {
            intro: `개념노트! 수학 실력의 비밀창고를 만들어봐요! 📔✨`,
            context: `"개념은 공부했는데 문제만 보면 기억이 안 나요" - 이건 개념을 단순 암기했기 때문이에요. 진짜 개념 정리는 따로 있거든요!`,
            action: `공식 암기가 아닌, 개념 간 연결고리를 만드는 노트 작성법을 알려드릴게요. 나만의 수학 지식 네트워크를 구축해봅시다!`
        },
        '음성대화 사용법': {
            intro: `AI와 수학 대화! 마치 개인 과외 선생님처럼요! 🗣️🤖`,
            context: `"혼자 공부하면 막히는 부분을 물어볼 사람이 없어요" - 이제 AI와 실시간으로 수학 대화를 나눠보세요!`,
            action: `단순 검색이 아닌, 진짜 대화를 통해 개념을 이해하고 문제 해결 과정을 함께 고민해보는 방법을 알려드릴게요!`
        },
        '문제풀이 시작': {
            intro: `문제를 마주한 그 첫 순간! 여기서 승부가 갈려요! 🎯`,
            context: `"문제를 읽어도 뭘 구하라는 건지 모르겠어요", "어떤 공식을 써야 할지 감이 안 와요" - 이런 경험 있죠?`,
            action: `문제 분석부터 접근 전략까지, 어떤 문제든 자신 있게 시작할 수 있는 체계적인 방법을 알려드릴게요!`
        },
        '시간관리 (그냥 ... , 빨리 풀기)': {
            intro: `시간관리! 실전에서 가장 중요한 스킬이에요! ⏱️💨`,
            context: `"시간 재고 풀면 다 맞는데, 시험에서는 시간이 부족해서..." - 이것도 기술이에요!`,
            action: `무작정 빨리 푸는 게 아니라, 전략적 시간 배분과 속도 조절 기법을 연습해봅시다!`
        }
    };

    const defaultMessage = {
        intro: `${item.title}! 좋은 선택이에요! 🌟`,
        context: `수학 공부에서 정말 중요한 부분이거든요.`,
        action: `지금부터 차근차근 알려드릴게요!`
    };

    const message = mathItemMessages[item.title] || defaultMessage;

    setTimeout(() => {
        addMessage('ai', message.intro);
    }, 500);
    
    setTimeout(() => {
        addMessage('ai', message.context);
    }, 2000);
    
    setTimeout(() => {
        addMessage('ai', message.action);
    }, 3500);
    
    setTimeout(() => {
        addMessage('ai', `자, 그럼 ${item.title} 기능을 실행해볼까요? 🚀`);
    }, 5000);
    
    startItemExecution(item, 6000);
}

// 기본 세부 기능 소개
function showDefaultItemIntro(item) {
    // AI 응답
    setTimeout(() => {
        addMessage('ai', `${item.title}에 대해 자세히 설명드리겠습니다.`);
    }, 500);
    
    setTimeout(() => {
        addMessage('ai', item.description);
    }, 1500);
    
    setTimeout(() => {
        addMessage('ai', `${item.title} 기능을 실행하겠습니다.`);
    }, 2500);
    
    startItemExecution(item, 3500);
}

// 공통 아이템 실행 함수
function startItemExecution(item, delay) {
    // 메뉴 탭과 동일한 방식으로 진행상황 표시
    setTimeout(() => {
        // 채팅 컨테이너에 진행 상황 표시 영역 추가 (고유 ID로 겹치지 않게)
        const chatContainer = document.getElementById('chatContainer');
        const progressId = `onboardingProgress_${Date.now()}`;
        const progressArea = document.createElement('div');
        progressArea.className = 'onboarding-progress-area';
        progressArea.innerHTML = `
            <div class="progress-header">
                <h3>🚀 ${item.title} 실행 중...</h3>
            </div>
            <div class="progress-messages" id="${progressId}"></div>
        `;
        chatContainer.appendChild(progressArea);
        chatContainer.scrollTop = chatContainer.scrollHeight;
        
        const progressMessages = document.getElementById(progressId);
        
        setTimeout(() => {
            addOnboardingProgressMessage(progressMessages, `${item.title} 실행을 시작합니다...`);
        }, 500);
        
        // 세부 작업들 순차 실행
        item.details.forEach((detail, index) => {
            setTimeout(() => {
                addOnboardingProgressMessage(progressMessages, `✓ ${detail} - 완료`);
            }, 1500 + (index * 800));
        });
        
        // 완료 메시지 및 이전 메뉴 버튼
        setTimeout(() => {
            addOnboardingProgressMessage(progressMessages, `🎉 ${item.title} 실행이 완료되었습니다!`);
            
            // 이전 메뉴 버튼 추가
            setTimeout(() => {
                addBackButton('이전 메뉴로', () => {
                    // 직전 단계인 세부 메뉴 선택 카드로 이동
                    clearChat();
                    setTimeout(() => {
                        addMessage('ai', '어떤 세부 기능을 자세히 알아보시겠습니까?');
                        showDetailMenuCards(currentTab.items);
                    }, 100);
                });
            }, 500);
        }, 1500 + (item.details.length * 800) + 1000);
    }, delay);
}

// ==================== 메뉴 모드 ====================
async function showMenuInterface(category) {
    const menuStructure = getMenuStructure();
    const categoryData = menuStructure[category];
    
    if (!categoryData) {
        showMenuWelcome();
        return;
    }

    const menuTabGrid = document.getElementById('menuTabGrid');
    const submenuContainer = document.getElementById('submenuContainer');
    
    // 바이럴 마케팅의 경우 특별한 iframe 인터페이스 표시
    if (category === 'viral') {
        showViralMarketingInterface();
        return;
    }
    
    // 메뉴 그리드 생성 - 탭 버튼들 표시
    menuTabGrid.innerHTML = `
        <div class="menu-interface">
            <h2>${agents[category].avatar} ${categoryData.title}</h2>
            <p class="menu-description">${categoryData.description}</p>
            <div class="menu-tabs-full">
                ${categoryData.tabs.map((tab, index) => `
                    <button class="menu-tab-button-full ${index === 0 ? 'active' : ''}" onclick="selectMenuTab('${tab.id}', '${tab.title}')">
                        ${tab.title}
                    </button>
                `).join('')}
            </div>
        </div>
    `;
    
    // 첫 번째 탭의 세부 메뉴를 자동으로 표시
    if (categoryData.tabs.length > 0) {
        const firstTab = categoryData.tabs[0];
        await showSubmenuItems(firstTab);
    }
}

function showMenuWelcome() {
    const menuTabGrid = document.getElementById('menuTabGrid');
    const submenuContainer = document.getElementById('submenuContainer');
    
    menuTabGrid.innerHTML = `
        <div class="menu-welcome">
            <h2>메뉴 선택</h2>
            <p>좌측 메뉴에서 원하는 기능을 선택하세요.</p>
        </div>
    `;
    
    submenuContainer.innerHTML = '';
}

// 메뉴 탭 선택 함수
async function selectMenuTab(tabId, tabTitle) {
    const menuStructure = getMenuStructure();
    const categoryData = menuStructure[currentCategory];
    
    if (!categoryData) return;
    
    // 선택된 탭 찾기
    const selectedTab = categoryData.tabs.find(tab => tab.id === tabId);
    if (!selectedTab) return;
    
    // 탭 버튼 활성화 상태 업데이트
    document.querySelectorAll('.menu-tab-button-full').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    // 서브메뉴 표시
    await showSubmenuItems(selectedTab);
}

// 서브메뉴 아이템 표시 함수
async function showSubmenuItems(tab) {
    const submenuContainer = document.getElementById('submenuContainer');
    
    // 현재 탭 정보 저장
    currentTab = tab;
    
    // 인지관성 카테고리의 경우 특별 처리
    if (currentCategory === 'bias') {
        showBiasPatterns(tab);
        return;
    }
    
    // 로딩 표시
    submenuContainer.innerHTML = '<div class="loading">플러그인 설정을 불러오는 중...</div>';
    
    // 데이터베이스에서 카드 설정 로드
    let savedCardSettings = [];
    if (window.ktmPluginClient) {
        try {
            console.log(`Loading cards for category: ${currentCategory}, tab: ${tab.title}`);
            console.log('Current user ID:', window.ktmPluginClient.currentUserId);
            
            // 항상 DB에서 최신 데이터를 로드 (카테고리 전체)
            await window.ktmPluginClient.loadCardSettings(currentCategory);
            
            // 모든 카드를 가져온 후 현재 탭과 일치하는 것만 필터링
            const allCategoryCards = window.ktmPluginClient.getCardSettings(currentCategory) || [];
            
            console.log(`All cards for category ${currentCategory}:`, allCategoryCards);
            console.log(`Current tab title: "${tab.title}"`);
            
            // card_title이 현재 탭의 title과 일치하는 카드만 필터링
            // 대소문자 구분 없이 비교하고, 앞뒤 공백 제거
            const normalizedTabTitle = tab.title.trim();
            const tabSettings = allCategoryCards.filter(card => {
                const normalizedCardTitle = (card.card_title || '').trim();
                const matches = normalizedCardTitle === normalizedTabTitle;
                console.log(`Card check - card_title: "${card.card_title}" (normalized: "${normalizedCardTitle}"), tab.title: "${tab.title}" (normalized: "${normalizedTabTitle}"), matches: ${matches}`);
                return matches;
            });
            
            // 현재 탭에 해당하는 설정만 사용
            if (Array.isArray(tabSettings)) {
                savedCardSettings = tabSettings;
            }
            
            console.log(`Filtered cards for tab "${tab.title}":`, savedCardSettings);
            console.log('Number of cards after filtering:', savedCardSettings.length);
            
            // 기본 카드만 필터링하여 확인
            const defaultCards = savedCardSettings.filter(card => card.plugin_id === 'default_card');
            console.log('Number of default cards:', defaultCards.length);
        } catch (error) {
            console.error('카드 설정 로드 실패:', error);
        }
    }
    
    submenuContainer.innerHTML = `
        <div class="menu-tab-section">
            <h3>${tab.title}</h3>
            <p class="tab-description">${tab.description}</p>
            
            <div class="menu-cards-container">
                <div class="menu-cards-grid" id="menuCardsGrid">
                    <!-- 데이터베이스에서 로드한 플러그인 카드들 -->
                    ${savedCardSettings
                        .map(cardSetting => {
                            console.log('Processing cardSetting:', cardSetting);
                            const plugin = pluginTypes.find(p => p.id === cardSetting.plugin_id);
                            console.log('Found plugin:', plugin);
                            
                            const config = cardSetting.plugin_config || {};
                            const cardTitle = config.plugin_name || config.card_title || cardSetting.card_title || '제목 없음';
                            const description = config.description || (plugin ? plugin.description : '사용자 정의 플러그인');
                            
                            // 플러그인 타입에 따른 아이콘 설정
                            let icon = '🔌';
                            if (plugin) {
                                icon = plugin.icon;
                            } else if (cardSetting.plugin_id === 'default_card') {
                                icon = '📋';
                                console.log('기본 카드 발견:', cardSetting);
                            }
                            
                            return `
                                <div class="menu-card plugin-card plugin-modified" 
                                     onclick="executePluginAction('${cardSetting.plugin_id}', ${JSON.stringify(config).replace(/"/g, '&quot;')})">
                                    <button class="card-settings-btn" onclick="event.stopPropagation(); editPluginSettings('${cardSetting.id}', '${cardSetting.plugin_id}', '${cardTitle}')">⚙️</button>
                                    <button class="card-delete-btn" onclick="event.stopPropagation(); deletePluginCard('${currentCategory}', '${tab.title}', '${cardSetting.id}', '${cardSetting.card_index}')">❌</button>
                                    <div class="card-icon">${icon}</div>
                                    <h4>${cardTitle}</h4>
                                    <p class="card-description">${description}</p>
                                    <div class="plugin-indicator">${plugin ? plugin.title : cardSetting.plugin_id === 'default_card' ? '기본 카드' : '플러그인'}</div>
                                </div>
                            `;
                        }).join('')}
                    
                    <!-- 플러그인 추가 카드 -->
                    <div class="menu-card add-card" onclick="showAddPluginMenu()">
                        <div class="add-icon">+</div>
                        <p>플러그인 추가</p>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// 플러그인 액션 실행
function executePluginAction(pluginId, config) {
    console.log('Executing plugin action:', pluginId, config);
    
    // URL 또는 파일 경로 확인
    let targetUrl = null;
    
    if (pluginId === 'default_card') {
        // 기본 카드 - URL 또는 파일 경로 확인
        targetUrl = config.url || config.file_path || config.internal_url;
    } else if (pluginId === 'external_link') {
        targetUrl = config.external_url || config.url;
    } else if (pluginId === 'internal_link') {
        targetUrl = config.internal_url || config.file_path || config.url;
    } else if (pluginId === 'send_message') {
        // 메시지 발송 기능 - 팝업으로 메시지 표시
        showMessagePopup(config);
        return;
    } else if (pluginId === 'agent') {
        // 에이전트 URL이 있으면 iframe으로 열기
        const agentUrl = config.agent_url || config.fileName || config.file_path;
        
        if (agentUrl) {
            // iframe 슬라이딩 패널로 열기
            openAgentPanelWithIframe({
                url: agentUrl,
                title: config.plugin_name || config.card_title || config.agent_config?.title || '에이전트'
            });
        } else {
            // URL이 없으면 기존 채팅 패널로 폴백
            const agentConfig = config.agent_config || {};
            const prompt = config.agent_prompt || config.systemPrompt || `"${config.plugin_name || config.card_title || '에이전트'}" 기능을 도와드리겠습니다.`;
            
            openChatPanelWithAgent({
                title: config.plugin_name || config.card_title || agentConfig.title || '에이전트',
                description: config.description || agentConfig.description || '에이전트 기능',
                systemPrompt: prompt,
                config: {
                    ...agentConfig,
                    details: config.details || agentConfig.details || [],
                    category: config.category || agentConfig.category,
                    originalTab: config.originalTab || agentConfig.originalTab,
                    agent_type: config.agent_type || 'custom'
                }
            });
        }
        return;
    }
    
    // 공통 파일 경로 처리 (모든 플러그인 타입에 적용)
    if (!targetUrl && config.file_path) {
        targetUrl = config.file_path;
        // 상대 경로인 경우 처리
        if (!targetUrl.startsWith('http') && !targetUrl.startsWith('/')) {
            const baseUrl = window.location.origin + window.location.pathname.replace(/[^/]*$/, '');
            targetUrl = baseUrl + targetUrl;
        }
    }
    
    // URL이 있으면 우측 패널로 열기
    if (targetUrl) {
        openAgentPanel();
        const iframe = document.getElementById('agentIframe');
        if (iframe) {
            iframe.src = targetUrl;
        }
    } else {
        // URL이 없어도 우측 패널에 플러그인 정보 표시
        console.warn('URL이 지정되지 않았습니다. 기본 정보를 표시합니다.', config);
        
        const pluginName = config.plugin_name || config.card_title || '플러그인';
        const description = config.description || '설명이 없습니다.';
        
        const pluginContent = `
                <!DOCTYPE html>
                <html lang="ko">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>${pluginName}</title>
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            padding: 40px;
                            background-color: #f5f5f5;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            min-height: 100vh;
                            margin: 0;
                        }
                        .content {
                            background: white;
                            padding: 40px;
                            border-radius: 8px;
                            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                            text-align: center;
                            max-width: 600px;
                        }
                        h1 {
                            color: #333;
                            margin-bottom: 20px;
                        }
                        p {
                            color: #666;
                            line-height: 1.6;
                        }
                        .info {
                            background: #f0f0f0;
                            padding: 20px;
                            border-radius: 4px;
                            margin-top: 20px;
                        }
                        .config-data {
                            text-align: left;
                            background: #f8f9fa;
                            padding: 15px;
                            border-radius: 4px;
                            margin-top: 20px;
                            font-family: monospace;
                            font-size: 14px;
                            white-space: pre-wrap;
                            word-wrap: break-word;
                        }
                    </style>
                </head>
                <body>
                    <div class="content">
                        <h1>${pluginName}</h1>
                        <p>${description}</p>
                        <div class="info">
                            <p>이 플러그인에는 아직 URL이나 파일이 지정되지 않았습니다.</p>
                        </div>
                        <div class="config-data">
                            <strong>플러그인 설정 정보:</strong><br>
                            ${JSON.stringify(config, null, 2)}
                        </div>
                    </div>
                </body>
                </html>
            `;
            
        // iframe에 HTML 콘텐츠 로드
        openAgentPanel();
        const iframe = document.getElementById('agentIframe');
        if (iframe) {
            const blob = new Blob([pluginContent], { type: 'text/html' });
            const url = URL.createObjectURL(blob);
            iframe.src = url;
            
            // URL 정리 (메모리 누수 방지)
            iframe.onload = () => {
                URL.revokeObjectURL(url);
            };
        }
    }
}

// 메시지 팝업 표시 함수 (우측 패널로 변경)
function showMessagePopup(config) {
    // 우측 패널로 메시지 표시
    openAgentPanel();
    
    const messageContent = config.message_content || '메시지가 없습니다.';
    const messageType = config.message_type || 'info';
    const pluginName = config.plugin_name || '메시지';
    
    // 메시지 타입별 색상
    const colors = {
        info: '#0066cc',
        success: '#00a854',
        warning: '#faad14',
        error: '#ff4d4f'
    };
    
    const popupHTML = `
        <!DOCTYPE html>
        <html lang="ko">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>${pluginName}</title>
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    margin: 0;
                    padding: 0;
                    background: #f5f5f5;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    height: 100vh;
                }
                .message-container {
                    background: white;
                    border-radius: 12px;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
                    padding: 40px;
                    max-width: 90%;
                    text-align: center;
                }
                .message-icon {
                    font-size: 48px;
                    margin-bottom: 20px;
                }
                .message-title {
                    font-size: 24px;
                    font-weight: 600;
                    color: #333;
                    margin-bottom: 10px;
                }
                .message-content {
                    font-size: 16px;
                    color: #666;
                    line-height: 1.6;
                    margin-bottom: 30px;
                }
                .close-button {
                    background: ${colors[messageType]};
                    color: white;
                    border: none;
                    padding: 12px 30px;
                    border-radius: 6px;
                    font-size: 16px;
                    cursor: pointer;
                    transition: opacity 0.3s;
                }
                .close-button:hover {
                    opacity: 0.8;
                }
            </style>
        </head>
        <body>
            <div class="message-container">
                <div class="message-icon">
                    ${messageType === 'info' ? 'ℹ️' : 
                      messageType === 'success' ? '✅' : 
                      messageType === 'warning' ? '⚠️' : 
                      messageType === 'error' ? '❌' : '💬'}
                </div>
                <h2 class="message-title">${pluginName}</h2>
                <div class="message-content">${messageContent.replace(/\n/g, '<br>')}</div>
                <button class="close-button" onclick="window.close()">확인</button>
            </div>
        </body>
        </html>
    `;
    
    // iframe에 HTML 콘텐츠 로드
    const iframe = document.getElementById('agentIframe');
    if (iframe) {
        const blob = new Blob([popupHTML], { type: 'text/html' });
        const url = URL.createObjectURL(blob);
        iframe.src = url;
        
        // URL 정리 (메모리 누수 방지)
        iframe.onload = () => {
            URL.revokeObjectURL(url);
        };
    }
}

// 에이전트 팝업 열기 함수 (채팅 패널로 리다이렉트)
function openAgentPopup(agentData) {
    // 채팅 패널로 리다이렉트
    openChatPanelWithAgent(agentData);
}

// 기본 카드 삭제
async function deleteDefaultCard(category, tabTitle, cardTitle) {
    if (confirm(`'${cardTitle}' 카드를 삭제하시겠습니까?\n\n삭제된 카드는 나중에 복원할 수 있습니다.`)) {
        const cardKey = `${category}_${tabTitle}_${cardTitle}`;
        
        // 삭제된 카드 목록에 추가
        deletedDefaultCards.add(cardKey);
        
        // localStorage에 저장하여 영구적으로 기억
        saveDeletedCards();
        
        // 현재 탭 새로고침
        if (currentTab) {
            if (currentCategory === 'viral') {
                // 바이럴 마케팅의 경우 현재 플랫폼 탭 새로고침
                showViralMarketingInterface();
            } else {
                await showSubmenuItems(currentTab);
            }
        }
        
        alert(`'${cardTitle}' 카드가 삭제되었습니다.`);
    }
}

// 플러그인 카드 삭제
async function deletePluginCard(category, tabTitle, cardId, cardIndex) {
    // 현재 카드 설정 가져오기
    if (window.ktmPluginClient) {
        const existingCards = window.ktmPluginClient.getCardSettings(category, tabTitle) || [];
        const cardToDelete = existingCards.find(card => card.id == cardId || card.card_index == cardIndex);
        
        if (!cardToDelete) {
            alert('삭제할 카드를 찾을 수 없습니다.');
            return;
        }
        
        const cardTitle = cardToDelete.plugin_config?.plugin_name || cardToDelete.plugin_config?.card_title || '이 카드';
        
        if (confirm(`'${cardTitle}' 카드를 삭제하시겠습니까?`)) {
            try {
                // deleteCardSetting API 호출 - cardId와 cardIndex를 사용
                const result = await window.ktmPluginClient.deleteCardSettingById(
                    category, 
                    tabTitle, 
                    cardId || null,
                    cardIndex
                );
                
                if (result.success) {
                    alert('플러그인 카드가 삭제되었습니다.');
                    // 화면 새로고침
                    if (currentCategory === 'viral') {
                        // 바이럴 마케팅의 경우 현재 플랫폼 탭 새로고침
                        showViralMarketingInterface();
                    } else {
                        showMenuInterface(currentCategory);
                    }
                } else {
                    alert('삭제 중 오류가 발생했습니다: ' + result.error);
                }
            } catch (error) {
                console.error('플러그인 삭제 오류:', error);
                console.error('Error details:', {
                    message: error.message,
                    stack: error.stack,
                    category: category,
                    tabTitle: tabTitle,
                    cardId: cardId,
                    cardIndex: cardIndex
                });
                alert('삭제 중 오류가 발생했습니다: ' + error.message);
            }
        }
    }
}

// 플러그인 설정 편집
async function editPluginSettings(settingId, pluginId, cardTitle) {
    // 기존 설정을 로드하여 편집 모달 열기
    const plugin = pluginTypes.find(p => p.id === pluginId);
    if (!plugin) return;
    
    // 현재 탭의 카드 설정 가져오기
    if (window.ktmPluginClient && currentTab) {
        const existingCards = window.ktmPluginClient.getCardSettings(currentCategory, currentTab.title) || [];
        
        // settingId로 해당 카드 찾기
        const existingCard = existingCards.find(card => card.id == settingId);
        
        if (existingCard) {
            // 기존 카드의 설정을 포함하여 편집 모달 열기
            const config = existingCard.plugin_config || {};
            openPluginSettings(pluginId, { 
                title: cardTitle, 
                existingCard: true,
                settingId: settingId,
                existingConfig: config,
                cardIndex: existingCard.card_index
            });
        } else {
            // 카드를 찾을 수 없으면 새 카드로 처리
            openPluginSettings(pluginId, { title: cardTitle, existingCard: true });
        }
    } else {
        // 플러그인 클라이언트가 없으면 기본 동작
        openPluginSettings(pluginId, { title: cardTitle, existingCard: true });
    }
}

// executeMenuAction 함수 제거됨 - 모든 카드는 플러그인으로 처리

// 연쇄상호작용 인터페이스 표시
function showChainInteractionInterface(container, itemTitle, tabTitle) {
    const chainInteractionArea = document.createElement('div');
    chainInteractionArea.className = 'chain-interaction-area';
    chainInteractionArea.innerHTML = `
        <div class="chain-interaction-header">
            <h4>🔗 연쇄상호작용 시스템</h4>
            <p>${itemTitle}에 대한 유사한 상황의 학생들에게 동시 피드백을 진행할 수 있습니다.</p>
        </div>
        <div class="chain-interaction-controls">
            <div class="condition-status" id="conditionStatus">
                <span class="status-indicator">⚠️</span>
                <span class="status-text">조건 미설정</span>
                <button class="condition-setup-btn" onclick="setupConditions('${itemTitle}', '${tabTitle}')">조건 설정</button>
            </div>
            <div class="student-search-area" id="studentSearchArea" style="display: none;">
                <div class="search-controls">
                    <input type="text" placeholder="학생 검색..." class="student-search-input" id="studentSearchInput">
                    <button class="search-btn" onclick="searchStudents()">검색</button>
                </div>
                <div class="student-list" id="studentList">
                    <!-- 학생 목록이 여기에 표시됩니다 -->
                </div>
                <div class="execution-controls">
                    <button class="execute-btn" onclick="executeChainInteraction('${itemTitle}', '${tabTitle}')" disabled id="executeBtn">실행</button>
                    <button class="skip-btn" onclick="skipChainInteraction()">Skip</button>
                </div>
            </div>
        </div>
    `;
    
    container.appendChild(chainInteractionArea);
    container.scrollTop = container.scrollHeight;
}

// 조건 설정 (팝업으로 연결 예정)
function setupConditions(itemTitle, tabTitle) {
    // 임시로 조건이 설정된 것으로 처리 (실제로는 팝업으로 연결)
    const conditionStatus = document.getElementById('conditionStatus');
    const studentSearchArea = document.getElementById('studentSearchArea');
    
    conditionStatus.innerHTML = `
        <span class="status-indicator">✅</span>
        <span class="status-text">조건 설정됨</span>
        <button class="condition-setup-btn" onclick="setupConditions('${itemTitle}', '${tabTitle}')">조건 수정</button>
    `;
    
    studentSearchArea.style.display = 'block';
    
    // 임시 학생 데이터로 자동 검색 실행
    setTimeout(() => {
        autoSearchStudents(itemTitle);
    }, 500);
}

// 학생 검색 (DB 연결 예정)
function searchStudents() {
    const searchInput = document.getElementById('studentSearchInput');
    const searchTerm = searchInput.value.trim();
    
    if (searchTerm) {
        displayStudentList(searchTerm);
    }
}

// 자동 학생 검색 (체험용)
function autoSearchStudents(itemTitle) {
    const studentList = document.getElementById('studentList');
    const executeBtn = document.getElementById('executeBtn');
    
    // 임시 학생 데이터
    const sampleStudents = [
        { name: '김학생', grade: '고2', similarity: '85%', status: '유사패턴' },
        { name: '이학생', grade: '고2', similarity: '78%', status: '유사패턴' },
        { name: '박학생', grade: '고1', similarity: '72%', status: '부분유사' }
    ];
    
    studentList.innerHTML = `
        <div class="student-list-header">
            <h5>유사 패턴 학생 목록 (${sampleStudents.length}명)</h5>
        </div>
        <div class="student-items">
            ${sampleStudents.map(student => `
                <div class="student-item">
                    <div class="student-info">
                        <span class="student-name">${student.name}</span>
                        <span class="student-grade">${student.grade}</span>
                    </div>
                    <div class="student-stats">
                        <span class="similarity">${student.similarity}</span>
                        <span class="status ${student.status === '유사패턴' ? 'similar' : 'partial'}">${student.status}</span>
                    </div>
                </div>
            `).join('')}
        </div>
    `;
    
    executeBtn.disabled = false;
}

// 학생 목록 표시
function displayStudentList(searchTerm) {
    // DB 검색 결과를 표시하는 로직 (추후 구현)
    autoSearchStudents(searchTerm); // 임시로 자동 검색 사용
}

// 연쇄상호작용 실행
function executeChainInteraction(itemTitle, tabTitle) {
    const studentList = document.getElementById('studentList');
    const executeBtn = document.getElementById('executeBtn');
    
    // 실행 중 상태로 변경
    executeBtn.textContent = '실행 중...';
    executeBtn.disabled = true;
    
    // 실행 결과 표시
    setTimeout(() => {
        const resultArea = document.createElement('div');
        resultArea.className = 'execution-result';
        resultArea.innerHTML = `
            <div class="result-header">
                <h5>🎉 연쇄상호작용 실행 완료</h5>
            </div>
            <div class="result-details">
                <p>✓ 3명의 학생에게 메시지 발송 완료</p>
                <p>✓ 개별 맞춤 피드백 전달</p>
            </div>
            <div class="follow-up-area">
                <div class="follow-up-status">
                    <span class="status-indicator">⚠️</span>
                    <span class="status-text">추가상호작용, 추적계획 없음</span>
                    <button class="follow-up-btn" onclick="setupFollowUp('${itemTitle}', '${tabTitle}')">후속 상호작용 설정</button>
                </div>
                <button class="skip-btn" onclick="skipFollowUp()">Skip</button>
            </div>
        `;
        
        studentList.appendChild(resultArea);
        studentList.scrollTop = studentList.scrollHeight;
    }, 2000);
}

// Skip 연쇄상호작용
function skipChainInteraction() {
    const chainInteractionArea = document.querySelector('.chain-interaction-area');
    if (chainInteractionArea) {
        chainInteractionArea.style.opacity = '0.5';
        
        const skipMessage = document.createElement('div');
        skipMessage.className = 'skip-message';
        skipMessage.innerHTML = `
            <p>⏭️ 연쇄상호작용을 건너뛰었습니다.</p>
            <p>언제든 다시 실행할 수 있습니다.</p>
        `;
        
        chainInteractionArea.appendChild(skipMessage);
    }
}

// 후속 상호작용 설정
function setupFollowUp(itemTitle, tabTitle) {
    // 임시로 설정된 것으로 처리 (실제로는 별도 설정 화면으로 연결)
    const followUpStatus = document.querySelector('.follow-up-status');
    
    followUpStatus.innerHTML = `
        <span class="status-indicator">✅</span>
        <span class="status-text">후속 상호작용 설정됨</span>
        <button class="follow-up-btn" onclick="setupFollowUp('${itemTitle}', '${tabTitle}')">설정 수정</button>
    `;
    
    setTimeout(() => {
        const followUpArea = document.querySelector('.follow-up-area');
        const details = document.createElement('div');
        details.className = 'follow-up-details';
        details.innerHTML = `
            <div class="follow-up-schedule">
                <h6>📅 설정된 후속 상호작용</h6>
                <ul>
                    <li>1일 후: 학습 진도 체크</li>
                    <li>3일 후: 성과 평가</li>
                    <li>1주 후: 종합 리뷰</li>
                </ul>
            </div>
        `;
        followUpArea.appendChild(details);
    }, 500);
}

// Skip 후속 상호작용
function skipFollowUp() {
    const followUpArea = document.querySelector('.follow-up-area');
    if (followUpArea) {
        followUpArea.style.opacity = '0.5';
        
        const skipMessage = document.createElement('div');
        skipMessage.className = 'skip-message';
        skipMessage.innerHTML = `<p>⏭️ 후속 상호작용 설정을 건너뛰었습니다.</p>`;
        
        followUpArea.appendChild(skipMessage);
    }
}

function addProgressMessage(container, message) {
    const messageElement = document.createElement('div');
    messageElement.className = 'progress-message';
    messageElement.textContent = message;
    container.appendChild(messageElement);
    container.scrollTop = container.scrollHeight;
}

// 온보딩용 진행 메시지 추가 함수
function addOnboardingProgressMessage(container, message) {
    const messageElement = document.createElement('div');
    messageElement.className = 'onboarding-progress-message';
    messageElement.textContent = message;
    container.appendChild(messageElement);
    container.scrollTop = container.scrollHeight;
    
    // 채팅 컨테이너도 스크롤
    const chatContainer = document.getElementById('chatContainer');
    chatContainer.scrollTop = chatContainer.scrollHeight;
}

// ==================== 채팅 모드 ====================
function showChatInterface() {
    clearChat();
    
    // 현재 카테고리와 메뉴 정보 가져오기
    const menuStructure = getMenuStructure();
    const categoryData = menuStructure[currentCategory];
    const agent = agents[currentCategory];
    
    // 초기 메시지 표시
    setTimeout(() => {
        if (currentCategory && categoryData) {
            addMessage('ai', `안녕하세요! ${agent.name}입니다. ${categoryData.title}에 대해 무엇이든 물어보세요.`);
            
            // 테스트 인터페이스 링크 추가
            setTimeout(() => {
                addTestInterfaceLink();
            }, 500);
            
            // 현재 탭 정보가 있으면 표시
            if (currentTabId) {
                const currentTabData = categoryData.tabs.find(tab => tab.id === currentTabId);
                if (currentTabData) {
                    setTimeout(() => {
                        addMessage('ai', `현재 "${currentTabData.title}" 탭을 보고 계시네요. 이와 관련된 학습 활동이나 기록에 대해 궁금하신 점이 있으신가요?`);
                    }, 1500);
                }
            }
        } else {
            addMessage('ai', '안녕하세요! 학습 관리 시스템입니다. 좌측 메뉴에서 카테고리를 선택하시면 더 구체적인 도움을 드릴 수 있습니다.');
            
            // 테스트 인터페이스 링크 추가
            setTimeout(() => {
                addTestInterfaceLink();
            }, 500);
        }
    }, 300);
    
    // 채팅 입력 활성화
    document.getElementById('messageInput').disabled = false;
    document.getElementById('messageInput').placeholder = '질문을 입력하세요...';
}

function goToTestPage() {
    // 테스트 페이지로 이동하는 로직
    alert('테스트 페이지로 이동합니다. (현재는 개발 중)');
}

// 테스트 인터페이스 링크 추가 함수
function addTestInterfaceLink() {
    const chatContainer = document.getElementById('chatContainer');
    const linkElement = document.createElement('div');
    linkElement.className = 'test-interface-link';
    linkElement.innerHTML = `
        <a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/omniui/omniui_teacher_api.php" 
           target="_blank" 
           class="test-link">
            <span class="test-icon">🔧</span>
            <span class="test-text">테스트 인터페이스</span>
        </a>
    `;
    
    chatContainer.appendChild(linkElement);
    chatContainer.scrollTop = chatContainer.scrollHeight;
}

function showChatPreview() {
    const chatContainer = document.getElementById('chatContainer');
    chatContainer.innerHTML = `
        <div class="chat-preview">
            <h3>채팅 기능 미리보기</h3>
            <div class="preview-messages">
                <div class="preview-message ai">
                    <div class="message-avatar">🤖</div>
                    <div class="message-content">
                        <div class="message-text">안녕하세요! 무엇을 도와드릴까요?</div>
                    </div>
                </div>
                <div class="preview-message user">
                    <div class="message-avatar">👤</div>
                    <div class="message-content">
                        <div class="message-text">학습 데이터를 분석해주세요.</div>
                    </div>
                </div>
                <div class="preview-message ai">
                    <div class="message-avatar">🤖</div>
                    <div class="message-content">
                        <div class="message-text">학습 데이터를 분석하고 있습니다...</div>
                    </div>
                </div>
            </div>
            <p class="preview-note">* 이는 미리보기이며, 실제 기능은 개발 중입니다.</p>
            <button class="test-button" onclick="showChatInterface()">뒤로 가기</button>
        </div>
    `;
}

// ==================== 채팅 기능 ====================
function clearChat() {
    const chatContainer = document.getElementById('chatContainer');
    chatContainer.innerHTML = '';
}

function addMessage(sender, message) {
    const chatContainer = document.getElementById('chatContainer');
    const messageElement = document.createElement('div');
    messageElement.className = `message ${sender}`;
    messageElement.id = `message-${Date.now()}`;
    
    const avatar = sender === 'user' ? '👤' : (agents[currentCategory]?.avatar || '🤖');
    
    messageElement.innerHTML = `
        <div class="message-avatar">${avatar}</div>
        <div class="message-content">
            <div class="message-text">${message}</div>
            <div class="message-time">${new Date().toLocaleTimeString()}</div>
        </div>
    `;
    
    chatContainer.appendChild(messageElement);
    chatContainer.scrollTop = chatContainer.scrollHeight;
    
    return messageElement.id;
}

function sendMessage() {
    const messageInput = document.getElementById('messageInput');
    const message = messageInput.value.trim();
    
    if (message) {
        addMessage('user', message);
        messageInput.value = '';
        
        // 현재 컨텍스트 기반 AI 응답 생성
        setTimeout(() => {
            const response = generateContextualResponse(message);
            addMessage('ai', response);
        }, 1000);
    }
}

// 현재 메뉴 컨텍스트에 따른 응답 생성
function generateContextualResponse(userMessage) {
    const menuStructure = getMenuStructure();
    const categoryData = menuStructure[currentCategory];
    const lowerMessage = userMessage.toLowerCase();
    
    // 카테고리별 맞춤 응답
    if (currentCategory === 'quarterly' && categoryData) {
        if (lowerMessage.includes('목표') || lowerMessage.includes('분기')) {
            return `분기 목표 설정과 관련된 질문이시군요. DB를 확인해보니 최근 분기목표 활동 기록이 있습니다.\n\n` +
                   `📊 활동 기록 (mdl_alt42_activitylog):\n` +
                   `- type: 분기목표\n` +
                   `- course: 수능/내신 대비\n` +
                   `- status: 정규수업 진행중\n\n` +
                   `분기별 목표를 세분화하여 주간, 일일 목표로 연결하는 것이 중요합니다.`;
        }
    } else if (currentCategory === 'weekly' && categoryData) {
        if (lowerMessage.includes('주간') || lowerMessage.includes('계획')) {
            return `주간 활동 계획에 대해 알려드리겠습니다.\n\n` +
                   `📅 주간 활동 패턴 (mdl_alt42_contextlog):\n` +
                   `- 월-금: 정규수업 (개념/심화)\n` +
                   `- 토: 보충수업\n` +
                   `- type: parentaltalk 기록 확인됨\n\n` +
                   `이번 주 학습 진도와 다음 주 계획을 연계하여 관리하는 것을 추천합니다.`;
        }
    } else if (currentCategory === 'daily' && categoryData) {
        if (lowerMessage.includes('오늘') || lowerMessage.includes('일정')) {
            return `오늘의 학습 활동을 분석해드리겠습니다.\n\n` +
                   `📝 오늘 활동 (mdl_alt42_activitylog):\n` +
                   `- type: 오늘목표\n` +
                   `- context: 수업 중반\n` +
                   `- 포모도로 기법 3회 실행\n` +
                   `- memory: "수학 문제 풀이 집중도 향상"\n\n` +
                   `오늘 계획하신 활동을 구체적으로 말씀해주시면 더 자세히 도와드리겠습니다.`;
        }
    } else if (currentCategory === 'realtime' && categoryData) {
        if (lowerMessage.includes('실시간') || lowerMessage.includes('모니터링')) {
            return `실시간 학습 상태를 모니터링하고 있습니다.\n\n` +
                   `🔴 실시간 데이터:\n` +
                   `- 현재 활동: 포모도로 진행중\n` +
                   `- 집중도: 양호\n` +
                   `- context: 수업 중반\n` +
                   `- 최근 기록: firsttalk (30분 전)\n\n` +
                   `학습 패턴을 실시간으로 분석하여 최적의 학습 환경을 제공하고 있습니다.`;
        }
    } else if (currentCategory === 'interaction' && categoryData) {
        if (lowerMessage.includes('상호작용') || lowerMessage.includes('피드백')) {
            return `상호작용 기록을 확인했습니다.\n\n` +
                   `💬 최근 상호작용 (mdl_alt42_contextlog):\n` +
                   `- type: personaltalk (개인 상담)\n` +
                   `- type: journaling (학습 일지)\n` +
                   `- feedback: "수학 개념 이해도 향상중"\n\n` +
                   `선생님과의 상호작용 기록이 학습 개선에 도움이 되고 있습니다.`;
        }
    } else if (currentCategory === 'bias' && categoryData) {
        if (lowerMessage.includes('인지') || lowerMessage.includes('문제')) {
            return `인지관성 개선을 위한 분석 결과입니다.\n\n` +
                   `🧠 인지 패턴 분석:\n` +
                   `- 주요 오류 패턴: 계산 실수 반복\n` +
                   `- 개선 전략: 단계별 검산 습관화\n` +
                   `- memory: "같은 유형 실수 3회 반복"\n` +
                   `- 추천: 문제풀이 과정 세분화\n\n` +
                   `인지관성을 극복하기 위한 맞춤형 전략을 제공해드릴 수 있습니다.`;
        }
    } else if (currentCategory === 'consultation' && categoryData) {
        if (lowerMessage.includes('상담') || lowerMessage.includes('학생')) {
            return `상담 관리 시스템에서 데이터를 조회했습니다.\n\n` +
                   `🤝 상담 기록 (mdl_alt42_contextlog):\n` +
                   `- type: personaltalk (최근 3건)\n` +
                   `- 신규 상담: 고2 학생 2명\n` +
                   `- 정기 상담: 이번 주 15건 완료\n` +
                   `- feedback: "수학 성적 상승 추세"\n\n` +
                   `어떤 유형의 상담 정보가 필요하신가요?\n` +
                   `- 신규학생 상담\n` +
                   `- 정기상담 일정\n` +
                   `- 상황맞춤 상담`;
        }
    }
    
    // 현재 탭 관련 응답
    if (currentTabId) {
        const currentTabData = categoryData?.tabs.find(tab => tab.id === currentTabId);
        if (currentTabData) {
            return `"${currentTabData.title}"에 대한 질문이시군요.\n\n` +
                   `이 기능은 ${currentTabData.description}를 도와드립니다.\n` +
                   `구체적으로 어떤 부분이 궁금하신가요?`;
        }
    }
    
    // 기본 응답
    return `질문을 이해했습니다. 현재 ${categoryData?.title || '선택된 메뉴'}와 관련된 학습 데이터를 분석하고 있습니다.\n\n` +
           `더 구체적인 정보를 원하시면:\n` +
           `- "오늘 학습 기록 보여줘"\n` +
           `- "이번 주 목표 달성률은?"\n` +
           `- "최근 피드백 내용 확인"\n` +
           `등으로 질문해주세요.`;
}

// ==================== 검색 기능 ====================
function initializeSearch() {
    const searchInput = document.getElementById('searchInput');
    searchInput.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const categories = document.querySelectorAll('.menu-category');
        
        categories.forEach(category => {
            const title = category.querySelector('.category-title').textContent.toLowerCase();
            if (title.includes(searchTerm)) {
                category.style.display = 'block';
            } else {
                category.style.display = searchTerm === '' ? 'block' : 'none';
            }
        });
    });
}

// 삭제된 카드 목록 저장
function saveDeletedCards() {
    const userId = window.ktmPluginClient?.currentUserId || 'default';
    const storageKey = `ktm_deleted_cards_${userId}`;
    localStorage.setItem(storageKey, JSON.stringify(Array.from(deletedDefaultCards)));
    updateFloatingRestoreButton();
}

// 삭제된 카드 목록 로드
function loadDeletedCards() {
    const userId = window.ktmPluginClient?.currentUserId || 'default';
    const storageKey = `ktm_deleted_cards_${userId}`;
    const saved = localStorage.getItem(storageKey);
    if (saved) {
        try {
            const cards = JSON.parse(saved);
            deletedDefaultCards = new Set(cards);
        } catch (e) {
            console.error('삭제된 카드 목록 로드 실패:', e);
        }
    }
    updateFloatingRestoreButton();
}

// 우측 하단 고정 복원 버튼 업데이트
function updateFloatingRestoreButton() {
    const floatingBtn = document.getElementById('floatingRestoreBtn');
    const countSpan = document.getElementById('restoreCount');
    
    if (floatingBtn && countSpan) {
        if (deletedDefaultCards.size > 0) {
            floatingBtn.style.display = 'flex';
            countSpan.textContent = deletedDefaultCards.size;
        } else {
            floatingBtn.style.display = 'none';
        }
    }
}

// 삭제된 카드 복원 기능
function showRestoreDeletedCards() {
    if (deletedDefaultCards.size === 0) {
        alert('삭제된 카드가 없습니다.');
        return;
    }
    
    const modal = document.createElement('div');
    modal.className = 'add-card-modal';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>삭제된 카드 복원</h3>
                <button class="modal-close" onclick="closeModal()">×</button>
            </div>
            <div class="modal-body">
                <p>복원할 카드를 선택하세요:</p>
                <div class="deleted-cards-list">
                    ${Array.from(deletedDefaultCards).map(cardKey => {
                        const [category, tabTitle, cardTitle] = cardKey.split('_');
                        return `
                            <div class="deleted-card-item">
                                <span>${cardTitle} (${category} > ${tabTitle})</span>
                                <button class="btn-primary" onclick="restoreDefaultCard('${cardKey}')">복원</button>
                            </div>
                        `;
                    }).join('')}
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

// 기본 카드 복원
async function restoreDefaultCard(cardKey) {
    deletedDefaultCards.delete(cardKey);
    saveDeletedCards();
    closeModal();
    
    // 현재 탭 새로고침
    if (currentTab) {
        if (currentCategory === 'viral') {
            // 바이럴 마케팅의 경우 현재 플랫폼 탭 새로고침
            showViralMarketingInterface();
        } else {
            await showSubmenuItems(currentTab);
        }
    }
    
    alert('카드가 복원되었습니다.');
}

// ==================== 초기화 ====================
document.addEventListener('DOMContentLoaded', async function() {
    console.log('=== 페이지 초기화 시작 ===');
    
    // 플러그인 클라이언트 확인
    if (!window.ktmPluginClient) {
        console.error('KTM Plugin Client not initialized!');
        // 클라이언트가 없으면 재시도
        window.ktmPluginClient = new KTMPluginSettingsClient('plugin_settings_api_real.php');
    }
    console.log('Plugin Client User ID:', window.ktmPluginClient.currentUserId);
    
    // 온보딩 카드들을 자동으로 메뉴에 추가 - 비활성화됨
    // 하드코딩된 값들이 자동으로 DB에 추가되는 것을 방지하기 위해 주석 처리
    // setTimeout(async () => {
    //     await autoAddOnboardingCardsToMenu();
    // }, 2000); // 플러그인 시스템이 완전히 초기화된 후 실행
    
    // 기본 카드 타입 매핑 로드
    const savedMappings = localStorage.getItem('defaultCardTypeMappings');
    if (savedMappings) {
        window.defaultCardTypeMappings = JSON.parse(savedMappings);
        console.log('기본 카드 타입 매핑 로드:', window.defaultCardTypeMappings);
    }
    
    initializeSearch();
    
    // URL 파라미터로부터 상태 복원 시도
    restoreFromUrlParams();
    
    // 삭제된 카드 목록 로드
    loadDeletedCards();
    
    // 저장된 상태 복원 시도
    restoreState();
    
    console.log('=== 페이지 초기화 완료 ===');
    
    // 페이지 로드 완료 후 복원 버튼 상태 업데이트
    setTimeout(() => {
        updateFloatingRestoreButton();
    }, 100);
    
    // 기본 모드 설정 (상태 복원이 없으면)
    if (!currentMode) {
        switchMode('onboarding');
    }
    
    // Enter 키로 메시지 전송
    document.getElementById('messageInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });
    
    console.log('교육 AI 시스템이 초기화되었습니다.');
});

// ==================== 이전 메뉴 버튼 기능 ====================
function addBackButton(text, callback) {
    const chatContainer = document.getElementById('chatContainer');
    const backButtonContainer = document.createElement('div');
    backButtonContainer.className = 'back-button-container';
    backButtonContainer.innerHTML = `
        <button class="onboarding-back-button" onclick="this.clickHandler()">${text}</button>
    `;
    
    // 클릭 핸들러 설정
    const button = backButtonContainer.querySelector('.onboarding-back-button');
    button.clickHandler = callback;
    
    chatContainer.appendChild(backButtonContainer);
    chatContainer.scrollTop = chatContainer.scrollHeight;
}

// ==================== 플러그인 관리 함수 ====================
function showAddPluginMenu() {
    // 에이전트 플러그인 설정 모달 바로 열기
    openPluginSettings('agent');
}


function closeModal() {
    const modal = document.querySelector('.add-card-modal');
    if (modal) modal.remove();
}

function addPlugin(pluginId) {
    const plugin = pluginTypes.find(p => p.id === pluginId);
    if (plugin) {
        closeModal();
        
        if (pluginId === 'default_card') {
            // 기본 카드 선택 모달 표시
            showDefaultCardsSelection();
        } else {
            // 플러그인 설정 모달 바로 열기
            openPluginSettings(pluginId);
        }
    }
}

function showDefaultCardsSelection() {
    console.log('showDefaultCardsSelection called');
    console.log('currentCategory:', currentCategory);
    console.log('currentTab:', currentTab);
    
    const defaultCards = getDefaultCards();
    console.log('defaultCards:', defaultCards);
    
    const modal = document.createElement('div');
    modal.className = 'add-card-modal';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>기본 카드 선택</h3>
                <button class="modal-close" onclick="closeModal()">×</button>
            </div>
            <div class="modal-body">
                ${defaultCards.length > 0 ? `
                    <p>추가할 기본 카드를 선택하세요:</p>
                    <div class="default-cards-grid">
                        ${defaultCards.map((card, index) => `
                            <div class="default-card-option" onclick="addDefaultCard('${card.title.replace(/'/g, "\\'")}', '${card.description.replace(/'/g, "\\'")}', '${(card.link || '').replace(/'/g, "\\'")}')">
                                <h4>${card.title}</h4>
                                <p>${card.description}</p>
                            </div>
                        `).join('')}
                    </div>
                ` : `
                    <p>현재 탭에 사용 가능한 기본 카드가 없습니다.</p>
                    <p style="color: #9ca3af; font-size: 14px; margin-top: 10px;">카테고리와 탭을 선택한 후 다시 시도해주세요.</p>
                    <p style="color: #666; font-size: 12px; margin-top: 5px;">디버그: category=${currentCategory}, tab=${JSON.stringify(currentTab)}</p>
                `}
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

function addDefaultCard(title, description, link) {
    closeModal();
    
    // 저장된 플러그인 타입 매핑 확인
    let pluginType = 'default_card';
    let config = {
        card_title: title,
        plugin_name: title,
        description: description,
        url: link || '',
        original_template_title: title  // 원본 템플릿 제목 저장
    };
    
    // 기본 카드 템플릿 매핑 확인
    const mappingKey = `${currentCategory}_${currentTab.title}_${title}`;
    if (window.defaultCardTypeMappings && window.defaultCardTypeMappings[mappingKey]) {
        const mapping = window.defaultCardTypeMappings[mappingKey];
        pluginType = mapping.pluginType;
        config = { ...config, ...mapping.pluginConfig };
    }
    
    // KTM 플러그인 클라이언트를 통해 저장
    if (window.ktmPluginClient && currentTab) {
        // 현재 탭의 카드 설정 가져오기 (카테고리와 탭 제목 모두 전달)
        const existingCards = window.ktmPluginClient.getCardSettings(currentCategory, currentTab.title) || [];
        const cardIndex = existingCards.length;
        
        console.log('addDefaultCard - existing cards:', existingCards);
        console.log('addDefaultCard - cardIndex:', cardIndex);
        console.log('addDefaultCard - currentTab:', currentTab);
        
        console.log('Saving card with parameters:', {
            category: currentCategory,
            tabTitle: currentTab.title,
            cardIndex: cardIndex,
            pluginType: pluginType,
            config: config,
            displayOrder: cardIndex
        });
        
        window.ktmPluginClient.saveCardSetting(
            currentCategory,
            currentTab.title,
            cardIndex,
            pluginType,  // 매핑된 플러그인 타입 사용
            config,
            cardIndex  // display_order도 cardIndex와 동일하게 설정
        ).then(result => {
            console.log('Save result:', result);
            console.log('Save success:', result.success);
            if (result.success) {
                // SweetAlert로 성공 메시지 표시 (3초)
                Swal.fire({
                    icon: 'success',
                    title: '카드 추가 완료',
                    text: `${title} 카드가 추가되었습니다.`,
                    timer: 3000,
                    timerProgressBar: true,
                    showConfirmButton: false,
                    position: 'top-end',
                    toast: true
                });
                
                // 새로운 카드만 화면에 추가
                const menuCardsGrid = document.getElementById('menuCardsGrid');
                if (menuCardsGrid) {
                    // 새로 추가된 카드의 HTML 생성
                    const plugin = pluginTypes.find(p => p.id === pluginType);
                    const icon = plugin ? plugin.icon : '📋';
                    const description = config.description || (plugin ? plugin.description : '기본 카드');
                    
                    const newCardHTML = `
                        <div class="menu-card plugin-card plugin-modified" 
                             onclick="executePluginAction('${pluginType}', ${JSON.stringify(config).replace(/"/g, '&quot;')})">
                            <button class="card-settings-btn" onclick="event.stopPropagation(); editPluginSettings('${result.id || Date.now()}', '${pluginType}', '${title}')">⚙️</button>
                            <button class="card-delete-btn" onclick="event.stopPropagation(); deletePluginCard('${currentCategory}', '${currentTab.title}', '${result.id}', ${cardIndex})">❌</button>
                            <div class="card-icon">${icon}</div>
                            <h4>${title}</h4>
                            <p class="card-description">${description}</p>
                            <div class="plugin-indicator">${plugin ? plugin.title : '플러그인'}</div>
                        </div>
                    `;
                    
                    // 플러그인 추가 버튼 찾기
                    const addPluginButton = menuCardsGrid.querySelector('.add-plugin-card');
                    if (addPluginButton) {
                        // 플러그인 추가 버튼 앞에 새 카드 삽입
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = newCardHTML;
                        const newCard = tempDiv.firstElementChild;
                        menuCardsGrid.insertBefore(newCard, addPluginButton);
                    } else {
                        // 플러그인 추가 버튼이 없으면 끝에 추가
                        menuCardsGrid.insertAdjacentHTML('beforeend', newCardHTML);
                    }
                } else {
                    // menuCardsGrid가 없으면 전체 새로고침
                    if (typeof currentTab === 'object' && currentTab.title) {
                        showSubmenuItems(currentTab);
                    }
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: '추가 실패',
                    text: '카드 추가에 실패했습니다.',
                    timer: 3000,
                    timerProgressBar: true,
                    position: 'top-end',
                    toast: true
                });
                console.error('Save failed:', result);
            }
        }).catch(error => {
            console.error('Save error:', error);
            Swal.fire({
                icon: 'error',
                title: '오류 발생',
                text: '카드 추가 중 오류가 발생했습니다.',
                timer: 3000,
                timerProgressBar: true,
                position: 'top-end',
                toast: true
            });
        });
    }
}

function deletePlugin(index) {
    userSelectedPlugins.splice(index, 1);
    // 현재 탭 새로고침
    const menuStructure = getMenuStructure();
    const categoryData = menuStructure[currentCategory];
    if (categoryData && categoryData.tabs.length > 0) {
        showSubmenuItems(categoryData.tabs[0]);
    }
}

function openPluginSettings(pluginId, customData = null) {
    const plugin = pluginTypes.find(p => p.id === pluginId);
    if (!plugin) return;
    
    const settingsModal = document.createElement('div');
    settingsModal.className = 'settings-modal';
    settingsModal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>${plugin.icon} ${plugin.title} 설정</h3>
                <button class="modal-close" onclick="closeSettingsModal()">×</button>
            </div>
            <div class="modal-body">
                <div class="settings-interface">
                    ${pluginId === 'default_card' && customData && customData.existingCard ? `
                        <div class="plugin-type-selector" style="margin-bottom: 20px; padding: 15px; background: #374151; border-radius: 8px;">
                            <label style="display: block; margin-bottom: 10px; color: #f9fafb; font-weight: bold;">
                                플러그인 타입 변경
                            </label>
                            <select class="form-control" id="pluginTypeSelector" onchange="changePluginType(this.value, ${JSON.stringify(customData).replace(/"/g, '&quot;')})">
                                <option value="default_card" selected>기본 카드</option>
                                <option value="internal_link">내부링크 열기</option>
                                <option value="external_link">외부링크 열기</option>
                                <option value="send_message">메시지 발송</option>
                                <option value="agent">에이전트</option>
                            </select>
                            <small style="display: block; margin-top: 8px; color: #9ca3af;">
                                다른 플러그인 타입으로 변경하면 해당 타입의 설정을 할 수 있습니다.
                            </small>
                        </div>
                    ` : ''}
                    <div id="pluginSettingsContainer">
                        ${getPluginSettingsInterface(pluginId, customData)}
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeSettingsModal()">취소</button>
                <button class="btn-primary" onclick="savePluginSettings('${pluginId}', ${customData ? JSON.stringify(customData).replace(/"/g, '&quot;') : 'null'})">${customData && customData.settingId ? '수정' : '저장'}</button>
                ${pluginId !== 'default_card' ? `
                    <button class="btn-info" onclick="saveAsDefaultCard('${pluginId}', ${customData ? JSON.stringify(customData).replace(/"/g, '&quot;') : 'null'})" style="background: #8b5cf6;">
                        기본 카드로 저장
                    </button>
                ` : ''}
            </div>
        </div>
    `;
    document.body.appendChild(settingsModal);
    
    // 편집 모드인 경우 기존 설정 채우기
    if (customData && customData.existingCard) {
        // 카드 제목 설정
        if (customData.title) {
            const titleInput = document.getElementById('pluginCardTitle');
            if (titleInput) {
                titleInput.value = customData.title;
            }
        }
        
        // 기존 설정이 있으면 폼에 채우기
        if (customData.existingConfig) {
            const config = customData.existingConfig;
            
            // 플러그인별로 설정 채우기
            if (pluginId === 'agent') {
                const fileNameInput = document.getElementById('agentFileName');
                if (fileNameInput) {
                    fileNameInput.value = config.agent_url || config.fileName || '';
                }
                
                
                const descInput = document.getElementById('agentDescription');
                if (descInput && config.description) descInput.value = config.description;
            } else if (pluginId === 'default_card') {
                const descInput = document.getElementById('cardDescription');
                if (descInput && config.description) descInput.value = config.description;
                
                const urlInput = document.getElementById('defaultCardUrl');
                if (urlInput) {
                    urlInput.value = config.url || config.file_path || '';
                }
                
                
                const detailsInput = document.getElementById('defaultCardDetails');
                if (detailsInput && config.details && Array.isArray(config.details)) {
                    detailsInput.value = config.details.join('\n');
                }
            } else if (pluginId === 'external_link' || pluginId === 'internal_link') {
                const urlInput = document.getElementById(pluginId === 'external_link' ? 'externalLinkUrl' : 'internalLinkUrl');
                if (urlInput) {
                    if (pluginId === 'internal_link') {
                        urlInput.value = config.internal_url || config.file_path || config.url || '';
                    } else {
                        urlInput.value = config.url || '';
                    }
                }
                
                
                const descInput = document.getElementById('linkDescription');
                if (descInput && config.description) descInput.value = config.description;
            } else if (pluginId === 'send_message') {
                const descInput = document.getElementById('messageDescription');
                if (descInput && config.description) descInput.value = config.description;
            }
        }
    }
}

function closeSettingsModal() {
    const modal = document.querySelector('.settings-modal');
    if (modal) modal.remove();
}

// 내부링크 실행 함수
function executeInternalLink() {
    // 현재 상태 저장
    saveCurrentState();
    
    // 예시: 학습 진도 페이지로 이동
    alert('내부 페이지로 이동합니다.');
    // 실제 구현 시: window.location.href = './progress.html';
}

function getPluginSettingsInterface(pluginId, customData) {
    // 각 플러그인 유형별 설정 인터페이스 반환
    const interfaces = {
        default_card: `
            <h4>기본 카드 설정</h4>
            <div class="form-group" style="background-color: #f5f5f5; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #e0e0e0;">
                <label style="font-weight: bold; display: block; margin-bottom: 8px;">카드 제목 <span style="color: red;">*</span></label>
                <input type="text" class="form-control" id="pluginCardTitle" placeholder="이 카드를 구분할 이름을 입력하세요 (필수)" required style="border: 2px solid #ff6b6b;">
                <small style="color: #666; font-style: italic; margin-top: 5px; display: block;">이 카드를 다른 카드와 구분하기 위한 고유한 이름을 입력하세요.</small>
            </div>
            <div class="form-group">
                <label>카드 설명</label>
                <textarea class="form-control" id="cardDescription" rows="2" placeholder="이 카드의 기능을 설명하세요"></textarea>
            </div>
            <div class="form-group">
                <label>링크 URL 또는 파일 경로</label>
                <input type="text" class="form-control" id="defaultCardUrl" placeholder="https://example.com 또는 ./files/document.pdf">
                <small style="color: #666;">클릭 시 열릴 URL 또는 파일 경로 (예: ./weekly/report.html, ../docs/guide.pdf)</small>
            </div>
            <div class="form-group">
                <label>상세 작업 내용 (선택사항)</label>
                <textarea class="form-control" id="defaultCardDetails" rows="3" placeholder="이 카드의 상세 작업 내용을 줄바꿈으로 구분하여 입력하세요"></textarea>
                <small style="color: #666;">각 작업을 한 줄씩 입력하세요</small>
            </div>
        `,
        internal_link: `
            <h4>내부링크 설정</h4>
            <div class="form-group" style="background-color: #f5f5f5; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #e0e0e0;">
                <label style="font-weight: bold; display: block; margin-bottom: 8px;">카드 제목 <span style="color: red;">*</span></label>
                <input type="text" class="form-control" id="pluginCardTitle" placeholder="이 카드를 구분할 이름을 입력하세요 (필수)" required style="border: 2px solid #ff6b6b;">
                <small style="color: #666; font-style: italic; margin-top: 5px; display: block;">이 카드를 다른 카드와 구분하기 위한 고유한 이름을 입력하세요.</small>
            </div>
            <div class="form-group">
                <label>내부 파일 경로</label>
                <input type="text" class="form-control" id="internalLinkUrl" placeholder="./dashboard.html 또는 ../reports/summary.php">
                <small style="color: #666;">프로젝트 내부 파일 경로 (상대 경로 또는 절대 경로)</small>
            </div>
            <div class="form-group">
                <label>카드 설명</label>
                <textarea class="form-control" id="linkDescription" rows="2" placeholder="이 링크의 기능을 설명하세요"></textarea>
            </div>
        `,
        external_link: `
            <h4>외부링크 설정</h4>
            <div class="form-group" style="background-color: #f5f5f5; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #e0e0e0;">
                <label style="font-weight: bold; display: block; margin-bottom: 8px;">카드 제목 <span style="color: red;">*</span></label>
                <input type="text" class="form-control" id="pluginCardTitle" placeholder="이 카드를 구분할 이름을 입력하세요 (필수)" required style="border: 2px solid #ff6b6b;">
                <small style="color: #666; font-style: italic; margin-top: 5px; display: block;">이 카드를 다른 카드와 구분하기 위한 고유한 이름을 입력하세요.</small>
            </div>
            <div class="form-group">
                <label>URL 주소</label>
                <input type="url" class="form-control" id="externalLinkUrl" placeholder="https://example.com">
            </div>
            <div class="form-group">
                <label>카드 설명</label>
                <textarea class="form-control" id="linkDescription" rows="2" placeholder="이 링크의 기능을 설명하세요"></textarea>
            </div>
        `,
        send_message: `
            <h4>메시지 발송 설정</h4>
            <div class="form-group" style="background-color: #f5f5f5; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #e0e0e0;">
                <label style="font-weight: bold; display: block; margin-bottom: 8px;">카드 제목 <span style="color: red;">*</span></label>
                <input type="text" class="form-control" id="pluginCardTitle" placeholder="이 카드를 구분할 이름을 입력하세요 (필수)" required style="border: 2px solid #ff6b6b;">
                <small style="color: #666; font-style: italic; margin-top: 5px; display: block;">이 카드를 다른 카드와 구분하기 위한 고유한 이름을 입력하세요.</small>
            </div>
            <div class="form-group">
                <label>카드 설명</label>
                <textarea class="form-control" id="messageDescription" rows="2" placeholder="이 메시지 발송 기능을 설명하세요"></textarea>
            </div>
        `,
        agent: `
            <h4>에이전트 설정</h4>
            <div class="form-group" style="background-color: #f5f5f5; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #e0e0e0;">
                <label style="font-weight: bold; display: block; margin-bottom: 8px;">카드 제목 <span style="color: red;">*</span></label>
                <input type="text" class="form-control" id="pluginCardTitle" placeholder="이 카드를 구분할 이름을 입력하세요 (필수)" required style="border: 2px solid #ff6b6b;">
                <small style="color: #666; font-style: italic; margin-top: 5px; display: block;">이 카드를 다른 카드와 구분하기 위한 고유한 이름을 입력하세요.</small>
            </div>
            <div class="form-group">
                <label>에이전트 링크주소</label>
                <input type="text" class="form-control" id="agentFileName" placeholder="https://example.com/agent.html 또는 ./agents/math_tutor.html">
                <small style="color: #666;">에이전트 URL 또는 파일 경로를 입력하세요</small>
            </div>
            <div class="form-group">
                <label>에이전트 설명</label>
                <textarea class="form-control" id="agentDescription" rows="2" placeholder="이 에이전트가 처리하는 작업을 설명하세요"></textarea>
            </div>
        `
    };
    
    return interfaces[pluginId] || '<p>플러그인 설정 인터페이스를 준비 중입니다.</p>';
}

// toggleScheduleFields 함수 제거됨 - 간소화된 플러그인 형식에서는 불필요

// 에이전트 파일 생성 함수
function createAgentFile(fileName, agentTitle) {
    console.log(`에이전트 파일 생성 요청: ${fileName} (${agentTitle})`);
    // 실제로는 서버 API를 통해 파일을 생성해야 하지만, 
    // 여기서는 로컬에서 직접 파일을 생성합니다.
}

// 플러그인 타입 변경 함수
function changePluginType(newPluginType, customData) {
    // 기존 모달을 닫지 않고 설정 인터페이스만 변경
    const settingsContainer = document.getElementById('pluginSettingsContainer');
    if (settingsContainer) {
        settingsContainer.innerHTML = getPluginSettingsInterface(newPluginType, customData);
        
        // 플러그인 ID 업데이트
        const saveButton = document.querySelector('.modal-footer .btn-primary');
        if (saveButton) {
            saveButton.setAttribute('onclick', `savePluginSettings('${newPluginType}', ${JSON.stringify(customData).replace(/"/g, '&quot;')})`);
        }
    }
}

// 기본 카드로 저장 함수
function saveAsDefaultCard(pluginId, customData) {
    const form = document.querySelector('.settings-interface form');
    if (!form) return;
    
    const formData = new FormData(form);
    const config = {};
    
    // 플러그인별 설정 수집
    switch(pluginId) {
        case 'internal_link':
            config.card_title = formData.get('plugin_name') || '내부 링크';
            config.plugin_name = formData.get('plugin_name') || '내부 링크';
            config.description = formData.get('plugin_description') || '내부 페이지로 이동';
            config.url = formData.get('internal_link_path') || '';
            break;
            
        case 'external_link':
            config.card_title = formData.get('plugin_name') || '외부 링크';
            config.plugin_name = formData.get('plugin_name') || '외부 링크';
            config.description = formData.get('plugin_description') || '외부 사이트로 이동';
            config.url = formData.get('external_link_url') || '';
            break;
            
        case 'send_message':
            config.card_title = formData.get('plugin_name') || '메시지 발송';
            config.plugin_name = formData.get('plugin_name') || '메시지 발송';
            config.description = formData.get('plugin_description') || '사용자에게 메시지 전송';
            config.message_content = formData.get('message_content') || '';
            break;
            
        case 'agent':
            const fileName = formData.get('agent_file_name') || 'sample_agent.html';
            config.card_title = formData.get('plugin_name') || '에이전트';
            config.plugin_name = formData.get('plugin_name') || '에이전트';
            config.description = formData.get('plugin_description') || '에이전트 실행';
            config.url = `https://mathking.kr/moodle/local/augmented_teacher/alt42/teacherhome/agents/${fileName}`;
            break;
    }
    
    // 기본 카드 목록에 추가
    if (!window.customDefaultCards) {
        window.customDefaultCards = [];
    }
    
    window.customDefaultCards.push(config);
    
    alert(`'${config.card_title}' 카드가 기본 카드로 저장되었습니다. 이제 플러그인 추가 시 기본 카드로 선택할 수 있습니다.`);
    closeSettingsModal();
}

// 기본 카드의 플러그인 타입을 수정하는 함수
function editDefaultCardType(title, description, link, details, currentPluginType, currentPluginConfig) {
    closeModal(); // 기본 카드 선택 모달 닫기
    
    // 플러그인 타입 선택 모달 생성
    const modal = document.createElement('div');
    modal.className = 'settings-modal';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>🔧 기본 카드 플러그인 타입 설정</h3>
                <button class="modal-close" onclick="closeSettingsModal()">×</button>
            </div>
            <div class="modal-body">
                <div class="card-info-display" style="background: #f0f0f0; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <h4 style="margin: 0 0 10px 0; color: #333;">${title}</h4>
                    <p style="margin: 0; color: #666;">${description}</p>
                </div>
                
                <div class="settings-interface">
                    <div class="form-group">
                        <label style="font-weight: bold; display: block; margin-bottom: 10px;">이 카드를 어떤 플러그인으로 실행하시겠습니까?</label>
                        <select class="form-control" id="defaultCardPluginType" onchange="onDefaultCardPluginTypeChange(this.value)">
                            <option value="default_card" ${currentPluginType === 'default_card' ? 'selected' : ''}>기본 카드 (단순 표시)</option>
                            <option value="internal_link" ${currentPluginType === 'internal_link' ? 'selected' : ''}>내부링크 열기</option>
                            <option value="external_link" ${currentPluginType === 'external_link' ? 'selected' : ''}>외부링크 열기</option>
                            <option value="send_message" ${currentPluginType === 'send_message' ? 'selected' : ''}>메시지 발송</option>
                            <option value="agent" ${currentPluginType === 'agent' ? 'selected' : ''}>에이전트</option>
                        </select>
                        <small style="color: #666; display: block; margin-top: 8px;">
                            선택한 플러그인 타입에 따라 카드 클릭 시 실행되는 동작이 결정됩니다.
                        </small>
                    </div>
                    
                    <div id="pluginTypeSettingsContainer" style="margin-top: 20px;">
                        <!-- 플러그인별 설정이 여기에 표시됩니다 -->
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeSettingsModal()">취소</button>
                <button class="btn-primary" onclick="saveDefaultCardTypeMapping('${title.replace(/'/g, "\\'")}', '${description.replace(/'/g, "\\'")}', '${link.replace(/'/g, "\\'")}', '${details.replace(/'/g, "\\'")}')">
                    저장하고 적용
                </button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
    
    // 현재 플러그인 타입에 맞는 설정 UI 표시
    window.currentDefaultCardConfig = currentPluginConfig || {};
    onDefaultCardPluginTypeChange(currentPluginType || 'default_card');
}

// 플러그인 타입 변경 시 설정 UI 업데이트
function onDefaultCardPluginTypeChange(pluginType) {
    const container = document.getElementById('pluginTypeSettingsContainer');
    if (!container) return;
    
    let settingsHTML = '';
    
    switch(pluginType) {
        case 'internal_link':
            settingsHTML = `
                <div class="form-group">
                    <label>내부 페이지 경로</label>
                    <input type="text" class="form-control" id="internalLinkPath" placeholder="/dashboard" 
                           value="${window.currentDefaultCardConfig.url || ''}">
                    <small style="color: #666;">플랫폼 내 페이지 경로를 입력하세요</small>
                </div>
            `;
            break;
            
        case 'external_link':
            settingsHTML = `
                <div class="form-group">
                    <label>외부 URL</label>
                    <input type="url" class="form-control" id="externalLinkUrl" placeholder="https://example.com" 
                           value="${window.currentDefaultCardConfig.url || ''}">
                    <small style="color: #666;">전체 URL을 입력하세요 (https:// 포함)</small>
                </div>
            `;
            break;
            
        case 'send_message':
            settingsHTML = `
                <div class="form-group">
                    <label>메시지 제목</label>
                    <input type="text" class="form-control" id="messageTitle" placeholder="메시지 제목" 
                           value="${window.currentDefaultCardConfig.messageTitle || ''}">
                </div>
                <div class="form-group">
                    <label>메시지 내용</label>
                    <textarea class="form-control" id="messageContent" rows="4" placeholder="발송할 메시지 내용">${window.currentDefaultCardConfig.messageContent || ''}</textarea>
                </div>
            `;
            break;
            
        case 'agent':
            settingsHTML = `
                <div class="form-group">
                    <label>에이전트 파일명</label>
                    <input type="text" class="form-control" id="agentFileName" placeholder="sample_agent.html" 
                           value="${window.currentDefaultCardConfig.fileName || ''}">
                    <small style="color: #666;">agents/ 폴더 내의 파일명</small>
                </div>
            `;
            break;
            
        default: // default_card
            settingsHTML = `
                <div class="form-group" style="padding: 20px; background: #f8f9fa; border-radius: 8px; text-align: center;">
                    <p style="margin: 0; color: #666;">기본 카드는 클릭 시 특별한 동작 없이 정보만 표시합니다.</p>
                </div>
            `;
    }
    
    container.innerHTML = settingsHTML;
}

// 기본 카드 타입 매핑 저장
function saveDefaultCardTypeMapping(title, description, link, details) {
    const pluginType = document.getElementById('defaultCardPluginType')?.value || 'default_card';
    let pluginConfig = {
        card_title: title,
        plugin_name: title,
        description: description
    };
    
    // 플러그인별 설정 수집
    switch(pluginType) {
        case 'internal_link':
            pluginConfig.url = document.getElementById('internalLinkPath')?.value || link;
            break;
            
        case 'external_link':
            pluginConfig.url = document.getElementById('externalLinkUrl')?.value || link;
            pluginConfig.target = '_blank';
            break;
            
        case 'send_message':
            pluginConfig.messageTitle = document.getElementById('messageTitle')?.value || title;
            pluginConfig.messageContent = document.getElementById('messageContent')?.value || description;
            break;
            
        case 'agent':
            const fileName = document.getElementById('agentFileName')?.value || 'sample_agent.html';
            pluginConfig.fileName = fileName;
            pluginConfig.url = `https://mathking.kr/moodle/local/augmented_teacher/alt42/teacherhome/agents/${fileName}`;
            break;
    }
    
    // 기본 카드 타입 매핑을 로컬 스토리지에 저장 (모든 사용자가 사용할 수 있도록)
    if (!window.defaultCardTypeMappings) {
        // 로컬 스토리지에서 기존 매핑 로드
        const savedMappings = localStorage.getItem('defaultCardTypeMappings');
        window.defaultCardTypeMappings = savedMappings ? JSON.parse(savedMappings) : {};
    }
    
    const mappingKey = `${currentCategory}_${currentTab.title}_${title}`;
    window.defaultCardTypeMappings[mappingKey] = {
        pluginType: pluginType,
        pluginConfig: pluginConfig
    };
    
    // 로컬 스토리지에 저장
    localStorage.setItem('defaultCardTypeMappings', JSON.stringify(window.defaultCardTypeMappings));
    
    // 서버에도 저장 (선택사항 - 나중에 구현 가능)
    if (window.ktmPluginClient) {
        // 기본 카드 템플릿 정보를 서버에 저장하는 로직 추가 가능
        console.log('기본 카드 템플릿 저장:', {
            category: currentCategory,
            tab: currentTab.title,
            cardTitle: title,
            pluginType: pluginType,
            pluginConfig: pluginConfig
        });
    }
    
    alert(`'${title}' 카드가 ${pluginTypes.find(p => p.id === pluginType)?.title || pluginType}(으)로 설정되었습니다.\n이제 모든 선생님이 이 카드를 추가할 때 자동으로 설정된 플러그인이 적용됩니다.`);
    closeSettingsModal();
    showDefaultCardsSelection();
}

function savePluginSettings(pluginIdOrItemTitle, customDataOrTabTitle) {
    // 파라미터 재정의: 호출 컨텍스트에 따라 다르게 처리
    let pluginId, cardTitle, itemTitle, tabTitle;
    let isEditMode = false;
    let settingId = null;
    let cardIndex = null;
    
    // customDataOrTabTitle이 문자열이면 contextModal에서 호출된 것
    if (typeof customDataOrTabTitle === 'string') {
        // contextModal에서 호출: savePluginSettings('${itemTitle}', '${tabTitle}')
        itemTitle = pluginIdOrItemTitle;
        tabTitle = customDataOrTabTitle;
        
        // 실제 플러그인 ID는 select에서 가져옴
        const pluginSelect = document.getElementById('pluginSelect');
        pluginId = pluginSelect ? pluginSelect.value : '';
        
        if (!pluginId) {
            alert('플러그인을 선택해주세요.');
            return;
        }
        
        // 카드 제목은 itemTitle을 사용
        cardTitle = itemTitle;
    } else {
        // 일반 플러그인 설정 모달에서 호출
        pluginId = pluginIdOrItemTitle;
        cardTitle = document.getElementById('pluginCardTitle')?.value;
        
        if (!cardTitle || cardTitle.trim() === '') {
            alert('카드 제목을 입력해주세요.');
            document.getElementById('pluginCardTitle')?.focus();
            return;
        }
        
        // 편집 모드 확인
        if (customDataOrTabTitle && customDataOrTabTitle.settingId) {
            isEditMode = true;
            settingId = customDataOrTabTitle.settingId;
            cardIndex = customDataOrTabTitle.cardIndex;
        }
    }
    
    // 플러그인 설정 저장을 위한 config 객체 생성
    let pluginConfig = {
        plugin_name: cardTitle.trim(),  // plugin_name으로 카드 제목 저장
        card_title: cardTitle.trim()
    };
    
    // 플러그인별 처리
    if (pluginId === 'default_card') {
        const description = document.getElementById('cardDescription')?.value;
        const url = document.getElementById('defaultCardUrl')?.value;
        const details = document.getElementById('defaultCardDetails')?.value;
        
        pluginConfig.description = description;
        pluginConfig.url = url || '';
        
        // 파일 경로도 지원
        if (url && !url.startsWith('http')) {
            pluginConfig.file_path = url;
        }
        
        
        // 상세 작업 내용을 배열로 변환 (줄바꿈으로 구분)
        if (details) {
            pluginConfig.details = details.split('\n').filter(line => line.trim());
        }
        
    } else if (pluginId === 'external_link') {
        const url = document.getElementById('externalLinkUrl')?.value;
        const description = document.getElementById('linkDescription')?.value;
        
        if (!url) {
            alert('URL을 입력해주세요.');
            return;
        }
        
        pluginConfig.url = url;
        pluginConfig.target = '_blank';
        pluginConfig.description = description;
        
    } else if (pluginId === 'internal_link') {
        const url = document.getElementById('internalLinkUrl')?.value;
        const description = document.getElementById('linkDescription')?.value;
        
        if (!url) {
            alert('파일 경로를 입력해주세요.');
            return;
        }
        
        // 내부 링크는 파일 경로로 저장
        pluginConfig.internal_url = url;
        pluginConfig.file_path = url;
        pluginConfig.description = description;
        
        
    } else if (pluginId === 'send_message') {
        const description = document.getElementById('messageDescription')?.value;
        pluginConfig.description = description;
        
    } else if (pluginId === 'agent') {
        const agentUrl = document.getElementById('agentFileName')?.value;
        const description = document.getElementById('agentDescription')?.value;
        
        if (!agentUrl) {
            alert('에이전트 링크주소를 입력해주세요.');
            return;
        }
        
        // agent_url 필드에 저장
        pluginConfig.agent_url = agentUrl;
        pluginConfig.description = description;
        
        
        // 기존 호환성을 위해 fileName도 저장
        if (!agentUrl.startsWith('http')) {
            pluginConfig.fileName = agentUrl;
        }
    }
    
    // 기본 카드인 경우 플러그인 타입 매핑도 업데이트
    if (customDataOrTabTitle && customDataOrTabTitle.existingCard) {
        // 데이터베이스에서 현재 카드의 설정을 가져와서 원본 템플릿 제목 확인
        let originalTemplateTitle = customDataOrTabTitle.title;
        
        if (window.ktmPluginClient) {
            const effectiveTabTitle = tabTitle || (currentTab ? currentTab.title : '');
            const currentCards = window.ktmPluginClient.getCardSettings(currentCategory, effectiveTabTitle) || [];
            const currentCard = currentCards.find(card => 
                (card.plugin_config && card.plugin_config.card_title === customDataOrTabTitle.title) ||
                card.card_title === customDataOrTabTitle.title
            );
            
            if (currentCard && currentCard.plugin_config && currentCard.plugin_config.original_template_title) {
                originalTemplateTitle = currentCard.plugin_config.original_template_title;
            }
        }
        
        // 기본 카드 타입 매핑 업데이트
        const effectiveTabTitle = tabTitle || (currentTab ? currentTab.title : '');
        const mappingKey = `${currentCategory}_${effectiveTabTitle}_${originalTemplateTitle}`;
        
        if (!window.defaultCardTypeMappings) {
            const savedMappings = localStorage.getItem('defaultCardTypeMappings');
            window.defaultCardTypeMappings = savedMappings ? JSON.parse(savedMappings) : {};
        }
        
        // 플러그인 타입이 변경되었는지 확인
        const currentPluginType = document.getElementById('pluginTypeSelector')?.value || pluginId;
        
        // 원본 템플릿 제목도 설정에 포함
        pluginConfig.original_template_title = originalTemplateTitle;
        
        window.defaultCardTypeMappings[mappingKey] = {
            pluginType: currentPluginType,
            pluginConfig: pluginConfig
        };
        
        // 로컬 스토리지에 저장
        localStorage.setItem('defaultCardTypeMappings', JSON.stringify(window.defaultCardTypeMappings));
        
        console.log('기본 카드 매핑 업데이트:', {
            mappingKey: mappingKey,
            originalTemplateTitle: originalTemplateTitle,
            pluginType: currentPluginType,
            pluginConfig: pluginConfig
        });
    }
    
    // KTM 플러그인 클라이언트를 통해 저장
    if (window.ktmPluginClient) {
        // 현재 탭의 제목을 card_title로 사용 (tabTitle이 없는 경우에만)
        const finalTabTitle = tabTitle || (currentTab ? currentTab.title : '');
        
        console.log('=== savePluginSettings Debug ===');
        console.log('tabTitle parameter:', tabTitle);
        console.log('currentTab:', currentTab);
        console.log('finalTabTitle:', finalTabTitle);
        console.log('currentCategory:', currentCategory);
        console.log('pluginId:', pluginId);
        console.log('cardTitle:', cardTitle);
        
        if (!finalTabTitle) {
            alert('탭 정보가 없습니다. 메뉴에서 탭을 선택한 후 다시 시도해주세요.');
            return;
        }
        
        // 편집 모드인 경우 기존 카드 인덱스 사용, 아니면 새 인덱스 생성
        let finalCardIndex;
        if (isEditMode && cardIndex !== null) {
            finalCardIndex = cardIndex;
        } else {
            // 새 카드는 마지막에 추가 - 특정 탭의 카드 설정만 가져옴
            const existingCards = window.ktmPluginClient.getCardSettings(currentCategory, finalTabTitle) || [];
            finalCardIndex = Array.isArray(existingCards) ? existingCards.length : Object.keys(existingCards).length;
        }
        
        window.ktmPluginClient.saveCardSetting(
            currentCategory,
            finalTabTitle,  // 탭 제목을 card_title로 사용
            finalCardIndex,
            pluginId,
            pluginConfig,
            finalCardIndex // display_order도 cardIndex와 동일하게
        ).then(result => {
            console.log('Save result:', result);
            if (result.success) {
                alert(`${cardTitle} 카드가 성공적으로 ${isEditMode ? '수정' : '저장'}되었습니다.`);
                closeSettingsModal();
                // 메뉴 인터페이스 새로고침
                if (currentMode === 'menu') {
                    if (currentCategory === 'viral') {
                        // 바이럴 마케팅의 경우 현재 플랫폼 탭 새로고침
                        showViralMarketingInterface();
                    } else {
                        showMenuInterface(currentCategory);
                    }
                }
            } else {
                console.error('Save failed:', result);
                alert('저장 중 오류가 발생했습니다: ' + (result.error || 'Unknown error'));
            }
        }).catch(error => {
            console.error('플러그인 저장 오류 상세:', error);
            console.error('Error stack:', error.stack);
            alert('저장 중 오류가 발생했습니다: ' + error.message);
        });
    } else {
        // 폴백: 로컬 저장
        if (!cardPluginSettings[currentCategory]) {
            cardPluginSettings[currentCategory] = {};
        }
        cardPluginSettings[currentCategory][cardTitle] = {
            pluginId: pluginId,
            config: pluginConfig
        };
        
        alert(`${cardTitle} 카드가 저장되었습니다.`);
        closeSettingsModal();
    }
}

// ==================== 상태 관리 및 네비게이션 ====================
function saveCurrentState() {
    const state = {
        category: currentCategory,
        mode: currentMode,
        tab: currentTab,
        item: currentItem,
        step: currentStep,
        scrollPosition: window.scrollY,
        timestamp: Date.now()
    };
    sessionStorage.setItem('navigationState', JSON.stringify(state));
}

function getCurrentState() {
    return {
        category: currentCategory,
        mode: currentMode,
        tab: currentTab,
        item: currentItem,
        step: currentStep
    };
}

function restoreState() {
    const savedState = sessionStorage.getItem('navigationState');
    if (savedState) {
        const state = JSON.parse(savedState);
        
        // 상태 복원
        if (state.category) {
            selectCategory(state.category);
        }
        if (state.mode) {
            switchMode(state.mode);
        }
        
        // 스크롤 위치 복원
        if (state.scrollPosition) {
            setTimeout(() => {
                window.scrollTo(0, state.scrollPosition);
            }, 100);
        }
        
        // 상태 초기화
        sessionStorage.removeItem('navigationState');
    }
}

// URL 파라미터로부터 상태 복원
function restoreFromUrlParams() {
    const urlParams = new URLSearchParams(window.location.search);
    const returnState = urlParams.get('returnState');
    const userId = urlParams.get('userid');
    
    // userid가 있으면 플러그인 설정 클라이언트에 설정
    if (userId && window.ktmPluginSettings) {
        window.ktmPluginSettings.setUserId(parseInt(userId));
        console.log('User ID set from URL:', userId);
    }
    
    if (returnState) {
        try {
            const state = JSON.parse(decodeURIComponent(returnState));
            
            // 상태 복원
            if (state.category) {
                currentCategory = state.category;
                selectCategory(state.category);
            }
            if (state.mode) {
                currentMode = state.mode;
                switchMode(state.mode);
            }
            if (state.tab) {
                currentTab = state.tab;
            }
            if (state.item) {
                currentItem = state.item;
            }
            if (state.step) {
                currentStep = state.step;
            }
            
            // URL 파라미터 제거
            window.history.replaceState({}, document.title, window.location.pathname);
        } catch (e) {
            console.error('상태 복원 오류:', e);
        }
    }
}

// ==================== 사용자 문맥정보 관리 ====================
function openContextSettings(itemTitle, tabTitle) {
    const contextModal = document.createElement('div');
    contextModal.className = 'context-modal';
    contextModal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>📋 ${itemTitle} - 플러그인 설정</h3>
                <button class="modal-close" onclick="closeContextModal()">×</button>
            </div>
            <div class="modal-body">
                <div class="plugin-settings-container">
                    <div class="form-group">
                        <label>플러그인 선택</label>
                        <select class="form-control" id="pluginSelect" onchange="showPluginSettings()">
                            <option value="">플러그인을 선택하세요</option>
                            ${pluginTypes.map(plugin => `
                                <option value="${plugin.id}">${plugin.icon} ${plugin.title}</option>
                            `).join('')}
                        </select>
                    </div>
                    
                    <div class="plugin-settings-ui" id="pluginSettingsUI" style="display: none;">
                        <!-- 플러그인 설정 UI가 여기에 표시됩니다 -->
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeContextModal()">취소</button>
                <button class="btn-primary" onclick="savePluginSettings('${itemTitle}', '${tabTitle}')">저장</button>
            </div>
        </div>
    `;
    document.body.appendChild(contextModal);
}

// 이전 복잡한 폼 시스템은 제거되었습니다. 이제 단순한 플러그인 선택만 지원합니다.

function showPluginSettings() {
    const pluginSelect = document.getElementById('pluginSelect');
    const pluginSettingsUI = document.getElementById('pluginSettingsUI');
    const selectedPluginId = pluginSelect.value;
    
    if (!selectedPluginId) {
        pluginSettingsUI.style.display = 'none';
        return;
    }
    
    // 플러그인별 설정 UI 표시
    const settingsHTML = getPluginSettingsInterface(selectedPluginId);
    pluginSettingsUI.innerHTML = settingsHTML;
    pluginSettingsUI.style.display = 'block';
}

function closeContextModal() {
    const modal = document.querySelector('.context-modal');
    if (modal) modal.remove();
}

function saveContextSettings(itemTitle, tabTitle) {
    // 컨텍스트 설정 저장
    const pluginSelect = document.getElementById('pluginSelect');
    const selectedPluginId = pluginSelect ? pluginSelect.value : '';
    
    if (selectedPluginId) {
        const cardKey = `${currentCategory}_${tabTitle}_${itemTitle}`;
        cardPluginSettings[cardKey] = {
            pluginId: selectedPluginId,
            timestamp: Date.now()
        };
        
        alert(`${itemTitle}에 플러그인이 적용되었습니다.`);
        closeContextModal();
        
        // 현재 탭 새로고침
        const menuStructure = getMenuStructure();
        const categoryData = menuStructure[currentCategory];
        if (categoryData) {
            const currentTabData = categoryData.tabs.find(tab => tab.title === tabTitle);
            if (currentTabData) {
                showSubmenuItems(currentTabData);
            }
        }
    } else {
        // 플러그인 선택 없이도 저장 가능하도록 변경
        closeContextModal();
    }
}

// ==================== 퍼스널 브랜딩 데이터 및 인터페이스 ====================
function getViralMarketingData() {
    return {
        title: '바이럴 마케팅',
        description: '바이럴 콘텐츠 제작 및 소셜미디어 마케팅 전략',
        tabs: [
            {
                id: 'blog',
                title: '블로그',
                description: '바이럴 블로그 콘텐츠 제작 및 SEO 전략',
                items: []
            },
            {
                id: 'youtube',
                title: '유튜브',
                description: '바이럴 유튜브 콘텐츠 제작 및 채널 성장',
                items: []
            },
            {
                id: 'instagram',
                title: '인스타',
                description: '인스타그램 바이럴 마케팅 전략',
                items: []
            },
            {
                id: 'x',
                title: 'X (Twitter)',
                description: 'X 플랫폼 바이럴 마케팅',
                items: []
            },
            {
                id: 'threads',
                title: 'Threads',
                description: 'Threads 바이럴 전략',
                items: []
            }
        ]
    };
}

// 상담관리 데이터
function getConsultationData() {
    return {
        title: '상담관리',
        description: '학생 및 학부모 상담을 체계적으로 관리하고 기록합니다',
        tabs: [
            {
                id: 'new_student',
                title: '신규학생',
                description: '신규 학생 상담 및 레벨 테스트',
                items: [
                    { title: '초등학생', description: '초등학생 신규 상담', details: ['학습 수준 파악', '학부모 상담', '학습 목표 설정', '수업 계획 수립'] },
                    { title: '중학생', description: '중학생 신규 상담', details: ['현재 성적 분석', '학습 습관 점검', '목표 설정', '커리큘럼 제안'] },
                    { title: '예비고', description: '예비 고등학생 상담', details: ['중학 내신 분석', '고등 과정 안내', '학습 전략 수립', '진로 상담'] },
                    { title: '고1', description: '고등학교 1학년 상담', details: ['내신 관리 전략', '수능 기초 안내', '학습 계획 수립', '진로 탐색'] },
                    { title: '고2', description: '고등학교 2학년 상담', details: ['문/이과 선택 상담', '내신 심화 전략', '수능 준비 계획', '대입 로드맵'] },
                    { title: '고3', description: '고등학교 3학년 상담', details: ['수능 집중 전략', '수시/정시 상담', '실전 대비 계획', '멘탈 관리'] }
                ]
            },
            {
                id: 'regular_consult',
                title: '정기상담',
                description: '재원생 정기 상담 및 학습 점검',
                items: [
                    { title: '초등학생', description: '초등학생 정기 상담', details: ['학습 진도 점검', '학습 태도 개선', '학부모 피드백', '다음 목표 설정'] },
                    { title: '중학생', description: '중학생 정기 상담', details: ['성적 변화 분석', '학습 방법 개선', '시험 대비 전략', '진로 구체화'] },
                    { title: '예비고', description: '예비고 정기 상담', details: ['고등 준비 상황', '선행 학습 점검', '학습 습관 강화', '목표 재설정'] },
                    { title: '고1', description: '고1 정기 상담', details: ['내신 성적 분석', '학습 패턴 점검', '약점 보완 계획', '수능 기초 점검'] },
                    { title: '고2', description: '고2 정기 상담', details: ['내신 관리 점검', '수능 준비 상황', '모의고사 분석', '입시 전략 수립'] },
                    { title: '고3', description: '고3 정기 상담', details: ['수능 준비 점검', '실전 연습 분석', '입시 일정 관리', '컨디션 관리'] }
                ]
            },
            {
                id: 'exam_consult',
                title: '시험관련',
                description: '시험 관련 특별 상담 및 관리',
                items: [
                    { title: '시험대비 안내', description: '시험 전 준비사항 안내', details: ['시험 범위 확인', '학습 계획 점검', '취약 단원 집중', '시험 전략 안내'] },
                    { title: '시험 마무리 상담', description: '시험 직전 최종 점검', details: ['핵심 내용 정리', '실수 방지 전략', '시간 관리 팁', '멘탈 관리'] },
                    { title: '시험결과 상담', description: '시험 후 결과 분석', details: ['성적 분석', '오답 원인 파악', '개선점 도출', '다음 계획 수립'] }
                ]
            },
            {
                id: 'situation_consult',
                title: '상황맞춤 상담',
                description: '특별한 상황에 맞춘 맞춤형 상담',
                items: [
                    { title: '입시상담', description: '대학 입시 전략 상담', details: ['수시/정시 전략', '대학별 전형 분석', '학생부 관리', '자소서 컨설팅'] },
                    { title: '스마트폰 관련', description: '스마트폰 사용 관련 상담', details: ['사용 시간 관리', '학습 앱 활용', '집중력 향상 방법', '디지털 디톡스'] }
                ]
            },
            {
                id: 'case_skillup',
                title: '사례청취 및 스킬업',
                description: '상담 사례 분석과 전문성 향상',
                items: []
            },
            {
                id: 'parent_persona',
                title: '학부모 페르소나',
                description: '학부모 유형별 맞춤 대응 전략',
                items: []
            },
            {
                id: 'student_persona',
                title: '학생 페르소나',
                description: '학생 유형별 맞춤 학습 전략',
                items: []
            }
        ]
    };
}

function showViralMarketingInterface() {
    const menuTabGrid = document.getElementById('menuTabGrid');
    const submenuContainer = document.getElementById('submenuContainer');
    
    // 현재 카테고리 설정
    currentCategory = 'viral';
    
    // 바이럴 마케팅 전용 인터페이스
    menuTabGrid.innerHTML = `
        <div class="viral-interface">
            <div class="viral-header">
                <h2>💰 바이럴 마케팅 전문가</h2>
                <p class="menu-description">바이럴 콘텐츠 제작 및 소셜미디어 마케팅 전략</p>
            </div>
            
            <!-- 플랫폼 탭 -->
            <div class="platform-tabs">
                <button class="platform-tab active" onclick="selectPlatformTab('blog')">
                    📝 블로그
                </button>
                <button class="platform-tab" onclick="selectPlatformTab('youtube')">
                    📺 유튜브
                </button>
                <button class="platform-tab" onclick="selectPlatformTab('instagram')">
                    📷 인스타
                </button>
                <button class="platform-tab" onclick="selectPlatformTab('x')">
                    🐦 X
                </button>
                <button class="platform-tab" onclick="selectPlatformTab('threads')">
                    🧵 Threads
                </button>
            </div>
        </div>
    `;
    
    // 기본으로 블로그 탭 표시
    selectPlatformTab('blog');
}

function selectPlatformTab(platform) {
    // 탭 활성화 상태 업데이트
    document.querySelectorAll('.platform-tab').forEach(tab => tab.classList.remove('active'));
    if (event && event.target) {
        event.target.classList.add('active');
    }
    
    // 현재 카테고리를 바이럴로 설정
    currentCategory = 'viral';
    
    // 플랫폼별 카드 표시
    showPlatformCards(platform);
}


async function showPlatformCards(platform) {
    const submenuContainer = document.getElementById('submenuContainer');
    const viralData = getViralMarketingData();
    const platformData = viralData.tabs.find(tab => tab.id === platform);
    
    if (!platformData) return;
    
    // 현재 탭 정보 저장 (바이럴 마케팅용)
    currentTab = platformData;
    
    // 로딩 표시
    submenuContainer.innerHTML = '<div class="loading">플러그인 설정을 불러오는 중...</div>';
    
    // 데이터베이스에서 카드 설정 로드
    let savedCardSettings = [];
    if (window.ktmPluginClient) {
        try {
            // 카테고리와 탭 제목으로 로드
            await window.ktmPluginClient.loadCardSettings('viral', platformData.title);
            const tabSettings = window.ktmPluginClient.getCardSettings('viral', platformData.title) || [];
            
            // 현재 탭에 해당하는 설정만 사용
            if (Array.isArray(tabSettings)) {
                savedCardSettings = tabSettings;
            }
            
            console.log(`Loaded card settings for viral/${platformData.title}:`, savedCardSettings);
        } catch (error) {
            console.error('카드 설정 로드 실패:', error);
        }
    }
    
    submenuContainer.innerHTML = `
        <div class="platform-section">
            <h3>${platformData.title} 관리</h3>
            <p class="platform-description">${platformData.description}</p>
            
            <div class="menu-cards-container">
                <div class="menu-cards-grid">
                    <!-- 데이터베이스에서 로드한 플러그인 카드들 -->
                    ${savedCardSettings
                        .map(cardSetting => {
                            const config = cardSetting.plugin_config || {};
                            // 원본 플러그인 ID를 사용하여 플러그인 타입 찾기
                            const originalPluginId = config.original_plugin_id || cardSetting.plugin_id.split('_')[0];
                            const plugin = pluginTypes.find(p => p.id === originalPluginId);
                            const cardTitle = config.plugin_name || config.card_title || cardSetting.card_title || '제목 없음';
                            return `
                                <div class="menu-card plugin-card plugin-modified" 
                                     onclick="executePluginAction('${originalPluginId}', ${JSON.stringify(config).replace(/"/g, '&quot;')})">
                                    <button class="card-settings-btn" onclick="event.stopPropagation(); editPluginSettings('${cardSetting.id}', '${cardSetting.plugin_id}', '${cardTitle}')">⚙️</button>
                                    <button class="card-delete-btn" onclick="event.stopPropagation(); deletePluginCard('viral', '${platformData.title}', '${cardSetting.id}', '${cardSetting.card_index}')">❌</button>
                                    <div class="card-icon">${plugin ? plugin.icon : '🔌'}</div>
                                    <h4>${cardTitle}</h4>
                                    <p class="card-description">${plugin ? plugin.description : '사용자 정의 플러그인'}</p>
                                    <div class="plugin-indicator">${plugin ? plugin.title : '플러그인'}</div>
                                </div>
                            `;
                        }).join('')}
                    
                    <!-- 플러그인 추가 카드 -->
                    <div class="menu-card add-card" onclick="showAddPluginMenu()">
                        <div class="add-icon">+</div>
                        <p>플러그인 추가</p>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function executePlatformAction(actionTitle, platform) {
    // 현재 상태 저장
    saveCurrentState();
    
    const submenuContainer = document.getElementById('submenuContainer');
    
    // 진행 상황 표시 영역 생성
    const progressId = `platformProgress_${Date.now()}`;
    const progressArea = document.createElement('div');
    progressArea.className = 'menu-progress-area';
    progressArea.innerHTML = `
        <h3>🚀 ${actionTitle} 실행 중...</h3>
        <div class="progress-messages" id="${progressId}"></div>
    `;
    
    submenuContainer.appendChild(progressArea);
    
    const progressMessages = document.getElementById(progressId);
    
    setTimeout(() => {
        addProgressMessage(progressMessages, `${actionTitle} 실행을 시작합니다...`);
    }, 500);
    
    // 플랫폼별 액션 데이터 찾기
    const viralData = getViralMarketingData();
    const platformData = viralData.tabs.find(tab => tab.id === platform);
    const actionData = platformData.items.find(item => item.title === actionTitle);
    
    if (actionData) {
        // 세부 작업들 순차 실행
        actionData.details.forEach((detail, index) => {
            setTimeout(() => {
                addProgressMessage(progressMessages, `✓ ${detail} - 완료`);
            }, 1500 + (index * 800));
        });
        
        // 완료 메시지
        setTimeout(() => {
            addProgressMessage(progressMessages, `🎉 ${actionTitle} 실행이 완료되었습니다!`);
        }, 1500 + (actionData.details.length * 800) + 1000);
    }
}

// ==================== 네비게이션 함수 ====================
function goToHome() {
    // 현재 상태 저장
    saveCurrentState();
    
    // URL에서 userid 가져오기
    const urlParams = new URLSearchParams(window.location.search);
    const userId = urlParams.get('userid');
    
    // 학생 홈으로 이동 (userid 파라미터 유지)
    let targetUrl = 'https://mathking.kr/moodle/local/augmented_teacher/alt42/studenthome/index.php';
    if (userId) {
        targetUrl += '?userid=' + userId;
    }
    window.location.href = targetUrl;
}

// ==================== 채팅 팁 기능 ====================
function toggleChatTips() {
    const chatTipsPanel = document.getElementById('chatTipsPanel');
    const chatTipsOverlay = document.getElementById('chatTipsOverlay');
    const isActive = chatTipsPanel.classList.contains('active');
    
    if (isActive) {
        // 패널 닫기
        chatTipsPanel.classList.remove('active');
        chatTipsOverlay.classList.remove('active');
    } else {
        // 패널 열기
        chatTipsPanel.classList.add('active');
        chatTipsOverlay.classList.add('active');
    }
}

// ==================== 에이전트 패널 기능 ====================
function openAgentPanel(url, title) {
    const agentPanel = document.getElementById('agentPanel');
    const agentOverlay = document.getElementById('agentPanelOverlay');
    const agentIframe = document.getElementById('agentIframe');
    
    // 제목 업데이트 (적절한 아이콘 선택)
    const headerTitle = agentPanel.querySelector('.agent-panel-header h3');
    if (headerTitle) {
        // 플러그인 타입에 따라 아이콘 설정
        let icon = '📋';
        if (url.includes('agent')) {
            icon = '🤖';
        } else if (url.includes('.php') || url.includes('.html')) {
            icon = '📄';
        } else if (url.includes('http')) {
            icon = '🌐';
        }
        headerTitle.textContent = `${icon} ${title}`;
    }
    
    // iframe URL 설정
    agentIframe.src = url;
    
    // 패널 열기
    agentPanel.classList.add('active');
    agentOverlay.classList.add('active');
}

function closeAgentPanel() {
    const agentPanel = document.getElementById('agentPanel');
    const agentOverlay = document.getElementById('agentPanelOverlay');
    const agentIframe = document.getElementById('agentIframe');
    
    // 패널 닫기
    agentPanel.classList.remove('active');
    agentOverlay.classList.remove('active');
    
    // iframe 초기화
    setTimeout(() => {
        agentIframe.src = '';
    }, 300); // 애니메이션 완료 후 초기화
}

// 에이전트 iframe 슬라이딩 패널 열기 (agent_url 사용)
function openAgentPanelWithIframe(config) {
    const panel = document.getElementById('agentPanel');
    const iframe = document.getElementById('agentIframe');
    
    if (!panel || !iframe) {
        console.error('Agent panel or iframe not found');
        return;
    }
    
    // URL 처리 - 상대 경로인 경우 절대 경로로 변환
    let targetUrl = config.url;
    if (targetUrl && !targetUrl.startsWith('http') && !targetUrl.startsWith('/')) {
        const baseUrl = window.location.origin + window.location.pathname.replace(/[^/]*$/, '');
        targetUrl = baseUrl + targetUrl;
    }
    
    // 기존 openAgentPanel 함수 재사용
    openAgentPanel(targetUrl, config.title || '에이전트');
    
    // iframe 로드 에러 핸들링
    iframe.onerror = function() {
        console.error('Failed to load agent URL:', targetUrl);
        iframe.src = 'about:blank';
        setTimeout(() => {
            if (iframe.contentDocument && iframe.contentDocument.body) {
                iframe.contentDocument.body.innerHTML = `
                    <div style="padding: 20px; font-family: Arial, sans-serif;">
                        <h2 style="color: #d32f2f;">⚠️ 에이전트 로드 실패</h2>
                        <p>에이전트 URL을 로드할 수 없습니다.</p>
                        <p style="color: #666; font-size: 0.9em;">URL: ${targetUrl}</p>
                        <button onclick="parent.closeAgentPanel()" style="margin-top: 20px; padding: 10px 20px; background: #2196F3; color: white; border: none; border-radius: 4px; cursor: pointer;">닫기</button>
                    </div>
                `;
            }
        }, 100);
    };
}

// ==================== 슬라이딩 채팅 패널 기능 ====================
function openChatPanel() {
    const chatPanel = document.getElementById('slidingChatPanel');
    const chatOverlay = document.getElementById('chatPanelOverlay');
    
    chatPanel.classList.add('active');
    chatOverlay.classList.add('active');
    
    // 채팅 버튼 활성화 상태로 변경
    const chatButton = document.querySelector('[onclick="switchMode(\'chat\')"]');
    if (chatButton) chatButton.classList.add('active');
    
    // 초기 환영 메시지
    const messagesContainer = document.getElementById('chatPanelMessages');
    if (messagesContainer.children.length === 0) {
        addChatPanelMessage('ai', '안녕하세요! 무엇을 도와드릴까요?');
    }
}

// 에이전트와 함께 채팅 패널 열기
function openChatPanelWithAgent(agentData) {
    const chatPanel = document.getElementById('slidingChatPanel');
    const chatOverlay = document.getElementById('chatPanelOverlay');
    const chatHeader = chatPanel.querySelector('.chat-panel-header h3');
    
    // 헤더 업데이트
    if (chatHeader) {
        chatHeader.textContent = `🤖 ${agentData.title || 'AI 챗봇 대화'}`;
    }
    
    chatPanel.classList.add('active');
    chatOverlay.classList.add('active');
    
    // 채팅 버튼 활성화 상태로 변경
    const chatButton = document.querySelector('[onclick="switchMode(\'chat\')"]');
    if (chatButton) chatButton.classList.add('active');
    
    // 에이전트 데이터 저장
    chatPanel.agentData = agentData;
    
    // 채팅 메시지 초기화 및 에이전트 환영 메시지
    const messagesContainer = document.getElementById('chatPanelMessages');
    messagesContainer.innerHTML = ''; // 기존 메시지 초기화
    
    // 에이전트 소개 메시지
    addChatPanelMessage('ai', `안녕하세요! ${agentData.title}을(를) 도와드리는 AI 에이전트입니다.`);
    
    if (agentData.description) {
        addChatPanelMessage('ai', agentData.description);
    }
    
    if (agentData.config && agentData.config.details && agentData.config.details.length > 0) {
        const detailsMessage = '제가 도와드릴 수 있는 기능은 다음과 같습니다:\n' + 
                             agentData.config.details.map(detail => `• ${detail}`).join('\n');
        addChatPanelMessage('ai', detailsMessage);
    }
    
    addChatPanelMessage('ai', '무엇을 도와드릴까요?');
}

function closeChatPanel() {
    const chatPanel = document.getElementById('slidingChatPanel');
    const chatOverlay = document.getElementById('chatPanelOverlay');
    
    chatPanel.classList.remove('active');
    chatOverlay.classList.remove('active');
    
    // 채팅 버튼 비활성화 상태로 변경
    const chatButton = document.querySelector('[onclick="switchMode(\'chat\')"]');
    if (chatButton) chatButton.classList.remove('active');
    
    // 에이전트 데이터 초기화
    delete chatPanel.agentData;
    
    // 헤더 원래대로 복원
    const chatHeader = chatPanel.querySelector('.chat-panel-header h3');
    if (chatHeader) {
        chatHeader.textContent = '💬 AI 챗봇 대화';
    }
}

function addChatPanelMessage(sender, message) {
    const messagesContainer = document.getElementById('chatPanelMessages');
    const messageElement = document.createElement('div');
    messageElement.className = `chat-panel-message ${sender}`;
    
    const now = new Date();
    const time = now.toLocaleTimeString('ko-KR', { hour: '2-digit', minute: '2-digit' });
    
    messageElement.innerHTML = `
        <div>${message}</div>
        <div class="message-time">${time}</div>
    `;
    
    messagesContainer.appendChild(messageElement);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

function sendChatMessage() {
    const input = document.getElementById('chatPanelInput');
    const message = input.value.trim();
    const chatPanel = document.getElementById('slidingChatPanel');
    
    if (message) {
        // 사용자 메시지 추가
        addChatPanelMessage('user', message);
        
        // 입력 필드 초기화
        input.value = '';
        
        // AI 응답 시뮬레이션
        setTimeout(() => {
            let response;
            
            // 에이전트가 있는 경우 에이전트 컨텍스트에 맞는 응답
            if (chatPanel.agentData) {
                const agentData = chatPanel.agentData;
                response = generateAgentResponse(message, agentData);
            } else {
                // 일반 응답
                const responses = [
                    '네, 도와드리겠습니다!',
                    '좋은 질문이네요. 설명드리겠습니다.',
                    '관련 정보를 찾아보겠습니다.',
                    '더 자세한 정보가 필요하신가요?'
                ];
                response = responses[Math.floor(Math.random() * responses.length)];
            }
            
            addChatPanelMessage('ai', response);
        }, 1000);
    }
}

// 에이전트 응답 생성 함수
function generateAgentResponse(userMessage, agentData) {
    const lowerMessage = userMessage.toLowerCase();
    
    // 상담관리 고3 등 특정 에이전트 맞춤 응답
    if (agentData.title === '고3' && agentData.config?.category === 'consultation') {
        if (lowerMessage.includes('수능') || lowerMessage.includes('시험')) {
            return '수능 준비와 관련해서 도움이 필요하신가요? 수능 집중 전략, 과목별 학습 계획, 시간 관리 등 어떤 부분을 도와드릴까요?';
        } else if (lowerMessage.includes('수시') || lowerMessage.includes('정시')) {
            return '대학 입시 전략에 대해 상담해드리겠습니다. 학생의 현재 내신과 모의고사 성적을 알려주시면 맞춤형 입시 전략을 제안해드릴 수 있습니다.';
        } else if (lowerMessage.includes('스트레스') || lowerMessage.includes('멘탈') || lowerMessage.includes('걱정')) {
            return '고3 시기는 정신적으로 힘든 시기입니다. 스트레스 관리와 멘탈 케어 방법에 대해 도와드리겠습니다. 어떤 부분이 가장 힘드신가요?';
        }
    }
    
    // 간단한 키워드 기반 응답 (실제로는 더 복잡한 AI 로직 필요)
    if (lowerMessage.includes('시작') || lowerMessage.includes('시작해')) {
        return agentData.systemPrompt || `네, ${agentData.title} 기능을 시작하겠습니다. 어떤 부분부터 도와드릴까요?`;
    } else if (lowerMessage.includes('도움') || lowerMessage.includes('기능')) {
        const details = agentData.config?.details || [];
        if (details.length > 0) {
            return `${agentData.title} 전문 상담에서 도와드릴 수 있는 기능은 다음과 같습니다:\n\n` + 
                   details.map((detail, index) => `${index + 1}. ${detail}`).join('\n') + 
                   '\n\n어떤 부분에 대해 상담을 시작하시겠습니까?';
        }
        return `제가 도와드릴 수 있는 기능은 다음과 같습니다: ${details.join(', ') || '다양한 기능'}`;
    } else if (lowerMessage.includes('설명')) {
        return agentData.description || '이 기능에 대해 자세히 설명드리겠습니다.';
    } else {
        // 에이전트 URL이 있는 경우 더 구체적인 안내
        if (agentData.config?.agent_url) {
            return `${agentData.title} 전문 상담을 시작하겠습니다. 구체적인 상담 내용을 말씀해주시면 맞춤형 도움을 드리겠습니다.`;
        }
        return `네, 이해했습니다. "${userMessage}"에 대해 ${agentData.title} 관점에서 도와드리겠습니다. 구체적으로 어떤 도움이 필요하신가요?`;
    }
}

// ==================== 전역 함수 노출 ====================
window.selectCategory = selectCategory;
window.switchMode = switchMode;
window.sendMessage = sendMessage;
window.selectMenuTab = selectMenuTab;
// window.executeMenuAction 제거됨 - 모든 카드는 플러그인으로 처리
window.goToTestPage = goToTestPage;
window.showChatPreview = showChatPreview;
window.showAddPluginMenu = showAddPluginMenu;
window.closeModal = closeModal;
window.addPlugin = addPlugin;
window.deletePlugin = deletePlugin;
window.openPluginSettings = openPluginSettings;
window.closeSettingsModal = closeSettingsModal;
window.savePluginSettings = savePluginSettings;
window.deleteDefaultCard = deleteDefaultCard;
window.showRestoreDeletedCards = showRestoreDeletedCards;
window.restoreDefaultCard = restoreDefaultCard;
window.openCustomInterfaceForMenuItem = openCustomInterfaceForMenuItem;
window.openContextSettings = openContextSettings;
window.switchContextTab = switchContextTab;
window.closeContextModal = closeContextModal;
window.saveContextSettings = saveContextSettings;
window.saveCurrentState = saveCurrentState;
window.restoreState = restoreState;
window.goToHome = goToHome;
window.showViralMarketingInterface = showViralMarketingInterface;
window.selectPlatformTab = selectPlatformTab;
window.showPlatformCards = showPlatformCards;
window.executePlatformAction = executePlatformAction;
window.generateContextualResponse = generateContextualResponse;
window.toggleChatTips = toggleChatTips;
window.openChatPanel = openChatPanel;
window.openChatPanelWithAgent = openChatPanelWithAgent;
window.closeChatPanel = closeChatPanel;
window.sendChatMessage = sendChatMessage;
window.generateAgentResponse = generateAgentResponse;
window.openAgentPanel = openAgentPanel;
window.closeAgentPanel = closeAgentPanel;
window.openAgentPanelWithIframe = openAgentPanelWithIframe;
window.executePluginAction = executePluginAction;
window.openAgentPopup = openAgentPopup;
window.showMessagePopup = showMessagePopup;
window.showDefaultCardsSelection = showDefaultCardsSelection;
window.addDefaultCard = addDefaultCard;
window.changePluginType = changePluginType;
window.saveAsDefaultCard = saveAsDefaultCard;
window.editDefaultCardType = editDefaultCardType;
window.saveDefaultCardTypeMapping = saveDefaultCardTypeMapping;
window.onDefaultCardPluginTypeChange = onDefaultCardPluginTypeChange;
window.showBiasPatterns = showBiasPatterns;
window.showBiasPatternDetail = showBiasPatternDetail;

// 인지관성 패턴 표시 함수
function showBiasPatterns(tab) {
    const submenuContainer = document.getElementById('submenuContainer');
    
    // 탭별로 패턴 필터링
    let filteredPatterns = [];
    const tabId = tab.id || tab;
    
    // 탭 ID에 따라 관련 카테고리의 패턴 필터링
    const categoryMapping = {
        'concept_study': ['인지 과부하', '자신감 왜곡'],
        'problem_solving': ['실수 패턴', '접근 전략 오류'],
        'learning_management': ['학습 습관'],
        'exam_preparation': ['시간/압박 관리', '검증/확인 부재'],
        'practical_training': ['실수 패턴', '검증/확인 부재'],
        'attendance': ['기타 장애']
    };
    
    const relevantCategories = categoryMapping[tabId] || Object.keys(biasCategoryColors);
    filteredPatterns = biasPatterns.filter(p => relevantCategories.includes(p.category));
    
    // 패턴 카드 렌더링
    const html = `
        <div class="bias-patterns-container">
            <div class="bias-patterns-header">
                <h3>${tab.title || tab} - 인지관성 패턴 분석</h3>
                <p class="bias-patterns-desc">학생들의 인지관성 패턴을 파악하고 개선 방안을 제공합니다</p>
            </div>
            <div class="bias-patterns-grid">
                ${filteredPatterns.map(pattern => `
                    <div class="bias-pattern-card" 
                         onclick="showBiasPatternDetail(${pattern.id})"
                         style="border-left: 4px solid ${biasCategoryColors[pattern.category]}">
                        <div class="pattern-header">
                            <span class="pattern-icon">${pattern.icon}</span>
                            <span class="pattern-priority priority-${pattern.priority}">
                                ${pattern.priority === 'high' ? '높음' : pattern.priority === 'medium' ? '중간' : '낮음'}
                            </span>
                        </div>
                        <h4 class="pattern-name">${pattern.name}</h4>
                        <p class="pattern-desc">${pattern.desc}</p>
                        <div class="pattern-footer">
                            <span class="pattern-category" style="color: ${biasCategoryColors[pattern.category]}">
                                ${pattern.category}
                            </span>
                            <span class="pattern-audio">🎧 ${pattern.audioTime}</span>
                        </div>
                    </div>
                `).join('')}
            </div>
        </div>
    `;
    
    submenuContainer.innerHTML = html;
    
    // CSS 스타일 추가 (이미 없는 경우)
    if (!document.getElementById('bias-patterns-styles')) {
        const style = document.createElement('style');
        style.id = 'bias-patterns-styles';
        style.textContent = `
            .bias-patterns-container {
                padding: 20px;
            }
            .bias-patterns-header {
                margin-bottom: 20px;
            }
            .bias-patterns-header h3 {
                font-size: 1.5em;
                margin-bottom: 10px;
            }
            .bias-patterns-desc {
                color: #666;
                font-size: 14px;
            }
            .bias-patterns-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 20px;
            }
            .bias-pattern-card {
                background: white;
                border-radius: 8px;
                padding: 15px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                cursor: pointer;
                transition: all 0.3s;
                position: relative;
            }
            .bias-pattern-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            }
            .pattern-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 10px;
            }
            .pattern-icon {
                font-size: 24px;
            }
            .pattern-priority {
                font-size: 12px;
                padding: 2px 8px;
                border-radius: 12px;
                font-weight: bold;
            }
            .priority-high {
                background: #ffebee;
                color: #c62828;
            }
            .priority-medium {
                background: #fff3e0;
                color: #e65100;
            }
            .priority-low {
                background: #e8f5e9;
                color: #2e7d32;
            }
            .pattern-name {
                font-size: 16px;
                font-weight: bold;
                margin: 10px 0;
                color: #333;
            }
            .pattern-desc {
                font-size: 13px;
                color: #666;
                line-height: 1.5;
                margin-bottom: 15px;
            }
            .pattern-footer {
                display: flex;
                justify-content: space-between;
                align-items: center;
                font-size: 12px;
            }
            .pattern-category {
                font-weight: bold;
            }
            .pattern-audio {
                color: #999;
            }
        `;
        document.head.appendChild(style);
    }
}

// 인지관성 패턴 상세 표시 함수
function showBiasPatternDetail(patternId) {
    const pattern = biasPatterns.find(p => p.id === patternId);
    if (!pattern) return;
    
    // 솔루션 데이터가 없으면 기본값 설정
    const solution = pattern.solution || {
        action: "개선 방안을 준비 중입니다.",
        check: "확인 방법을 준비 중입니다.",
        audioScript: "오디오 스크립트를 준비 중입니다.",
        teacherDialog: "교사 대화를 준비 중입니다."
    };
    
    Swal.fire({
        title: `${pattern.icon} ${pattern.name}`,
        html: `
            <div style="text-align: left; max-height: 60vh; overflow-y: auto;">
                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <p style="color: #666; line-height: 1.6;">${pattern.desc}</p>
                    <div style="margin-top: 10px;">
                        <span style="display: inline-block; padding: 4px 12px; background: ${biasCategoryColors[pattern.category]}20; color: ${biasCategoryColors[pattern.category]}; border-radius: 4px; font-size: 12px; font-weight: bold;">
                            ${pattern.category}
                        </span>
                        <span style="margin-left: 10px; color: #999; font-size: 12px;">
                            🎧 오디오 ${pattern.audioTime}
                        </span>
                    </div>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <h4 style="color: #2196F3; margin-bottom: 10px;">✅ 개선 방안</h4>
                    <p style="background: #e3f2fd; padding: 12px; border-radius: 6px; line-height: 1.6;">
                        ${solution.action}
                    </p>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <h4 style="color: #4CAF50; margin-bottom: 10px;">🔍 확인 방법</h4>
                    <p style="background: #e8f5e9; padding: 12px; border-radius: 6px; line-height: 1.6;">
                        ${solution.check}
                    </p>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <h4 style="color: #FF9800; margin-bottom: 10px;">🎧 오디오 스크립트</h4>
                    <p style="background: #fff3e0; padding: 12px; border-radius: 6px; line-height: 1.6; font-style: italic;">
                        "${solution.audioScript}"
                    </p>
                </div>
                
                <div style="margin-bottom: 10px;">
                    <h4 style="color: #9C27B0; margin-bottom: 10px;">💬 학생-교사 대화 예시</h4>
                    <p style="background: #f3e5f5; padding: 12px; border-radius: 6px; line-height: 1.6;">
                        <strong>학생:</strong> "${solution.teacherDialog}"
                    </p>
                </div>
            </div>
        `,
        width: '700px',
        confirmButtonText: '확인',
        confirmButtonColor: '#2196F3'
    });
}

// (두 번째 DOMContentLoaded는 첫 번째와 통합되었으므로 제거)
// 아래의 initializeAgent 함수를 위한 주석
function initializeAgentIfNeeded() {
    // 플러그인 클라이언트 준비 확인
    if (window.ktmPluginClient) {
        console.log('KTM 플러그인 클라이언트 준비됨');
    }
    
    // URL 파라미터에서 복원 상태 확인
    const urlParams = new URLSearchParams(window.location.search);
    const returnState = urlParams.get('returnState');
    
    if (returnState) {
        try {
            const state = JSON.parse(decodeURIComponent(returnState));
            restoreState(state);
        } catch (e) {
            console.error('상태 복원 실패:', e);
            // 복원 실패 시 iframe만 표시 (초기 상태 유지)
        }
    }
    // 초기 로딩 시에는 iframe만 표시되고, 사용자가 메뉴를 선택하면 UI가 나타남
}