/**
 * Q-MIND (Quantum-Mindset Insight Navigation Design) - 공통 스크립트
 * 양자 학습 모델 시각화 및 상호작용
 */

// ==================== 전역 상태 ====================
const QuantumState = {
    learner: {
        energyLevel: 30,
        fluctuationIntensity: 0.5,
        completedTasks: 0,
        totalTasks: 9,
        selectedEmotion: null,
        level: 1
    },
    animation: {
        particlesRunning: false,
        waveformRunning: false,
        collapseRunning: false
    },
    particles: [],
    entangledAgents: [],
    agentId: ''
};

// 역설적 질문 목록
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
    // 에이전트 ID 설정
    if (typeof agentConfig !== 'undefined') {
        QuantumState.agentId = agentConfig.id;
    }
    
    loadSavedState();
    initParticleCanvas();
    initWaveformCanvas();
    initCollapseCanvas();
    initProjectInteractions();
    initEmotionSelector();
    initTunnelingInjection();
    updateEnergyDisplay();
    
    if (typeof entangledAgentsData !== 'undefined') {
        QuantumState.entangledAgents = entangledAgentsData;
    }
});

// ==================== 상태 저장/로드 ====================
function saveState() {
    const key = `qmind_${QuantumState.agentId}`;
    const stateToSave = {
        learner: QuantumState.learner,
        timestamp: Date.now()
    };
    localStorage.setItem(key, JSON.stringify(stateToSave));
}

function loadSavedState() {
    const key = `qmind_${QuantumState.agentId}`;
    const saved = localStorage.getItem(key);
    if (saved) {
        try {
            const parsed = JSON.parse(saved);
            QuantumState.learner = { ...QuantumState.learner, ...parsed.learner };
            restoreCheckboxStates();
        } catch (e) {
            console.error('[Q-MIND] Error loading state:', e);
        }
    }
}

function restoreCheckboxStates() {
    const checkboxes = document.querySelectorAll('.project-checkbox');
    let completed = 0;
    
    checkboxes.forEach((cb, index) => {
        const taskKey = `task_${QuantumState.agentId}_${cb.dataset.projectId || index}`;
        if (localStorage.getItem(taskKey) === 'true') {
            cb.checked = true;
            completed++;
        }
    });
    
    QuantumState.learner.completedTasks = completed;
    QuantumState.learner.totalTasks = checkboxes.length;
}

// ==================== 입자 구름 ====================
function initParticleCanvas() {
    const canvas = document.getElementById('particleCanvas');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    const rect = canvas.parentElement.getBoundingClientRect();
    canvas.width = rect.width;
    canvas.height = rect.height;
    
    for (let i = 0; i < 100; i++) {
        QuantumState.particles.push(createParticle(canvas.width, canvas.height));
    }
    
    QuantumState.animation.particlesRunning = true;
    animateParticles(canvas, ctx);
    
    window.addEventListener('resize', () => {
        const newRect = canvas.parentElement.getBoundingClientRect();
        canvas.width = newRect.width;
        canvas.height = newRect.height;
    });
}

function createParticle(maxX, maxY) {
    const hue = Math.random() > 0.5 ? 270 : 190;
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
        const speedMult = 0.5 + energyFactor * 1.5;
        p.x += p.speedX * speedMult;
        p.y += p.speedY * speedMult;
        
        const dx = centerX - p.x;
        const dy = centerY - p.y;
        const dist = Math.sqrt(dx * dx + dy * dy);
        if (dist > 50) {
            p.x += dx * 0.001;
            p.y += dy * 0.001;
        }
        
        if (p.x < 0 || p.x > canvas.width) p.speedX *= -1;
        if (p.y < 0 || p.y > canvas.height) p.speedY *= -1;
        
        p.pulse += 0.05;
        const pulseSize = p.size + Math.sin(p.pulse) * 0.5;
        
        ctx.beginPath();
        ctx.arc(p.x, p.y, pulseSize, 0, Math.PI * 2);
        ctx.fillStyle = `hsla(${p.hue}, 80%, 70%, ${p.alpha})`;
        ctx.fill();
        
        ctx.beginPath();
        ctx.arc(p.x, p.y, pulseSize * 2, 0, Math.PI * 2);
        ctx.fillStyle = `hsla(${p.hue}, 80%, 70%, ${p.alpha * 0.2})`;
        ctx.fill();
    });
    
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

// ==================== 에너지 파형 ====================
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
    
    ctx.strokeStyle = 'rgba(65, 90, 119, 0.3)';
    ctx.lineWidth = 1;
    for (let y = 0; y < canvas.height; y += 30) {
        ctx.beginPath();
        ctx.moveTo(0, y);
        ctx.lineTo(canvas.width, y);
        ctx.stroke();
    }
    
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
            
            if (x === 0) ctx.moveTo(x, y);
            else ctx.lineTo(x, y);
        }
        ctx.stroke();
    });
    
    const gradient = ctx.createLinearGradient(0, 0, 0, canvas.height);
    gradient.addColorStop(0, 'rgba(179, 136, 255, 0.1)');
    gradient.addColorStop(1, 'transparent');
    ctx.fillStyle = gradient;
    ctx.fillRect(0, 0, canvas.width * (energyLevel / 100), canvas.height);
    
    waveOffset += 0.05;
    requestAnimationFrame(() => animateWaveform(canvas, ctx));
}

// ==================== 붕괴 캔버스 ====================
function initCollapseCanvas() {
    const canvas = document.getElementById('collapseCanvas');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    canvas.width = canvas.offsetWidth;
    canvas.height = canvas.offsetHeight;
    
    drawCollapseState(canvas, ctx, 'superposition');
}

function drawCollapseState(canvas, ctx, state) {
    ctx.fillStyle = '#0a0f1a';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    
    const centerX = canvas.width / 2;
    const centerY = canvas.height / 2;
    
    if (state === 'superposition') {
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
        
        const gradient = ctx.createRadialGradient(centerX, centerY, 0, centerX, centerY, 80);
        gradient.addColorStop(0, 'rgba(179, 136, 255, 0.3)');
        gradient.addColorStop(1, 'transparent');
        ctx.fillStyle = gradient;
        ctx.fillRect(0, 0, canvas.width, canvas.height);
    } else if (state === 'collapsed') {
        drawCrystal(ctx, centerX, centerY);
        drawNeuralConnections(ctx, canvas.width, canvas.height);
    }
}

function drawCrystal(ctx, x, y) {
    const size = 30;
    ctx.shadowBlur = 20;
    ctx.shadowColor = '#00e676';
    
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
    for (let i = 0; i < 8; i++) {
        nodes.push({
            x: Math.random() * (width - 100) + 50,
            y: Math.random() * (height - 60) + 30
        });
    }
    
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
    
    nodes.forEach(n => {
        ctx.beginPath();
        ctx.arc(n.x, n.y, 4, 0, Math.PI * 2);
        ctx.fillStyle = '#00e676';
        ctx.fill();
    });
}

function triggerCollapse() {
    const canvas = document.getElementById('collapseCanvas');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    const message = document.querySelector('.collapse-message');
    
    let progress = 0;
    const animate = () => {
        progress += 0.02;
        
        ctx.fillStyle = 'rgba(10, 15, 26, 0.1)';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        
        if (progress < 1) {
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
            drawCollapseState(canvas, ctx, 'collapsed');
            if (message) message.classList.add('visible');
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
    
    projectHeaders.forEach(header => {
        header.addEventListener('click', (e) => {
            if (e.target.classList.contains('project-checkbox')) return;
            const card = header.closest('.project-card');
            card.classList.toggle('collapsed');
        });
    });
    
    checkboxes.forEach((cb, index) => {
        cb.addEventListener('change', () => {
            const taskKey = `task_${QuantumState.agentId}_${cb.dataset.projectId || index}`;
            localStorage.setItem(taskKey, cb.checked);
            
            updateEnergyFromTasks();
            
            if (cb.checked) {
                addFluctuation(15);
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
    
    const completionRate = completed / checkboxes.length;
    QuantumState.learner.energyLevel = Math.min(100, 30 + completionRate * 70);
    
    updateEnergyDisplay();
    saveState();
}

function addFluctuation(amount) {
    QuantumState.learner.fluctuationIntensity = Math.min(2, 
        QuantumState.learner.fluctuationIntensity + amount / 100
    );
    
    setTimeout(() => {
        QuantumState.learner.fluctuationIntensity = Math.max(0.3,
            QuantumState.learner.fluctuationIntensity - amount / 200
        );
    }, 5000);
    
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
    if (allChecked) triggerCollapse();
}

// ==================== 디스플레이 업데이트 ====================
function updateEnergyDisplay() {
    const levelEl = document.querySelector('.energy-level');
    const barFill = document.querySelector('.energy-bar-fill');
    
    if (levelEl) levelEl.textContent = `${Math.round(QuantumState.learner.energyLevel)}%`;
    if (barFill) barFill.style.width = `${QuantumState.learner.energyLevel}%`;
}

function updateLevelDisplay() {
    const levelEl = document.querySelector('.level-indicator .level');
    if (levelEl) levelEl.textContent = `Level ${QuantumState.learner.level}`;
}

// ==================== 감정 선택기 ====================
function initEmotionSelector() {
    const emotionBtns = document.querySelectorAll('.emotion-btn');
    
    emotionBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            emotionBtns.forEach(b => b.classList.remove('selected'));
            btn.classList.add('selected');
            QuantumState.learner.selectedEmotion = btn.dataset.emotion;
            addFluctuation(10);
            flashScreen(btn.dataset.color || '#00e5ff');
            saveState();
        });
    });
}

function flashScreen(color) {
    const flash = document.createElement('div');
    flash.style.cssText = `
        position: fixed; top: 0; left: 0; right: 0; bottom: 0;
        background: ${color}; opacity: 0.3; pointer-events: none;
        z-index: 9999; animation: flashFade 0.5s ease forwards;
    `;
    document.body.appendChild(flash);
    setTimeout(() => flash.remove(), 500);
}

// ==================== 터널링 인젝션 ====================
function initTunnelingInjection() {
    const shiftBtn = document.querySelector('.shift-perspective-btn');
    const questionEl = document.querySelector('.paradox-question');
    
    if (shiftBtn && questionEl) {
        showRandomQuestion(questionEl);
        
        shiftBtn.addEventListener('click', () => {
            showRandomQuestion(questionEl);
            addFluctuation(20);
            shiftBtn.style.transform = 'scale(0.95)';
            setTimeout(() => shiftBtn.style.transform = 'scale(1)', 100);
        });
    }
}

function showRandomQuestion(element) {
    const randomIndex = Math.floor(Math.random() * ParadoxQuestions.length);
    element.style.opacity = 0;
    setTimeout(() => {
        element.textContent = `"${ParadoxQuestions[randomIndex]}"`;
        element.style.opacity = 1;
    }, 200);
}

// ==================== 관측 시작 ====================
function startObservation() {
    const btn = document.querySelector('.observe-btn');
    const stateIndicator = document.querySelector('.state-indicator');
    
    if (btn) {
        btn.textContent = '관측 중...';
        btn.disabled = true;
    }
    
    if (stateIndicator) stateIndicator.textContent = '|ψ⟩ 관측 진행 중';
    
    addFluctuation(25);
    
    const projectsSection = document.querySelector('.projects-section');
    if (projectsSection) projectsSection.scrollIntoView({ behavior: 'smooth' });
    
    setTimeout(() => {
        if (btn) {
            btn.textContent = '🔭 관측 시작';
            btn.disabled = false;
        }
    }, 2000);
}

window.startObservation = startObservation;
window.triggerCollapse = triggerCollapse;


