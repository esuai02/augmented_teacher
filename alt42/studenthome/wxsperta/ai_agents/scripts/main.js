// 부드러운 스크롤 기능
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// 카드 애니메이션
function animateCardsOnScroll() {
    const cards = document.querySelectorAll('.agent-card');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '0';
                entry.target.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    entry.target.style.transition = 'all 0.5s ease';
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }, 100);
                observer.unobserve(entry.target);
            }
        });
    });

    cards.forEach(card => {
        observer.observe(card);
    });
}

// 프로젝트 토글 기능
function initProjectToggle() {
    const projectHeaders = document.querySelectorAll('.project-header');
    projectHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const subProjects = this.nextElementSibling;
            if (subProjects && subProjects.classList.contains('sub-projects')) {
                subProjects.classList.toggle('collapsed');
                this.classList.toggle('collapsed');
            }
        });
    });
}

// 프로젝트 진행도 추적
function initProgressTracking() {
    const checkboxes = document.querySelectorAll('.project-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const projectId = this.dataset.projectId;
            const isCompleted = this.checked;
            saveProjectProgress(projectId, isCompleted);
            updateProgressBar();
        });
    });
}

// 진행도 저장
function saveProjectProgress(projectId, isCompleted) {
    const progress = JSON.parse(localStorage.getItem('projectProgress') || '{}');
    progress[projectId] = isCompleted;
    localStorage.setItem('projectProgress', JSON.stringify(progress));
    
    // API로도 저장 (비동기)
    fetch('/ai_agents/api/progress_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            project_id: projectId,
            is_completed: isCompleted,
            item_type: projectId.includes('-p') && !projectId.includes('-p1-') ? 'project' : 'subproject'
        })
    }).catch(error => console.error('진행도 저장 실패:', error));
}

// 진행도 불러오기
function loadProjectProgress() {
    const progress = JSON.parse(localStorage.getItem('projectProgress') || '{}');
    Object.keys(progress).forEach(projectId => {
        const checkbox = document.querySelector(`[data-project-id="${projectId}"]`);
        if (checkbox) {
            checkbox.checked = progress[projectId];
        }
    });
}

// 진행도 바 업데이트
function updateProgressBar() {
    const totalCheckboxes = document.querySelectorAll('.project-checkbox').length;
    const checkedCheckboxes = document.querySelectorAll('.project-checkbox:checked').length;
    const progressPercent = totalCheckboxes > 0 ? (checkedCheckboxes / totalCheckboxes) * 100 : 0;
    
    const progressBar = document.querySelector('.progress-bar');
    if (progressBar) {
        progressBar.style.width = progressPercent + '%';
        progressBar.textContent = Math.round(progressPercent) + '%';
    }
}

// 프로젝트 간 연동 시각화
function initProjectConnections() {
    const connectionMap = {
        '01_time_capsule': ['03_growth_elevator', '15_timecapsule_ceo'],
        '02_timeline_synthesizer': ['04_performance_engine', '07_daily_command'],
        '03_growth_elevator': ['04_performance_engine', '13_growth_trigger'],
        '04_performance_engine': ['15_timecapsule_ceo'],
        '05_motivation_engine': ['08_inner_branding', '13_growth_trigger'],
        '06_swot_analyzer': ['14_competitive_strategist'],
        '07_daily_command': ['11_execution_pipeline'],
        '08_inner_branding': ['12_external_branding'],
        '09_vertical_explorer': ['16_ai_gardener'],
        '10_resource_gardener': ['18_info_hub', '19_knowledge_network'],
        '11_execution_pipeline': ['21_flexible_backbone'],
        '12_external_branding': ['13_growth_trigger'],
        '16_ai_gardener': ['19_knowledge_network'],
        '17_neural_architect': ['19_knowledge_network'],
        '18_info_hub': ['20_knowledge_crystal'],
        '19_knowledge_network': ['20_knowledge_crystal'],
        '21_flexible_backbone': ['11_execution_pipeline']
    };
    
    // 연결 시각화 로직 구현
    const cards = document.querySelectorAll('.agent-card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            const cardId = this.dataset.agentId;
            const connections = connectionMap[cardId] || [];
            highlightConnections(cardId, connections);
        });
        
        card.addEventListener('mouseleave', function() {
            clearHighlights();
        });
    });
}

function highlightConnections(sourceId, targetIds) {
    // 모든 카드를 흐리게
    document.querySelectorAll('.agent-card').forEach(card => {
        card.classList.add('dimmed');
    });
    
    // 소스와 타겟 카드 하이라이트
    const sourceCard = document.querySelector(`[data-agent-id="${sourceId}"]`);
    if (sourceCard) {
        sourceCard.classList.remove('dimmed');
        sourceCard.classList.add('highlighted');
    }
    
    targetIds.forEach(targetId => {
        const targetCard = document.querySelector(`[data-agent-id="${targetId}"]`);
        if (targetCard) {
            targetCard.classList.remove('dimmed');
            targetCard.classList.add('connected');
        }
    });
}

function clearHighlights() {
    document.querySelectorAll('.agent-card').forEach(card => {
        card.classList.remove('dimmed', 'highlighted', 'connected');
    });
}

// 페이지 로드 시 초기화
document.addEventListener('DOMContentLoaded', function() {
    animateCardsOnScroll();
    initProjectToggle();
    initProgressTracking();
    loadProjectProgress();
    updateProgressBar();
    initProjectConnections();
});