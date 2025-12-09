/* ========================================
   WXSPERTA 홀론 네비게이터 - JavaScript
   ======================================== */

// ========== TEMPLATE DATA ==========
const HOLON_TEMPLATES = [
    { 
        id: 'astral_elevator', 
        name: 'Astral Elevator', 
        icon: '🛗',
        description: '홀론 간 레벨 이동을 관장하는 엘리베이터 역할',
        fields: [
            { key: 'current_level', label: '현재 레벨', type: 'text' },
            { key: 'target_level', label: '목표 레벨', type: 'text' },
            { key: 'transition_method', label: '전환 방법', type: 'textarea' }
        ]
    },
    { 
        id: 'architect', 
        name: '아키텍트', 
        icon: '🏗️',
        description: '전체 시스템 구조를 설계하고 청사진을 제공',
        fields: [
            { key: 'system_scope', label: '시스템 범위', type: 'text' },
            { key: 'architecture_pattern', label: '아키텍처 패턴', type: 'text' },
            { key: 'design_principles', label: '설계 원칙', type: 'textarea' }
        ]
    },
    { 
        id: 'mission_spirit', 
        name: '미션실행의 정령', 
        icon: '✨',
        description: '미션을 활성화하고 실행 에너지를 부여하는 정령',
        fields: [
            { key: 'mission_statement', label: '미션 선언', type: 'textarea' },
            { key: 'activation_trigger', label: '활성화 트리거', type: 'text' },
            { key: 'energy_source', label: '에너지 소스', type: 'text' }
        ]
    },
    { 
        id: 'pm', 
        name: 'PM', 
        icon: '📊',
        description: '프로젝트 전반을 관리하고 일정/리소스를 조율',
        fields: [
            { key: 'project_name', label: '프로젝트명', type: 'text' },
            { key: 'timeline', label: '타임라인', type: 'text' },
            { key: 'milestones', label: '마일스톤', type: 'textarea' },
            { key: 'resources', label: '리소스 배분', type: 'textarea' }
        ]
    },
    { 
        id: 'engineer', 
        name: '엔지니어', 
        icon: '⚙️',
        description: '기술적 구현과 실행을 담당하는 역할',
        fields: [
            { key: 'tech_stack', label: '기술 스택', type: 'text' },
            { key: 'implementation_plan', label: '구현 계획', type: 'textarea' },
            { key: 'technical_constraints', label: '기술적 제약', type: 'textarea' }
        ]
    },
    { 
        id: 'scientist', 
        name: '과학자', 
        icon: '🔬',
        description: '연구, 분석, 실험을 통해 지식을 생성',
        fields: [
            { key: 'research_question', label: '연구 질문', type: 'text' },
            { key: 'hypothesis', label: '가설', type: 'textarea' },
            { key: 'methodology', label: '방법론', type: 'textarea' },
            { key: 'expected_outcome', label: '기대 결과', type: 'textarea' }
        ]
    },
    { 
        id: 'mediator', 
        name: '중재자', 
        icon: '⚖️',
        description: '홀론 간 갈등을 조율하고 합의를 도출',
        fields: [
            { key: 'parties', label: '관련 당사자', type: 'text' },
            { key: 'conflict_point', label: '갈등 지점', type: 'textarea' },
            { key: 'mediation_strategy', label: '중재 전략', type: 'textarea' }
        ]
    },
    { 
        id: 'meeting_planner', 
        name: '회의 기획자', 
        icon: '📅',
        description: '협업을 위한 회의를 기획하고 진행',
        fields: [
            { key: 'meeting_purpose', label: '회의 목적', type: 'text' },
            { key: 'participants', label: '참석자', type: 'text' },
            { key: 'agenda', label: '아젠다', type: 'textarea' },
            { key: 'expected_output', label: '기대 산출물', type: 'text' }
        ]
    },
    { 
        id: 'persona', 
        name: '페르소나', 
        icon: '🎭',
        description: '특정 역할을 수행하는 캐릭터 정의',
        fields: [
            { key: 'persona_name', label: '페르소나 이름', type: 'text' },
            { key: 'characteristics', label: '특성', type: 'textarea' },
            { key: 'goals', label: '목표', type: 'textarea' },
            { key: 'behaviors', label: '행동 패턴', type: 'textarea' }
        ]
    },
    { 
        id: 'worldview_designer', 
        name: '세계관 디자이너', 
        icon: '🌍',
        description: '전체 시스템의 세계관과 비전을 설계',
        fields: [
            { key: 'vision', label: '비전', type: 'textarea' },
            { key: 'core_values', label: '핵심 가치', type: 'textarea' },
            { key: 'belief_system', label: '신념 체계', type: 'textarea' }
        ]
    },
    { 
        id: 'context_refiner', 
        name: '문맥 구체화', 
        icon: '📍',
        description: '상황과 맥락을 구체화하고 명확하게 정의',
        fields: [
            { key: 'current_context', label: '현재 문맥', type: 'textarea' },
            { key: 'key_signals', label: '핵심 신호', type: 'textarea' },
            { key: 'refined_context', label: '구체화된 문맥', type: 'textarea' }
        ]
    },
    { 
        id: 'resource_structurer', 
        name: '리소스 구조화', 
        icon: '📦',
        description: '리소스를 체계적으로 구조화하고 관리',
        fields: [
            { key: 'resource_list', label: '리소스 목록', type: 'textarea' },
            { key: 'structure_pattern', label: '구조 패턴', type: 'text' },
            { key: 'accessibility', label: '접근성 설계', type: 'textarea' }
        ]
    },
    { 
        id: 'procedure_designer', 
        name: '절차 설계자', 
        icon: '🔄',
        description: '실행 절차를 설계하고 최적화',
        fields: [
            { key: 'procedure_name', label: '절차명', type: 'text' },
            { key: 'steps', label: '단계별 절차', type: 'textarea' },
            { key: 'optimization', label: '최적화 포인트', type: 'textarea' }
        ]
    },
    { 
        id: 'execution_guide', 
        name: '실행과정 가이드', 
        icon: '🚀',
        description: '실행 과정을 안내하고 난관 극복을 지원',
        fields: [
            { key: 'execution_phase', label: '실행 단계', type: 'text' },
            { key: 'action_items', label: '액션 아이템', type: 'textarea' },
            { key: 'obstacle_handling', label: '난관 대응', type: 'textarea' }
        ]
    },
    { 
        id: 'reflection_guide', 
        name: '성찰과정 가이드', 
        icon: '🔍',
        description: '성찰 과정을 안내하고 인사이트 도출을 지원',
        fields: [
            { key: 'reflection_point', label: '성찰 포인트', type: 'textarea' },
            { key: 'lessons_learned', label: '학습된 교훈', type: 'textarea' },
            { key: 'improvements', label: '개선 사항', type: 'textarea' }
        ]
    },
    { 
        id: 'result_propagator', 
        name: '결과 전파 안내자', 
        icon: '📡',
        description: '결과물을 적합한 대상에게 전파하고 시너지 연결',
        fields: [
            { key: 'result_summary', label: '결과 요약', type: 'textarea' },
            { key: 'target_audience', label: '전파 대상', type: 'text' },
            { key: 'propagation_channels', label: '전파 채널', type: 'textarea' },
            { key: 'synergy_points', label: '시너지 연결점', type: 'textarea' }
        ]
    }
];

// 홀론 간 소통 정보
const HOLON_COMMUNICATION = {
    method: '구조화된 결과물을 생성하기 위한 대화',
    collaboration: '공동실험을 실행하여 결과 리포트 생성',
    human_involvement: '필요한 경우 사람과 협업 (심층 리서치 등 사용)'
};

// ========== DATA ==========
const WXSPERTA_ELEMENTS = [
    { letter: 'W', name: '세계관', fullName: 'Worldview', color: '#FF6B6B',
      description: '최종목표, 페르소나, 의도, 가치관, 신념 체계',
      mission: '세계관, 목적, 의도가 강력하게 동작하여 W의 첨부문서를 고도화',
      will: '최단경로로 최소 엔트로피로 목표 도달. 성장의 토대 위에 데이터가 선명해지고 다음 단계로 성장하려는 강력한 의지',
      actionVerb: '세계관 강화',
      attachments: [] },
    { letter: 'X', name: '문맥지능', fullName: 'Contextual Intelligence', color: '#4ECDC4',
      description: '실시간 데이터, Heartbeat, 변화하는 흐름 이해',
      mission: '목적을 달성하도록 하는 의도가 강력하게 동작하여 문맥과 관련된 X의 첨부문서를 고도화',
      will: '실시간 데이터를 토대로 수정하고 기록하고 전달하여 새로운 길을 만들기 위한 깊이 있는 전략을 제공하려는 의지',
      actionVerb: '문맥 정교화',
      attachments: [] },
    { letter: 'S', name: '구조지능', fullName: 'Structural Intelligence', color: '#45B7D1',
      description: '리소스, 접근성, 즉시 사용성, 변수 간 상호작용 분석',
      mission: '리소스 구조를 식별하고 구조화하여 P,E 단계를 강력하게 지원하는 방식으로 S의 첨부문서를 고도화',
      will: '리소스를 식별하고 체계화, 실행단계에서의 readiness를 검토하여 견고한 리소스 지원을 하려는 의지',
      actionVerb: '구조 체계화',
      attachments: [] },
    { letter: 'P', name: '절차지능', fullName: 'Procedural Intelligence', color: '#96CEB4',
      description: '테스크 정의, 절차 개발 및 최적화',
      mission: '절차지능이 고도로 동작하도록 P의 첨부문서를 고도화',
      will: '리소스들을 최소 엔트로피로 실행하여 완벽한 접근법을 구사하려는 의지. 항상 준비된 상태에 도달하려는 의지',
      actionVerb: '절차 최적화',
      attachments: [] },
    { letter: 'E', name: '실행지능', fullName: 'Executional Intelligence', color: '#FFEAA7',
      description: '계획을 실제 행동으로, 문제해결, 실행 속도와 빈도',
      mission: '실행과정의 난관을 극복하도록 E의 첨부문서를 고도화',
      will: '실시간 문제해결을 위한 최고의 지능 해상도에 도달. 분야 전문가 페르소나를 구성하여 팀으로 문제해결하려는 의지',
      actionVerb: '실행력 강화',
      attachments: [] },
    { letter: 'R', name: '성찰지능', fullName: 'Reflective Intelligence', color: '#DDA0DD',
      description: '실행 후 분석, 비밀경로 탐색, 최적화',
      mission: '결과에 대한 성찰, 평가를 통한 피드백이 효과적으로 동작하도록 R의 첨부문서를 고도화',
      will: '적용예측, 미래예측에 대한 신뢰도 있는 리포트를 생성. 순환적으로 결과를 완성해 나가는 최종 책임자로서의 의지',
      actionVerb: '성찰 심화',
      attachments: [] },
    { letter: 'T', name: '트래픽지능', fullName: 'Traffic Intelligence', color: '#F39C12',
      description: '데이터 흐름 관리, 연쇄작용, J커브 지원',
      mission: '생성된 결과의 효과가 전파되도록 난관들을 식별하고, 필요한 지점들에 잘 전달되도록 탐색하여 대상이 필요로 하는 방식으로 전환하여 T의 첨부문서를 고도화',
      will: '결과를 시너지를 일으킬 수 있는 지점으로 연결하고 흐르게 하려는 의지. 전파가 실제 일어나게 하려는 의지',
      actionVerb: '전파 설계',
      attachments: [] },
    { letter: 'A', name: '고도화지능', fullName: 'Astral Ascend', color: '#9B59B6',
      description: '추상화, 모듈화, 집적화, 자동화',
      mission: '시스템 레벨의 피드백과 개선을 위한 정보들을 추출하여 상위 홀론으로 효과적으로 UP하도록 A의 첨부문서를 고도화',
      will: '현재 홀론의 성취가 최상위 홀론의 의지와 연결되도록 순환과 공명이 일어나게 하려는 적극적 중재자로서의 의지',
      actionVerb: '시스템 승격',
      attachments: [] }
];

const DIRECTIONS = [
    { key: 'F', name: 'Forward', korean: '전진', description: '사고의 진행, 예측, 연속 추론', color: '#4ECDC4' },
    { key: 'B', name: 'Backward', korean: '회귀', description: '원인 회귀, 근거 탐색, 근본 원리 복원', color: '#FF6B6B' },
    { key: 'L', name: 'Left', korean: '대안', description: '대안 탐색, 관점 전환, 반대 방향 사고', color: '#FFEAA7' },
    { key: 'R', name: 'Right', korean: '수렴', description: '정답 수렴, 기준 정렬, 체계화·구조화', color: '#96CEB4' },
    { key: 'U', name: 'Up', korean: '상승', description: '추상화, 원리화, 메타인지 상승', color: '#9B59B6' },
    { key: 'D', name: 'Down', korean: '하강', description: '구체화, 사례화, 실행 단계로 하강', color: '#45B7D1' }
];

const initialHolon = {
    title: "Self-Evolving K-12 EdTech",
    level: 0,
    path: [],
    W: { text: "전국 최고의 AI 기반 자기진화형 K-12 수학 교육 기업", attachments: [] },
    X: { text: "WXSPERTA 홀론 아키텍처 설계 완료, 실행 단계 진입", attachments: [] },
    S: { text: "조직 구조, 제품 아키텍처, 운영 전략, 투자 구조, API 시스템", attachments: [] },
    P: { text: "WXSPERTA 프레임워크 내재화 → 부서별 홀론 설계 → 제품 아키텍처 구축", attachments: [] },
    E: { text: "전 조직 교육 40h → 워크샵 80h → 구현 200h", attachments: [] },
    R: { text: "홀론 구조는 자기 유사성으로 확장 용이, 의지 보존이 핵심", attachments: [] },
    T: { text: "Top-down 전략 → Bottom-up 실행 피드백", attachments: [] },
    A: { text: "WXSPERTA 홀론 모델을 범용 조직 설계 프레임워크로 추상화", attachments: [] }
};

// ========== STATE ==========
let currentHolon = JSON.parse(JSON.stringify(initialHolon));
let selectedElement = null;
let showDirections = false;
let history = [JSON.parse(JSON.stringify(initialHolon))];
let historyIndex = 0;
let isTransitioning = false;
let outputLog = [];
let currentTab = 'holon';
let accordionStates = {}; // Track open/closed state of each accordion
let allExpanded = false;
let showTemplateDropdown = false;
let selectedTemplate = null;

// ========== HELPER FUNCTIONS ==========
function showNotification(message, type = 'info') {
    const notification = document.getElementById('notification');
    notification.textContent = message;
    notification.className = 'notification show ' + type;
    setTimeout(() => {
        notification.classList.remove('show');
    }, 3000);
}

function isExploreMode() {
    return !selectedElement;
}

function switchTab(tabName) {
    currentTab = tabName;
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    
    if (tabName === 'holon') {
        document.querySelector('.tab-btn:first-child').classList.add('active');
        document.getElementById('holonTab').classList.add('active');
    } else {
        document.querySelector('.tab-btn:last-child').classList.add('active');
        document.getElementById('helpTab').classList.add('active');
    }
}

function toggleAccordion(letter) {
    accordionStates[letter] = !accordionStates[letter];
    renderAccordionList();
}

function toggleAllAccordions() {
    allExpanded = !allExpanded;
    WXSPERTA_ELEMENTS.forEach(elem => {
        accordionStates[elem.letter] = allExpanded;
    });
    document.getElementById('expandAllBtn').textContent = allExpanded ? '📁 전체 접기' : '📂 전체 펼치기';
    renderAccordionList();
}

// ========== TEMPLATE FUNCTIONS ==========
function toggleTemplateDropdown() {
    showTemplateDropdown = !showTemplateDropdown;
    const dropdown = document.getElementById('templateDropdown');
    const btn = document.getElementById('templateBtn');
    const arrow = document.getElementById('templateArrow');
    
    if (showTemplateDropdown) {
        dropdown.classList.add('show');
        btn.classList.add('active');
        arrow.textContent = '▲';
    } else {
        dropdown.classList.remove('show');
        btn.classList.remove('active');
        arrow.textContent = '▼';
    }
}

function selectTemplate(templateId) {
    const template = HOLON_TEMPLATES.find(t => t.id === templateId);
    if (!template) return;

    selectedTemplate = template;
    showTemplateDropdown = false;
    
    document.getElementById('templateDropdown').classList.remove('show');
    document.getElementById('templateBtn').classList.remove('active');
    document.getElementById('templateArrow').textContent = '▼';
    
    renderTemplateList();
    renderTemplateForm();
    
    showNotification(`"이 홀론은 ${template.name}" 템플릿 선택됨`, 'info');
}

function closeTemplateForm() {
    selectedTemplate = null;
    document.getElementById('templateFormPanel').classList.remove('show');
    document.getElementById('templateFormOverlay').classList.remove('show');
    renderTemplateList();
}

function renderTemplateList() {
    const container = document.getElementById('templateList');
    let html = '';

    HOLON_TEMPLATES.forEach(template => {
        const isSelected = selectedTemplate && selectedTemplate.id === template.id;
        html += `
            <div class="template-item ${isSelected ? 'selected' : ''}" onclick="selectTemplate('${template.id}')">
                <span class="template-item-icon">${template.icon}</span>
                <span>이 홀론은 ${template.name}</span>
            </div>
        `;
    });

    container.innerHTML = html;
}

function renderTemplateForm() {
    if (!selectedTemplate) {
        document.getElementById('templateFormPanel').classList.remove('show');
        document.getElementById('templateFormOverlay').classList.remove('show');
        return;
    }

    const panel = document.getElementById('templateFormPanel');
    const overlay = document.getElementById('templateFormOverlay');
    const formIcon = document.getElementById('templateFormIcon');
    const formTitle = document.getElementById('templateFormTitleText');
    const formContent = document.getElementById('templateFormContent');

    formIcon.textContent = selectedTemplate.icon;
    formTitle.textContent = `이 홀론은 ${selectedTemplate.name}`;

    let html = `
        <div class="template-form-info">
            <div class="template-form-info-title">
                <span>💡</span> 역할 설명
            </div>
            <p>${selectedTemplate.description}</p>
        </div>
        <div class="template-form-info" style="background: rgba(78, 205, 196, 0.1); border-color: rgba(78, 205, 196, 0.2);">
            <div class="template-form-info-title" style="color: #4ECDC4;">
                <span>🔗</span> 홀론 소통 방식
            </div>
            <p><strong>대화 방식:</strong> ${HOLON_COMMUNICATION.method}</p>
            <p><strong>협업 방식:</strong> ${HOLON_COMMUNICATION.collaboration}</p>
            <p><strong>사람 협업:</strong> ${HOLON_COMMUNICATION.human_involvement}</p>
        </div>
    `;

    selectedTemplate.fields.forEach(field => {
        if (field.type === 'textarea') {
            html += `
                <div class="template-form-group">
                    <label class="template-form-label">${field.label}</label>
                    <textarea class="template-form-textarea" id="field_${field.key}" placeholder="${field.label}을(를) 입력하세요"></textarea>
                </div>
            `;
        } else {
            html += `
                <div class="template-form-group">
                    <label class="template-form-label">${field.label}</label>
                    <input type="text" class="template-form-input" id="field_${field.key}" placeholder="${field.label}을(를) 입력하세요">
                </div>
            `;
        }
    });

    html += `
        <div class="template-form-actions">
            <button class="template-form-btn template-form-btn-primary" onclick="applyTemplate()">
                ✨ 홀론에 적용
            </button>
            <button class="template-form-btn template-form-btn-secondary" onclick="saveTemplateData()">
                💾 저장
            </button>
            <button class="template-form-btn template-form-btn-secondary" onclick="closeTemplateForm()">
                취소
            </button>
        </div>
    `;

    formContent.innerHTML = html;
    overlay.classList.add('show');
    panel.classList.add('show');
}

function applyTemplate() {
    if (!selectedTemplate) return;

    const formData = {};
    selectedTemplate.fields.forEach(field => {
        const input = document.getElementById(`field_${field.key}`);
        formData[field.key] = input ? input.value : '';
    });

    console.log('Applied template:', selectedTemplate.name, formData);
    showNotification(`"${selectedTemplate.name}" 템플릿이 현재 홀론에 적용되었습니다`, 'refine');
    
    // 여기에 실제 홀론에 템플릿 데이터를 적용하는 로직 추가 가능
    closeTemplateForm();
}

function saveTemplateData() {
    if (!selectedTemplate) return;

    const formData = {};
    selectedTemplate.fields.forEach(field => {
        const input = document.getElementById(`field_${field.key}`);
        formData[field.key] = input ? input.value : '';
    });

    console.log('Saved template data:', selectedTemplate.name, formData);
    showNotification(`"${selectedTemplate.name}" 템플릿 데이터가 저장되었습니다`, 'info');
}

// ========== GENERATE FUNCTIONS ==========
function generateExploreHolon(direction, current) {
    const newPath = [...current.path, 'WA:' + direction];

    const transformations = {
        F: {
            title: current.title + ' → 미래 전개',
            W: { text: current.W.text + '의 다음 단계 비전으로 확장', attachments: [] },
            X: { text: '현재 상태에서 예측되는 다음 문맥과 신호들', attachments: [] },
            S: { text: '확장에 필요한 새로운 리소스 구조 설계', attachments: [] },
            P: { text: '다음 마일스톤을 위한 절차 로드맵', attachments: [] },
            E: { text: '확장 실행을 위한 구체적 액션 플랜', attachments: [] },
            R: { text: '예측 경로의 리스크와 성공 요인 분석', attachments: [] },
            T: { text: '확장 시 트래픽 흐름과 병목점 예측', attachments: [] },
            A: { text: '성장을 모듈화하여 재사용 가능한 패턴으로 추상화', attachments: [] }
        },
        B: {
            title: current.title + ' ← 근본 원리',
            W: { text: current.W.text + '의 핵심 존재 이유와 근본 가치', attachments: [] },
            X: { text: '이 문맥이 형성된 역사적 배경과 원인', attachments: [] },
            S: { text: '구조의 기초가 되는 핵심 원리와 공리', attachments: [] },
            P: { text: '절차가 이렇게 설계된 근본적 이유', attachments: [] },
            E: { text: '실행의 본질적 목적과 원초적 동기', attachments: [] },
            R: { text: '과거 시도에서 배운 핵심 교훈', attachments: [] },
            T: { text: '트래픽 패턴의 근원적 메커니즘', attachments: [] },
            A: { text: '고도화의 철학적 기반과 메타 원리', attachments: [] }
        },
        L: {
            title: current.title + ' ↰ 대안 경로',
            W: { text: current.W.text + '와 다른 관점의 세계관 탐색', attachments: [] },
            X: { text: '동일 상황의 다른 해석과 대안적 문맥', attachments: [] },
            S: { text: '기존과 다른 구조적 접근법과 아키텍처', attachments: [] },
            P: { text: '대안적 절차와 비전통적 프로세스', attachments: [] },
            E: { text: '색다른 실행 방식과 실험적 접근', attachments: [] },
            R: { text: '반대 관점에서의 성찰과 비판적 검토', attachments: [] },
            T: { text: '다른 채널과 비주류 트래픽 경로', attachments: [] },
            A: { text: '대안적 추상화 모델과 패러다임 전환', attachments: [] }
        },
        R: {
            title: current.title + ' ↱ 최적화',
            W: { text: current.W.text + '의 정제되고 명확한 버전', attachments: [] },
            X: { text: '핵심 문맥만 남긴 정제된 상황 인식', attachments: [] },
            S: { text: '표준화되고 최적화된 구조 설계', attachments: [] },
            P: { text: '검증된 베스트 프랙티스 기반 절차', attachments: [] },
            E: { text: '효율성 극대화된 실행 프로토콜', attachments: [] },
            R: { text: '성공 공식 도출과 체계화된 인사이트', attachments: [] },
            T: { text: '최적화된 트래픽 흐름과 전환율 극대화', attachments: [] },
            A: { text: '산업 표준으로 정립된 고도화 모델', attachments: [] }
        },
        U: {
            title: current.title + ' ↑ 메타 레벨',
            W: { text: current.W.text + '를 포함하는 상위 세계관', attachments: [] },
            X: { text: '더 큰 시스템 관점에서의 문맥', attachments: [] },
            S: { text: '메타 구조와 구조의 구조', attachments: [] },
            P: { text: '절차를 생성하는 메타 절차', attachments: [] },
            E: { text: '실행을 조율하는 상위 실행 시스템', attachments: [] },
            R: { text: '성찰에 대한 성찰, 메타인지', attachments: [] },
            T: { text: '트래픽 시스템 전체를 관장하는 메타 흐름', attachments: [] },
            A: { text: '고도화의 고도화, 초월적 통합', attachments: [] }
        },
        D: {
            title: current.title + ' ↓ 구체 실행',
            W: { text: current.W.text + '의 즉시 실행 가능한 버전', attachments: [] },
            X: { text: '지금 당장의 구체적 상황과 조건', attachments: [] },
            S: { text: '바로 사용 가능한 구체적 리소스 목록', attachments: [] },
            P: { text: '오늘 할 수 있는 구체적 단계별 절차', attachments: [] },
            E: { text: '즉각 실행할 첫 번째 액션 아이템', attachments: [] },
            R: { text: '이번 주 내 측정 가능한 피드백 포인트', attachments: [] },
            T: { text: '첫 트래픽을 발생시킬 구체적 채널', attachments: [] },
            A: { text: '가장 작은 단위의 모듈화된 실행 유닛', attachments: [] }
        }
    };

    const result = {
        ...current,
        ...transformations[direction],
        level: current.level + (direction === 'U' ? 1 : direction === 'D' ? -1 : 0),
        path: newPath
    };

    return result;
}

function generateRefineOutput(node, direction, current) {
    const elem = WXSPERTA_ELEMENTS.find(e => e.letter === node);
    const dir = DIRECTIONS.find(d => d.key === direction);

    const refineActions = {
        W: {
            F: '[W→F] 세계관의 미래 비전을 구체화하고 다음 단계 목표를 정의',
            B: '[W→B] 세계관의 근본 가치와 핵심 존재 이유를 재발굴',
            L: '[W→L] 대안적 세계관과 다른 관점에서의 목적 재정의',
            R: '[W→R] 세계관을 명확하고 일관된 형태로 정제하여 표준화',
            U: '[W→U] 세계관을 상위 메타 원리로 추상화하여 범용성 확보',
            D: '[W→D] 세계관을 즉시 실행 가능한 구체적 목표로 분해'
        },
        X: {
            F: '[X→F] 현재 문맥에서 예측되는 다음 신호와 트렌드 도출',
            B: '[X→B] 문맥 형성의 역사적 배경과 근본 원인 분석',
            L: '[X→L] 동일 상황에 대한 대안적 해석과 관점 제시',
            R: '[X→R] 핵심 문맥만 추출하여 정제된 상황 인식 구축',
            U: '[X→U] 문맥을 더 큰 시스템 관점에서 메타 분석',
            D: '[X→D] 문맥을 지금 당장 활용 가능한 구체적 정보로 변환'
        },
        S: {
            F: '[S→F] P,E 지원을 위한 미래 필요 리소스 구조 예측',
            B: '[S→B] 리소스 구조의 근본 원리와 핵심 의존성 분석',
            L: '[S→L] 대안적 리소스 구조와 다른 아키텍처 설계',
            R: '[S→R] 리소스 구조를 표준화하고 P,E 최적 지원 형태로 정제',
            U: '[S→U] 리소스 구조를 메타 레벨로 추상화하여 재사용성 확보',
            D: '[S→D] 리소스를 즉시 사용 가능한 구체적 형태로 준비'
        },
        P: {
            F: '[P→F] 다음 단계 절차와 미래 프로세스 로드맵 설계',
            B: '[P→B] 절차의 근본적 설계 원리와 이유 분석',
            L: '[P→L] 대안적 절차와 비전통적 프로세스 설계',
            R: '[P→R] 절차를 베스트 프랙티스 기반으로 최적화',
            U: '[P→U] 절차를 생성하는 메타 절차로 추상화',
            D: '[P→D] 절차를 오늘 바로 실행 가능한 단계로 구체화'
        },
        E: {
            F: '[E→F] 실행 과정에서 예상되는 난관과 대응 방안 예측',
            B: '[E→B] 실행의 본질적 목적과 원초적 동기 재확인',
            L: '[E→L] 색다른 실행 방식과 실험적 접근법 도출',
            R: '[E→R] 실행 프로토콜을 효율성 극대화 형태로 정제',
            U: '[E→U] 실행을 조율하는 상위 시스템 관점에서 분석',
            D: '[E→D] 즉각 실행할 첫 번째 액션 아이템으로 구체화'
        },
        R: {
            F: '[R→F] 현재 결과에서 미래 적용 가능한 인사이트 도출',
            B: '[R→B] 과거 시도에서 배운 핵심 교훈 분석',
            L: '[R→L] 반대 관점에서의 성찰과 비판적 검토',
            R: '[R→R] 성공 공식을 도출하여 체계화된 피드백 구축',
            U: '[R→U] 성찰에 대한 성찰, 메타인지 레벨로 상승',
            D: '[R→D] 이번 주 내 측정 가능한 피드백 포인트로 구체화'
        },
        T: {
            F: '[T→F] 전파 경로의 미래 흐름과 병목점 예측',
            B: '[T→B] 트래픽 패턴의 근원적 메커니즘 분석',
            L: '[T→L] 다른 채널과 비주류 전파 경로 탐색',
            R: '[T→R] 전파 흐름을 최적화하고 전환율 극대화',
            U: '[T→U] 트래픽 시스템 전체를 관장하는 메타 전략',
            D: '[T→D] 첫 트래픽을 발생시킬 구체적 채널과 방법'
        },
        A: {
            F: '[A→F] 상위 홀론과의 연결을 위한 미래 통합 경로 설계',
            B: '[A→B] 고도화의 철학적 기반과 메타 원리 분석',
            L: '[A→L] 대안적 추상화 모델과 패러다임 전환 탐색',
            R: '[A→R] 시스템 피드백을 표준화된 형식으로 정제',
            U: '[A→U] 고도화의 고도화, 초월적 통합으로 상승',
            D: '[A→D] 상위 홀론에 전달할 구체적 정보 패키지 생성'
        }
    };

    return {
        id: Date.now(),
        timestamp: new Date().toISOString(),
        node: node,
        direction: direction,
        action: refineActions[node][direction],
        nodeName: elem.name,
        directionName: dir.korean,
        mission: elem.mission
    };
}

// ========== EVENT HANDLERS ==========
function handleElementSelect(letter) {
    const elem = WXSPERTA_ELEMENTS.find(e => e.letter === letter);

    if (selectedElement && selectedElement.letter === letter) {
        selectedElement = null;
        showNotification('WA 탐색 모드로 전환', 'mode');
    } else {
        selectedElement = elem;
        showNotification(elem.letter + ' 노드 고도화 모드 활성화', 'mode');
    }
    showDirections = false;
    updateUI();
}

function handleDirectionSelect(direction) {
    if (isTransitioning) return;

    isTransitioning = true;
    showDirections = false;
    document.getElementById('wheel').classList.add('transitioning');

    setTimeout(() => {
        if (isExploreMode()) {
            const newHolon = generateExploreHolon(direction, currentHolon);
            history = history.slice(0, historyIndex + 1);
            history.push(JSON.parse(JSON.stringify(newHolon)));
            historyIndex = history.length - 1;
            currentHolon = newHolon;

            const dir = DIRECTIONS.find(d => d.key === direction);
            showNotification('[WA 탐색] ' + dir.korean + ' 방향으로 새로운 홀론 생성', 'explore');
        } else {
            const output = generateRefineOutput(selectedElement.letter, direction, currentHolon);
            outputLog.unshift(output);

            const dir = DIRECTIONS.find(d => d.key === direction);
            showNotification('[' + selectedElement.letter + ' 고도화] ' + dir.korean + ' 방향 OUTPUT 생성', 'refine');
        }

        document.getElementById('wheel').classList.remove('transitioning');
        isTransitioning = false;
        updateUI();
    }, 400);
}

function toggleDirections() {
    showDirections = !showDirections;
    updateUI();
}

function handleHistoryNav(delta) {
    const newIndex = historyIndex + delta;
    if (newIndex >= 0 && newIndex < history.length) {
        isTransitioning = true;
        document.getElementById('wheel').classList.add('transitioning');

        setTimeout(() => {
            historyIndex = newIndex;
            currentHolon = JSON.parse(JSON.stringify(history[newIndex]));
            selectedElement = null;
            document.getElementById('wheel').classList.remove('transitioning');
            isTransitioning = false;
            updateUI();
        }, 300);
    }
}

function handleReset() {
    history = [JSON.parse(JSON.stringify(initialHolon))];
    historyIndex = 0;
    currentHolon = JSON.parse(JSON.stringify(initialHolon));
    selectedElement = null;
    showDirections = false;
    outputLog = [];
    accordionStates = {};
    allExpanded = false;
    document.getElementById('expandAllBtn').textContent = '📂 전체 펼치기';
    showNotification('초기 상태로 리셋', 'info');
    updateUI();
}

// ========== UI UPDATE ==========
function updateUI() {
    // Mode Badge
    const modeBadge = document.getElementById('modeBadge');
    const modeDesc = document.getElementById('modeDesc');
    if (isExploreMode()) {
        modeBadge.className = 'mode-badge explore';
        modeBadge.textContent = '🔭 WA 탐색 모드';
        modeDesc.textContent = '전체 홀론 차원 이동';
    } else {
        modeBadge.className = 'mode-badge refine';
        modeBadge.textContent = '⚡ ' + selectedElement.letter + ' 고도화 모드';
        modeDesc.textContent = selectedElement.name + ' 첨부문서 고도화';
    }

    // History Controls
    document.getElementById('prevBtn').disabled = historyIndex === 0;
    document.getElementById('nextBtn').disabled = historyIndex === history.length - 1;
    document.getElementById('historyCounter').textContent = (historyIndex + 1) + '/' + history.length;

    // Path
    const pathContainer = document.getElementById('pathContainer');
    const pathContent = document.getElementById('pathContent');
    if (currentHolon.path.length > 0) {
        pathContainer.style.display = 'flex';
        let pathHtml = '';
        currentHolon.path.forEach(p => {
            const [mode, dir] = p.split(':');
            const dirInfo = DIRECTIONS.find(d => d.key === dir);
            pathHtml += '<span class="path-separator">→</span>';
            pathHtml += '<span style="color: ' + (dirInfo ? dirInfo.color : '#888') + '; font-weight: 600;">' + mode + ':' + (dirInfo ? dirInfo.korean : dir) + '</span>';
        });
        pathContent.innerHTML = pathHtml;
    } else {
        pathContainer.style.display = 'none';
    }

    // Wheel Ring
    const wheelRing = document.getElementById('wheelRing');
    if (isExploreMode()) {
        wheelRing.className = 'wheel-ring';
        wheelRing.style.setProperty('--active-color', '');
        wheelRing.style.setProperty('--active-shadow', '');
        wheelRing.style.setProperty('--active-shadow-inner', '');
    } else {
        wheelRing.className = 'wheel-ring refine-mode';
        wheelRing.style.setProperty('--active-color', selectedElement.color + '44');
        wheelRing.style.setProperty('--active-shadow', selectedElement.color + '22');
        wheelRing.style.setProperty('--active-shadow-inner', selectedElement.color + '11');
    }

    // Element Buttons
    renderElementButtons();

    // Center Button
    const centerBtn = document.getElementById('centerBtn');
    const centerIcon = document.getElementById('centerIcon');
    const centerText = document.getElementById('centerText');

    if (!showDirections) {
        centerBtn.style.display = 'flex';
        if (isExploreMode()) {
            centerBtn.className = 'center-btn explore';
            centerBtn.style.background = 'linear-gradient(135deg, #1a1a2e, #2d2d44)';
            centerIcon.textContent = '⬡';
            centerIcon.style.color = 'white';
            centerText.textContent = '방향선택';
            centerText.style.color = 'rgba(255,255,255,0.8)';
        } else {
            centerBtn.className = 'center-btn refine';
            centerBtn.style.background = 'linear-gradient(135deg, ' + selectedElement.color + '22, ' + selectedElement.color + '33)';
            centerBtn.style.setProperty('--active-color', selectedElement.color + '66');
            centerBtn.style.setProperty('--active-shadow', selectedElement.color + '33');
            centerIcon.textContent = selectedElement.letter;
            centerIcon.style.color = selectedElement.color;
            centerText.textContent = '고도화';
            centerText.style.color = selectedElement.color;
        }
    } else {
        centerBtn.style.display = 'none';
    }

    // Direction Container
    const dirContainer = document.getElementById('directionContainer');
    dirContainer.className = showDirections ? 'direction-container show' : 'direction-container';

    // Stats
    document.getElementById('holonTitle').textContent = currentHolon.title;
    document.getElementById('levelBadge').textContent = '레벨: ' + currentHolon.level;
    document.getElementById('depthBadge').textContent = '깊이: ' + currentHolon.path.length;
    document.getElementById('outputBadge').textContent = 'OUTPUT: ' + outputLog.length;

    // Selected Panel vs Explore Panel
    const selectedPanel = document.getElementById('selectedPanel');
    const explorePanel = document.getElementById('explorePanel');

    if (selectedElement) {
        selectedPanel.style.display = 'block';
        explorePanel.style.display = 'none';

        selectedPanel.style.background = 'linear-gradient(135deg, ' + selectedElement.color + '15, ' + selectedElement.color + '08)';
        selectedPanel.style.borderColor = selectedElement.color + '55';

        selectedPanel.innerHTML = `
            <div class="selected-header">
                <div class="selected-icon" style="background: ${selectedElement.color}44; color: ${selectedElement.color};">
                    ${selectedElement.letter}
                </div>
                <div>
                    <h3 class="selected-title" style="color: ${selectedElement.color};">${selectedElement.name}</h3>
                    <p class="selected-subtitle">${selectedElement.fullName}</p>
                </div>
            </div>
            <div class="mission-box">
                <h4 class="box-label">🎯 고도화 미션</h4>
                <p class="box-text">${selectedElement.mission}</p>
            </div>
            <div class="will-box">
                <h4 class="box-label">⚡ 의지 (Will)</h4>
                <p class="will-text">"${selectedElement.will}"</p>
            </div>
        `;
    } else {
        selectedPanel.style.display = 'none';
        explorePanel.style.display = 'block';
        renderAccordionList();
    }

    // Output Log
    const outputLogPanel = document.getElementById('outputLogPanel');
    const outputLogList = document.getElementById('outputLogList');

    if (outputLog.length > 0) {
        outputLogPanel.style.display = 'block';
        let logHtml = '';
        outputLog.slice(0, 5).forEach(log => {
            const elem = WXSPERTA_ELEMENTS.find(e => e.letter === log.node);
            const dir = DIRECTIONS.find(d => d.key === log.direction);
            logHtml += `
                <div class="log-item" style="border-left-color: ${elem ? elem.color : '#888'};">
                    <div class="log-header">
                        <span class="log-node" style="color: ${elem ? elem.color : '#888'};">${log.node}</span>
                        <span class="log-arrow">→</span>
                        <span class="log-direction" style="color: ${dir ? dir.color : '#888'};">${log.directionName}</span>
                        <span class="log-time">${new Date(log.timestamp).toLocaleTimeString()}</span>
                    </div>
                    <p class="log-action">${log.action}</p>
                </div>
            `;
        });
        outputLogList.innerHTML = logHtml;
    } else {
        outputLogPanel.style.display = 'none';
    }
}

function renderElementButtons() {
    const container = document.getElementById('elementButtons');
    let html = '';
    const radius = 135;

    WXSPERTA_ELEMENTS.forEach((elem, index) => {
        const angle = (index * 45 - 90) * (Math.PI / 180);
        const x = Math.cos(angle) * radius;
        const y = Math.sin(angle) * radius;
        const isSelected = selectedElement && selectedElement.letter === elem.letter;

        const bgColor = isSelected
            ? 'linear-gradient(135deg, ' + elem.color + '44, ' + elem.color + '66)'
            : 'linear-gradient(135deg, ' + elem.color + '22, ' + elem.color + '44)';
        const boxShadow = isSelected
            ? '0 0 20px ' + elem.color + '88, inset 0 0 15px ' + elem.color + '44'
            : '0 0 10px ' + elem.color + '22';

        html += `
            <button class="element-btn ${isSelected ? 'selected' : ''}"
                    style="--x: ${x}px; --y: ${y}px;
                           transform: translate(calc(-50% + ${x}px), calc(-50% + ${y}px));
                           background: ${bgColor};
                           border-color: ${elem.color};
                           color: ${elem.color};
                           box-shadow: ${boxShadow};"
                    onclick="handleElementSelect('${elem.letter}')">
                <span class="element-letter">${elem.letter}</span>
                <span class="element-name">${elem.name}</span>
            </button>
        `;
    });

    container.innerHTML = html;
}

function renderOuterDirButtons() {
    const container = document.getElementById('outerDirButtons');
    const positions = {
        U: {x: 0, y: -72},
        D: {x: 0, y: 72},
        B: {x: -72, y: 0},
        F: {x: 72, y: 0}
    };

    let html = '';
    DIRECTIONS.filter(d => !['L', 'R'].includes(d.key)).forEach(dir => {
        const pos = positions[dir.key];
        html += `
            <button class="dir-btn"
                    style="--x: ${pos.x}px; --y: ${pos.y}px;
                           left: 50%; top: 50%;
                           transform: translate(calc(-50% + ${pos.x}px), calc(-50% + ${pos.y}px));
                           width: 48px; height: 48px;
                           background: linear-gradient(135deg, ${dir.color}33, ${dir.color}55);
                           border-color: ${dir.color};
                           color: ${dir.color};"
                    onclick="handleDirectionSelect('${dir.key}')"
                    title="${dir.korean}: ${dir.description}">
                <span style="font-size: 1rem; font-weight: bold;">${dir.key}</span>
                <span style="font-size: 9px;">${dir.korean}</span>
            </button>
        `;
    });

    container.innerHTML = html;
}

function renderAccordionList() {
    const container = document.getElementById('accordionList');
    let html = '';

    WXSPERTA_ELEMENTS.forEach(elem => {
        const isOpen = accordionStates[elem.letter] || false;
        const holonData = currentHolon[elem.letter];
        const attachments = holonData?.attachments || [];

        html += `
            <div class="accordion-item ${isOpen ? 'open' : ''}" style="border-left-color: ${elem.color};">
                <div class="accordion-header" onclick="toggleAccordion('${elem.letter}')">
                    <span class="accordion-toggle">▶</span>
                    <span class="accordion-letter" style="color: ${elem.color};">${elem.letter}</span>
                    <span class="accordion-name">${elem.name}</span>
                    <span class="accordion-text">${holonData ? holonData.text : ''}</span>
                </div>
                <div class="accordion-content">
                    <div class="accordion-detail">
                        <div class="accordion-detail-label">📝 현재 상태</div>
                        <div class="accordion-detail-text">${holonData ? holonData.text : '-'}</div>
                    </div>
                    <div class="accordion-detail">
                        <div class="accordion-detail-label">🎯 미션</div>
                        <div class="accordion-detail-text">${elem.mission}</div>
                    </div>
                    <div class="accordion-detail">
                        <div class="accordion-detail-label">⚡ 의지</div>
                        <div class="accordion-detail-text" style="font-style: italic;">"${elem.will}"</div>
                    </div>
                    <div class="attachment-area">
                        <div class="attachment-label">
                            <span class="attachment-label-icon">📎</span>
                            <span>첨부</span>
                        </div>
                        <div class="attachment-list">
                            ${attachments.length > 0 
                                ? attachments.map(att => `<div class="attachment-item"><span class="attachment-item-icon">📄</span><span class="attachment-item-name">${att.name}</span></div>`).join('')
                                : '<span class="attachment-empty">없음</span>'
                            }
                        </div>
                        <button class="btn-attach" onclick="event.stopPropagation(); showNotification('첨부파일 추가 기능 준비중', 'info')">+ 추가</button>
                    </div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

function renderDirectionGuide() {
    const container = document.getElementById('directionGuide');
    let html = '';

    DIRECTIONS.forEach(dir => {
        html += `
            <div class="dir-card" style="border-top-color: ${dir.color};" onclick="toggleDirections()">
                <div class="dir-card-header">
                    <span class="dir-card-key" style="color: ${dir.color};">${dir.key}</span>
                    <span class="dir-card-korean">${dir.korean}</span>
                </div>
                <p class="dir-card-desc">${dir.description}</p>
            </div>
        `;
    });

    container.innerHTML = html;
}

function renderElementGuide() {
    const container = document.getElementById('elementGuide');
    let html = '';

    WXSPERTA_ELEMENTS.forEach(elem => {
        html += `
            <div style="background: rgba(0,0,0,0.3); border-radius: 0.5rem; padding: 0.5rem; border-left: 3px solid ${elem.color};">
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                    <span style="font-weight: bold; color: ${elem.color};">${elem.letter}</span>
                    <span style="color: #9ca3af; font-size: 0.75rem;">${elem.name}</span>
                </div>
                <p style="color: #6b7280; font-size: 0.75rem;">${elem.description}</p>
            </div>
        `;
    });

    container.innerHTML = html;
}

// ========== INITIALIZATION ==========
function init() {
    renderOuterDirButtons();
    renderDirectionGuide();
    renderElementGuide();
    renderTemplateList();
    updateUI();
}

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    const templateSelector = document.querySelector('.template-selector');
    if (templateSelector && !templateSelector.contains(e.target)) {
        if (showTemplateDropdown) {
            showTemplateDropdown = false;
            document.getElementById('templateDropdown').classList.remove('show');
            document.getElementById('templateBtn').classList.remove('active');
            document.getElementById('templateArrow').textContent = '▼';
        }
    }
});

// Start
init();

