/**
 * Q-MIND (Quantum-Mindset Insight Navigation Design)
 * 양자 학습 모델 시각화 및 상호작용
 * 
 * 핵심 개념:
 * - 중첩(Superposition): 학습자의 인지 상태 시각화
 * - 양자 요동(Fluctuation): 에너지 파형 모니터링
 * - 터널링(Tunneling): 통찰 유도
 * - 미래 인력(Future Attractor): 목표 앵커링
 * - 붕괴(Collapse): 이해 확정 시각화
 */

// ==================== 전역 상태 ====================
const QuantumState = {
    // 학습자 상태
    learner: {
        energyLevel: 30,
        fluctuationIntensity: 0.5,
        completedTasks: 0,
        totalTasks: 9,
        selectedEmotion: null,
        level: 1
    },
    
    // 애니메이션 상태
    animation: {
        particlesRunning: false,
        waveformRunning: false,
        collapseRunning: false
    },
    
    // 입자 시스템
    particles: [],
    
    // 연결된 에이전트
    entangledAgents: []
};

// 역설적 질문 목록 (터널링 인젝션용)
const ParadoxQuestions = [
    "이 문제가 이미 해결되었다면, 해결 직전에 뭘 깨달았을까?",
    "정답을 모른다고 가정하고, 가장 멍청한 시도를 해본다면?",
    "이 개념이 소리를 낸다면 어떤 소리일까? 날카로운? 둥근?",
    "반대로 생각해봐. 이걸 절대 이해 못하려면 어떻게 해야 할까?",
    "5살 아이에게 이걸 설명한다면 어떻게 말할까?",
    "이 문제가 사람이라면, 어떤 성격일까?",
    "완전히 다른 분야에서 비슷한 패턴을 찾아볼 수 있을까?",
    "지금 막혀있는 지점을 그림으로 그린다면 어떤 모양일까?",
    "미래의 내가 지금의 나에게 힌트 하나를 준다면, 뭘까?",
    "이 문제를 꿈에서 풀었다면, 꿈속에선 어떻게 풀었을까?"
];

// ==================== 초기화 ====================
document.addEventListener('DOMContentLoaded', () => {
    loadSavedState();
    initParticleCanvas();
    initWaveformCanvas();
    initCollapseCanvas();
    initProjectInteractions();
    initEmotionSelector();
    initTunnelingInjection();
    updateEnergyDisplay();
    
    // 연결된 에이전트 정보 로드
    if (typeof entangledAgentsData !== 'undefined') {
        QuantumState.entangledAgents = entangledAgentsData;
    }
});

// ==================== 상태 저장/로드 ====================
function saveState() {
    const stateToSave = {
        learner: QuantumState.learner,
        timestamp: Date.now()
    };
    localStorage.setItem('qmind_16_ai_gardener', JSON.stringify(stateToSave));
}

function loadSavedState() {
    const saved = localStorage.getItem('qmind_16_ai_gardener');
    if (saved) {
        try {
            const parsed = JSON.parse(saved);
            QuantumState.learner = { ...QuantumState.learner, ...parsed.learner };
            
            // 체크박스 상태 복원
            restoreCheckboxStates();
        } catch (e) {
            console.error('[Q-MIND] Error loading saved state:', e);
        }
    }
}

function restoreCheckboxStates() {
    const checkboxes = document.querySelectorAll('.project-checkbox');
    let completed = 0;
    
    checkboxes.forEach((cb, index) => {
        const key = `task_${cb.dataset.projectId || index}`;
        const saved = localStorage.getItem(key);
        if (saved === 'true') {
            cb.checked = true;
            completed++;
        }
    });
    
    QuantumState.learner.completedTasks = completed;
    QuantumState.learner.totalTasks = checkboxes.length;
}

// ==================== 입자 구름 (Probability Field) ====================
function initParticleCanvas() {
    const canvas = document.getElementById('particleCanvas');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    const rect = canvas.parentElement.getBoundingClientRect();
    canvas.width = rect.width;
    canvas.height = rect.height;
    
    // 입자 생성
    const particleCount = 100;
    for (let i = 0; i < particleCount; i++) {
        QuantumState.particles.push(createParticle(canvas.width, canvas.height));
    }
    
    QuantumState.animation.particlesRunning = true;
    animateParticles(canvas, ctx);
    
    // 리사이즈 핸들러
    window.addEventListener('resize', () => {
        const newRect = canvas.parentElement.getBoundingClientRect();
        canvas.width = newRect.width;
        canvas.height = newRect.height;
    });
}

function createParticle(maxX, maxY) {
    const hue = Math.random() > 0.5 ? 270 : 190; // 보라 또는 청록
    return {
        x: Math.random() * maxX,
        y: Math.random() * maxY,
        size: Math.random() * 3 + 1,
        speedX: (Math.random() - 0.5) * 0.5,
        speedY: (Math.random() - 0.5) * 0.5,
        hue: hue,
        alpha: Math.random() * 0.5 + 0.3,
        pulse: Math.random() * Math.PI * 2
    };
}

function animateParticles(canvas, ctx) {
    if (!QuantumState.animation.particlesRunning) return;
    
    ctx.fillStyle = 'rgba(10, 15, 26, 0.1)';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    
    const centerX = canvas.width / 2;
    const centerY = canvas.height / 2;
    const energyFactor = QuantumState.learner.energyLevel / 100;
    
    QuantumState.particles.forEach(p => {
        // 에너지에 따른 속도 조절
        const speedMult = 0.5 + energyFactor * 1.5;
        p.x += p.speedX * speedMult;
        p.y += p.speedY * speedMult;
        
        // 중심으로의 약한 인력
        const dx = centerX - p.x;
        const dy = centerY - p.y;
        const dist = Math.sqrt(dx * dx + dy * dy);
        if (dist > 50) {
            p.x += dx * 0.001;
            p.y += dy * 0.001;
        }
        
        // 경계 처리
        if (p.x < 0 || p.x > canvas.width) p.speedX *= -1;
        if (p.y < 0 || p.y > canvas.height) p.speedY *= -1;
        
        // 펄스 효과
        p.pulse += 0.05;
        const pulseSize = p.size + Math.sin(p.pulse) * 0.5;
        
        // 그리기
        ctx.beginPath();
        ctx.arc(p.x, p.y, pulseSize, 0, Math.PI * 2);
        ctx.fillStyle = `hsla(${p.hue}, 80%, 70%, ${p.alpha})`;
        ctx.fill();
        
        // 글로우 효과
        ctx.beginPath();
        ctx.arc(p.x, p.y, pulseSize * 2, 0, Math.PI * 2);
        ctx.fillStyle = `hsla(${p.hue}, 80%, 70%, ${p.alpha * 0.2})`;
        ctx.fill();
    });
    
    // 연결선 (근접한 입자들 연결)
    ctx.strokeStyle = 'rgba(179, 136, 255, 0.1)';
    ctx.lineWidth = 0.5;
    for (let i = 0; i < QuantumState.particles.length; i++) {
        for (let j = i + 1; j < QuantumState.particles.length; j++) {
            const p1 = QuantumState.particles[i];
            const p2 = QuantumState.particles[j];
            const dx = p1.x - p2.x;
            const dy = p1.y - p2.y;
            const dist = Math.sqrt(dx * dx + dy * dy);
            
            if (dist < 80) {
                ctx.beginPath();
                ctx.moveTo(p1.x, p1.y);
                ctx.lineTo(p2.x, p2.y);
                ctx.stroke();
            }
        }
    }
    
    requestAnimationFrame(() => animateParticles(canvas, ctx));
}

// ==================== 에너지 파형 (Fluctuation Monitor) ====================
function initWaveformCanvas() {
    const canvas = document.getElementById('waveformCanvas');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    canvas.width = canvas.offsetWidth;
    canvas.height = canvas.offsetHeight;
    
    QuantumState.animation.waveformRunning = true;
    animateWaveform(canvas, ctx);
}

let waveOffset = 0;
function animateWaveform(canvas, ctx) {
    if (!QuantumState.animation.waveformRunning) return;
    
    ctx.fillStyle = '#0a0f1a';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    
    const energyLevel = QuantumState.learner.energyLevel;
    const fluctuation = QuantumState.learner.fluctuationIntensity;
    
    // 그리드 라인
    ctx.strokeStyle = 'rgba(65, 90, 119, 0.3)';
    ctx.lineWidth = 1;
    for (let y = 0; y < canvas.height; y += 30) {
        ctx.beginPath();
        ctx.moveTo(0, y);
        ctx.lineTo(canvas.width, y);
        ctx.stroke();
    }
    
    // 파형 그리기
    const waves = [
        { color: 'rgba(179, 136, 255, 0.8)', amp: 30, freq: 0.02, phase: 0 },
        { color: 'rgba(0, 229, 255, 0.6)', amp: 20, freq: 0.03, phase: Math.PI / 3 },
        { color: 'rgba(255, 64, 129, 0.4)', amp: 15, freq: 0.04, phase: Math.PI / 2 }
    ];
    
    waves.forEach(wave => {
        ctx.beginPath();
        ctx.strokeStyle = wave.color;
        ctx.lineWidth = 2;
        
        const baseY = canvas.height / 2;
        const amplitude = wave.amp * (0.5 + energyLevel / 100) * fluctuation;
        
        for (let x = 0; x < canvas.width; x++) {
            const y = baseY + 
                Math.sin((x * wave.freq) + waveOffset + wave.phase) * amplitude +
                Math.sin((x * wave.freq * 2) + waveOffset * 1.5) * (amplitude * 0.3) +
                (Math.random() - 0.5) * fluctuation * 5;
            
            if (x === 0) {
                ctx.moveTo(x, y);
            } else {
                ctx.lineTo(x, y);
            }
        }
        ctx.stroke();
    });
    
    // 에너지 레벨 표시
    const gradient = ctx.createLinearGradient(0, 0, 0, canvas.height);
    gradient.addColorStop(0, 'rgba(179, 136, 255, 0.1)');
    gradient.addColorStop(1, 'transparent');
    
    ctx.fillStyle = gradient;
    ctx.fillRect(0, 0, canvas.width * (energyLevel / 100), canvas.height);
    
    waveOffset += 0.05;
    requestAnimationFrame(() => animateWaveform(canvas, ctx));
}

// ==================== 붕괴 시각화 (Collapse Canvas) ====================
function initCollapseCanvas() {
    const canvas = document.getElementById('collapseCanvas');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    canvas.width = canvas.offsetWidth;
    canvas.height = canvas.offsetHeight;
    
    // 초기 상태 그리기
    drawCollapseState(canvas, ctx, 'superposition');
}

function drawCollapseState(canvas, ctx, state) {
    ctx.fillStyle = '#0a0f1a';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    
    const centerX = canvas.width / 2;
    const centerY = canvas.height / 2;
    
    if (state === 'superposition') {
        // 중첩 상태: 흐릿한 확률 구름
        for (let i = 0; i < 50; i++) {
            const angle = Math.random() * Math.PI * 2;
            const radius = Math.random() * 60 + 20;
            const x = centerX + Math.cos(angle) * radius;
            const y = centerY + Math.sin(angle) * radius;
            
            ctx.beginPath();
            ctx.arc(x, y, 3, 0, Math.PI * 2);
            ctx.fillStyle = `rgba(179, 136, 255, ${Math.random() * 0.5 + 0.2})`;
            ctx.fill();
        }
        
        // 중심 글로우
        const gradient = ctx.createRadialGradient(centerX, centerY, 0, centerX, centerY, 80);
        gradient.addColorStop(0, 'rgba(179, 136, 255, 0.3)');
        gradient.addColorStop(1, 'transparent');
        ctx.fillStyle = gradient;
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        
    } else if (state === 'collapsed') {
        // 붕괴 상태: 결정체
        drawCrystal(ctx, centerX, centerY);
        
        // 신경망 연결선
        drawNeuralConnections(ctx, canvas.width, canvas.height);
    }
}

function drawCrystal(ctx, x, y) {
    const size = 30;
    
    // 글로우
    ctx.shadowBlur = 20;
    ctx.shadowColor = '#00e676';
    
    // 결정체 형태
    ctx.beginPath();
    ctx.moveTo(x, y - size);
    ctx.lineTo(x + size * 0.7, y - size * 0.3);
    ctx.lineTo(x + size * 0.7, y + size * 0.3);
    ctx.lineTo(x, y + size);
    ctx.lineTo(x - size * 0.7, y + size * 0.3);
    ctx.lineTo(x - size * 0.7, y - size * 0.3);
    ctx.closePath();
    
    const gradient = ctx.createLinearGradient(x - size, y - size, x + size, y + size);
    gradient.addColorStop(0, '#00e676');
    gradient.addColorStop(0.5, '#00e5ff');
    gradient.addColorStop(1, '#b388ff');
    
    ctx.fillStyle = gradient;
    ctx.fill();
    
    ctx.shadowBlur = 0;
}

function drawNeuralConnections(ctx, width, height) {
    const nodes = [];
    const nodeCount = 8;
    
    // 노드 생성
    for (let i = 0; i < nodeCount; i++) {
        nodes.push({
            x: Math.random() * (width - 100) + 50,
            y: Math.random() * (height - 60) + 30
        });
    }
    
    // 연결선
    ctx.strokeStyle = 'rgba(0, 230, 118, 0.3)';
    ctx.lineWidth = 1;
    
    nodes.forEach((n1, i) => {
        nodes.forEach((n2, j) => {
            if (i < j && Math.random() > 0.5) {
                ctx.beginPath();
                ctx.moveTo(n1.x, n1.y);
                ctx.lineTo(n2.x, n2.y);
                ctx.stroke();
            }
        });
    });
    
    // 노드
    nodes.forEach(n => {
        ctx.beginPath();
        ctx.arc(n.x, n.y, 4, 0, Math.PI * 2);
        ctx.fillStyle = '#00e676';
        ctx.fill();
    });
}

// 붕괴 애니메이션 트리거
function triggerCollapse() {
    const canvas = document.getElementById('collapseCanvas');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    const message = document.querySelector('.collapse-message');
    
    // 애니메이션
    let progress = 0;
    const animate = () => {
        progress += 0.02;
        
        ctx.fillStyle = 'rgba(10, 15, 26, 0.1)';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        
        if (progress < 1) {
            // 수렴 중
            const centerX = canvas.width / 2;
            const centerY = canvas.height / 2;
            
            for (let i = 0; i < 30; i++) {
                const angle = (Math.PI * 2 / 30) * i;
                const currentRadius = 100 * (1 - progress);
                const x = centerX + Math.cos(angle) * currentRadius;
                const y = centerY + Math.sin(angle) * currentRadius;
                
                ctx.beginPath();
                ctx.arc(x, y, 3, 0, Math.PI * 2);
                ctx.fillStyle = `rgba(179, 136, 255, ${0.5 + progress * 0.5})`;
                ctx.fill();
            }
            
            requestAnimationFrame(animate);
        } else {
            // 붕괴 완료
            drawCollapseState(canvas, ctx, 'collapsed');
            
            // 메시지 표시
            if (message) {
                message.classList.add('visible');
            }
            
            // 레벨업
            QuantumState.learner.level++;
            updateLevelDisplay();
            saveState();
        }
    };
    
    animate();
}

// ==================== 프로젝트 상호작용 ====================
function initProjectInteractions() {
    const projectHeaders = document.querySelectorAll('.project-header');
    const checkboxes = document.querySelectorAll('.project-checkbox');
    
    // 프로젝트 접기/펼치기
    projectHeaders.forEach(header => {
        header.addEventListener('click', (e) => {
            if (e.target.classList.contains('project-checkbox')) return;
            
            const card = header.closest('.project-card');
            card.classList.toggle('collapsed');
        });
    });
    
    // 체크박스 변경
    checkboxes.forEach((cb, index) => {
        cb.addEventListener('change', () => {
            const key = `task_${cb.dataset.projectId || index}`;
            localStorage.setItem(key, cb.checked);
            
            // 에너지 레벨 업데이트
            updateEnergyFromTasks();
            
            // 양자 요동 증가 (작업 시)
            if (cb.checked) {
                addFluctuation(15);
                
                // 모든 작업 완료 체크
                checkAllTasksCompleted();
            }
        });
    });
}

function updateEnergyFromTasks() {
    const checkboxes = document.querySelectorAll('.project-checkbox');
    let completed = 0;
    
    checkboxes.forEach(cb => {
        if (cb.checked) completed++;
    });
    
    QuantumState.learner.completedTasks = completed;
    QuantumState.learner.totalTasks = checkboxes.length;
    
    // 에너지 계산 (완료율 기반)
    const completionRate = completed / checkboxes.length;
    QuantumState.learner.energyLevel = Math.min(100, 30 + completionRate * 70);
    
    updateEnergyDisplay();
    saveState();
}

function addFluctuation(amount) {
    QuantumState.learner.fluctuationIntensity = Math.min(2, 
        QuantumState.learner.fluctuationIntensity + amount / 100
    );
    
    // 시간이 지나면 감소
    setTimeout(() => {
        QuantumState.learner.fluctuationIntensity = Math.max(0.3,
            QuantumState.learner.fluctuationIntensity - amount / 200
        );
    }, 5000);
    
    // 터널링 알림 체크
    checkTunnelingAlert();
}

function checkTunnelingAlert() {
    const alert = document.querySelector('.tunneling-alert');
    if (!alert) return;
    
    if (QuantumState.learner.energyLevel > 70 && QuantumState.learner.fluctuationIntensity > 1) {
        alert.classList.add('active');
    } else {
        alert.classList.remove('active');
    }
}

function checkAllTasksCompleted() {
    const checkboxes = document.querySelectorAll('.project-checkbox');
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
    
    if (allChecked) {
        triggerCollapse();
    }
}

// ==================== 에너지 디스플레이 업데이트 ====================
function updateEnergyDisplay() {
    const levelEl = document.querySelector('.energy-level');
    const barFill = document.querySelector('.energy-bar-fill');
    
    if (levelEl) {
        levelEl.textContent = `${Math.round(QuantumState.learner.energyLevel)}%`;
    }
    
    if (barFill) {
        barFill.style.width = `${QuantumState.learner.energyLevel}%`;
    }
}

function updateLevelDisplay() {
    const levelEl = document.querySelector('.level-indicator .level');
    if (levelEl) {
        levelEl.textContent = `Level ${QuantumState.learner.level}`;
    }
}

// ==================== 감정 선택기 (미래 앵커링) ====================
function initEmotionSelector() {
    const emotionBtns = document.querySelectorAll('.emotion-btn');
    
    emotionBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            // 기존 선택 해제
            emotionBtns.forEach(b => b.classList.remove('selected'));
            
            // 새 선택
            btn.classList.add('selected');
            QuantumState.learner.selectedEmotion = btn.dataset.emotion;
            
            // 에너지 부스트
            addFluctuation(10);
            
            // 시각 효과
            flashScreen(btn.dataset.color || '#00e5ff');
            
            saveState();
        });
    });
}

function flashScreen(color) {
    const flash = document.createElement('div');
    flash.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: ${color};
        opacity: 0.3;
        pointer-events: none;
        z-index: 9999;
        animation: flashFade 0.5s ease forwards;
    `;
    
    document.body.appendChild(flash);
    
    setTimeout(() => flash.remove(), 500);
}

// CSS 애니메이션 추가
const flashStyle = document.createElement('style');
flashStyle.textContent = `
    @keyframes flashFade {
        from { opacity: 0.3; }
        to { opacity: 0; }
    }
`;
document.head.appendChild(flashStyle);

// ==================== 터널링 인젝션 ====================
function initTunnelingInjection() {
    const shiftBtn = document.querySelector('.shift-perspective-btn');
    const questionEl = document.querySelector('.paradox-question');
    
    if (shiftBtn && questionEl) {
        // 초기 질문
        showRandomQuestion(questionEl);
        
        shiftBtn.addEventListener('click', () => {
            showRandomQuestion(questionEl);
            addFluctuation(20);
            
            // 버튼 효과
            shiftBtn.style.transform = 'scale(0.95)';
            setTimeout(() => {
                shiftBtn.style.transform = 'scale(1)';
            }, 100);
        });
    }
}

function showRandomQuestion(element) {
    const randomIndex = Math.floor(Math.random() * ParadoxQuestions.length);
    const question = ParadoxQuestions[randomIndex];
    
    // 페이드 효과
    element.style.opacity = 0;
    setTimeout(() => {
        element.textContent = `"${question}"`;
        element.style.opacity = 1;
    }, 200);
}

// ==================== 관측 시작 버튼 ====================
function startObservation() {
    const btn = document.querySelector('.observe-btn');
    const stateIndicator = document.querySelector('.state-indicator');
    
    if (btn) {
        btn.textContent = '관측 중...';
        btn.disabled = true;
    }
    
    if (stateIndicator) {
        stateIndicator.textContent = '|ψ⟩ 관측 진행 중';
    }
    
    // 에너지 부스트
    addFluctuation(25);
    
    // 프로젝트 섹션으로 스크롤
    const projectsSection = document.querySelector('.projects-section');
    if (projectsSection) {
        projectsSection.scrollIntoView({ behavior: 'smooth' });
    }
    
    setTimeout(() => {
        if (btn) {
            btn.textContent = '🔭 관측 시작';
            btn.disabled = false;
        }
    }, 2000);
}

// ==================== 유틸리티 ====================
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// 전역 함수 노출
window.startObservation = startObservation;
window.triggerCollapse = triggerCollapse;


