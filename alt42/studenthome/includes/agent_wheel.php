<?php
// agent_wheel.php - 21단계 에이전트 휠 메뉴 컴포넌트
// 이 파일은 좌측에 표시되는 에이전트 휠 메뉴를 제공합니다
?>

<style>
    /* 에이전트 휠 메뉴 스타일 */
    .agent-wheel-container {
        width: 180px;
        height: 100vh;
        background: linear-gradient(to bottom, #1e293b, #0f172a, #1e293b);
        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.2);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        position: fixed;
        top: 0;
        left: 0;
        z-index: 1001;
    }

    .wheel-header {
        padding: 28px 2rem;
        background: linear-gradient(to right, #3b82f6, #8b5cf6, #ec4899);
        color: white;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        z-index: 20;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        height: 88px;
        box-sizing: border-box;
    }

    .wheel-header h1 {
        font-size: 14px;
        font-weight: bold;
        text-align: center;
        margin: 0;
    }

    .wheel-header p {
        font-size: 10px;
        text-align: center;
        opacity: 0.9;
        margin-top: 2px;
    }

    .wheel-viewport {
        flex: 1;
        position: relative;
        overflow: hidden;
    }

    .wheel-fade-top {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 60px;
        background: linear-gradient(to bottom, #1e293b, transparent);
        z-index: 30;
        pointer-events: none;
    }

    .wheel-fade-bottom {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 60px;
        background: linear-gradient(to top, #1e293b, transparent);
        z-index: 30;
        pointer-events: none;
    }

    .wheel-nav-up, .wheel-nav-down {
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        z-index: 40;
        background: rgba(255, 255, 255, 0.1);
        border: none;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s;
        color: white;
    }

    .wheel-nav-up:hover, .wheel-nav-down:hover {
        background: rgba(255, 255, 255, 0.2);
    }

    .wheel-nav-up {
        top: 50px;
    }

    .wheel-nav-down {
        bottom: 50px;
    }

    .wheel-items-container {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .wheel-items-wrapper {
        position: relative;
        width: 100%;
        padding: 0 12px;
    }

    .wheel-item {
        position: absolute;
        width: 100%;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }

    .wheel-item.center {
        background: white;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    }

    .wheel-item:not(.center) {
        background: rgba(255, 255, 255, 0.1);
    }

    .wheel-item:not(.center):hover {
        background: rgba(255, 255, 255, 0.15);
    }

    .wheel-item-number {
        font-size: 11px;
        font-weight: bold;
        min-width: 20px;
    }

    .wheel-item.center .wheel-item-number {
        color: #4b5563;
    }

    .wheel-item:not(.center) .wheel-item-number {
        color: rgba(255, 255, 255, 0.6);
    }

    .wheel-item-icon {
        width: 28px;
        height: 28px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
    }

    .wheel-item.center .wheel-item-icon {
        color: white;
    }

    .wheel-item:not(.center) .wheel-item-icon {
        background: rgba(255, 255, 255, 0.1);
        color: rgba(255, 255, 255, 0.7);
    }

    .wheel-item-content {
        flex: 1;
    }

    .wheel-item-label {
        font-size: 11px;
        font-weight: 500;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .wheel-item-desc {
        font-size: 9px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .wheel-item.center .wheel-item-label {
        color: #1f2937;
    }

    .wheel-item.center .wheel-item-desc {
        color: #6b7280;
    }

    .wheel-item:not(.center) .wheel-item-label {
        color: rgba(255, 255, 255, 0.9);
    }

    .wheel-item:not(.center) .wheel-item-desc {
        color: rgba(255, 255, 255, 0.5);
    }

    .wheel-footer {
        padding: 12px;
        background: #1e293b;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .wheel-status {
        text-align: center;
        margin-bottom: 8px;
    }

    .wheel-status-text {
        font-size: 11px;
        font-weight: bold;
        color: white;
    }

    .wheel-indicators {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 4px;
    }

    .wheel-indicator {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        cursor: pointer;
        transition: all 0.2s;
        border: none;
        padding: 0;
    }

    .wheel-indicator:hover {
        background: rgba(255, 255, 255, 0.5);
    }

    .wheel-indicator.active {
        width: 16px;
        height: 6px;
        border-radius: 3px;
        background: #3b82f6;
    }

    /* 메인 컨텐츠 영역 */
    .main-wrapper {
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow-x: hidden;
        margin-left: 180px; /* Fixed sidebar width */
    }
</style>

<!-- 에이전트 휠 메뉴 HTML -->
<div class="agent-wheel-container">
    <div class="wheel-header">
        <h1>🚀 Mathking AI</h1>
        <p>21단계 자동개입 시스템</p>
    </div>

    <div class="wheel-viewport" id="wheelViewport">
        <div class="wheel-fade-top"></div>
        <div class="wheel-fade-bottom"></div>

        <button class="wheel-nav-up" onclick="moveWheel(-1)">▲</button>
        <button class="wheel-nav-down" onclick="moveWheel(1)">▼</button>

        <div class="wheel-items-container">
            <div class="wheel-items-wrapper" id="wheelItemsWrapper">
                <!-- 동적 생성될 아이템들 -->
            </div>
        </div>
    </div>

    <div class="wheel-footer">
        <div class="wheel-status">
            <span class="wheel-status-text" id="wheelStatus">1/21 - 온보딩</span>
        </div>
        <div class="wheel-indicators" id="wheelIndicators">
            <!-- 동적 생성될 인디케이터들 -->
        </div>
    </div>
</div>

<script>
    // 에이전트 휠 데이터
    const agentMenuItems = [
        { id: 1, icon: '👤', label: '온보딩', desc: '학생 프로필 로드' },
        { id: 2, icon: '📅', label: '시험일정 식별', desc: '일상정보 수집' },
        { id: 3, icon: '🎯', label: '목표 및 계획 분석', desc: '분기/주간/오늘' },
        { id: 4, icon: '📖', label: '문제활동 식별', desc: '개념이해/문제풀이' },
        { id: 5, icon: '❤️', label: '학습감정 분석', desc: '감정 상태 분석' },
        { id: 6, icon: '💬', label: '선생님 피드백', desc: '교사 기록' },
        { id: 7, icon: '🔍', label: '상호작용 타게팅', desc: 'REALTIME' },
        { id: 8, icon: '🧠', label: '침착도 분석', desc: 'REALTIME' },
        { id: 9, icon: '📈', label: '학습관리 분석', desc: 'REALTIME' },
        { id: 10, icon: '✏️', label: '개념노트 분석', desc: 'REALTIME' },
        { id: 11, icon: '📄', label: '문제노트 분석', desc: 'REALTIME' },
        { id: 12, icon: '☕', label: '휴식루틴 분석', desc: 'REALTIME' },
        { id: 13, icon: '⚠️', label: '학습이탈 분석', desc: 'REALTIME' },
        { id: 14, icon: '📍', label: '현재위치 평가', desc: 'REALTIME' },
        { id: 15, icon: '🔄', label: '문제 재정의', desc: '개선방안' },
        { id: 16, icon: '🧭', label: '상호작용 준비', desc: '준비/실행' },
        { id: 17, icon: '🚀', label: '잔여활동 조정', desc: '완결성 지원' },
        { id: 18, icon: '🔎', label: '시그너처 루틴', desc: '패턴 발견' },
        { id: 19, icon: '✨', label: '컨텐츠 생성', desc: '맞춤형 생성' },
        { id: 20, icon: '▶️', label: '개입준비', desc: '계획 수립' },
        { id: 21, icon: '⚡', label: '개입실행', desc: '최종 실행' }
    ];

    let wheelSelectedIndex = 0;
    let wheelIsAnimating = false;

    // 에이전트 휠 초기화
    function initAgentWheel() {
        renderWheelItems();
        renderWheelIndicators();
        updateWheelStatus();

        // 휠 이벤트 리스너
        const wheelViewport = document.getElementById('wheelViewport');
        if (wheelViewport) {
            wheelViewport.addEventListener('wheel', handleWheelScroll, { passive: false });
        }

        // 키보드 네비게이션
        document.addEventListener('keydown', function(e) {
            if (wheelIsAnimating) return;

            if (e.key === 'ArrowUp') {
                e.preventDefault();
                moveWheel(-1);
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                moveWheel(1);
            }
        });
    }

    // 휠 아이템 렌더링
    function renderWheelItems() {
        const wrapper = document.getElementById('wheelItemsWrapper');
        if (!wrapper) return;

        wrapper.innerHTML = '';

        agentMenuItems.forEach((item, index) => {
            const itemElement = document.createElement('div');
            itemElement.className = 'wheel-item';
            itemElement.id = `wheel-item-${index}`;

            const itemColor = getAgentColor(item.id);

            itemElement.innerHTML = `
                <span class="wheel-item-number">${item.id}</span>
                <div class="wheel-item-icon" style="background-color: ${index === wheelSelectedIndex ? itemColor : ''};">
                    <span>${item.icon}</span>
                </div>
                <div class="wheel-item-content">
                    <div class="wheel-item-label">${item.label}</div>
                    <div class="wheel-item-desc">${item.desc}</div>
                </div>
            `;

            itemElement.onclick = function() {
                if (index !== wheelSelectedIndex) {
                    animateToIndex(index);
                }
            };

            wrapper.appendChild(itemElement);
        });

        updateWheelPositions();
    }

    // 휠 인디케이터 렌더링
    function renderWheelIndicators() {
        const container = document.getElementById('wheelIndicators');
        if (!container) return;

        container.innerHTML = '';

        agentMenuItems.forEach((item, index) => {
            const indicator = document.createElement('button');
            indicator.className = `wheel-indicator ${index === wheelSelectedIndex ? 'active' : ''}`;
            indicator.onclick = () => animateToIndex(index);
            indicator.title = `${item.id}단계: ${item.label}`;
            container.appendChild(indicator);
        });
    }

    // 휠 위치 업데이트
    function updateWheelPositions() {
        const items = document.querySelectorAll('.wheel-item');

        items.forEach((item, index) => {
            const diff = index - wheelSelectedIndex;
            let adjustedDiff = diff;

            // 순환 처리
            if (Math.abs(diff) > agentMenuItems.length / 2) {
                adjustedDiff = diff > 0 ? diff - agentMenuItems.length : diff + agentMenuItems.length;
            }

            const yOffset = adjustedDiff * 42;
            const scale = Math.max(0.8, 1 - Math.abs(adjustedDiff) * 0.05);

            let opacity = 1;
            if (Math.abs(adjustedDiff) === 0) {
                opacity = 1;
            } else if (Math.abs(adjustedDiff) <= 2) {
                opacity = 0.85;
            } else if (Math.abs(adjustedDiff) <= 4) {
                opacity = 0.6;
            } else if (Math.abs(adjustedDiff) <= 6) {
                opacity = 0.35;
            } else {
                opacity = 0.2;
            }

            item.style.transform = `translateY(${yOffset}px) translateZ(${-Math.abs(adjustedDiff) * 20}px) scale(${scale})`;
            item.style.opacity = opacity;
            item.style.zIndex = 25 - Math.abs(adjustedDiff);
            item.style.display = Math.abs(adjustedDiff) > 9 ? 'none' : 'flex';

            // 중앙 아이템 강조
            if (index === wheelSelectedIndex) {
                item.classList.add('center');
                // 아이콘 색상 업데이트
                const icon = item.querySelector('.wheel-item-icon');
                if (icon) {
                    icon.style.backgroundColor = getAgentColor(agentMenuItems[index].id);
                }
            } else {
                item.classList.remove('center');
                const icon = item.querySelector('.wheel-item-icon');
                if (icon) {
                    icon.style.backgroundColor = '';
                }
            }
        });
    }

    // 에이전트별 색상
    function getAgentColor(id) {
        const colors = [
            '#3B82F6', '#10B981', '#8B5CF6', '#F97316', '#EC4899',
            '#F59E0B', '#6366F1', '#14B8A6', '#84CC16', '#06B6D4',
            '#A855F7', '#F472B6', '#FB923C', '#4ADE80', '#60A5FA',
            '#C084FC', '#FBBF24', '#34D399', '#F87171', '#818CF8',
            '#22D3EE'
        ];
        return colors[id - 1] || '#6B7280';
    }

    // 휠 상태 업데이트
    function updateWheelStatus() {
        const item = agentMenuItems[wheelSelectedIndex];
        const statusElement = document.getElementById('wheelStatus');
        if (statusElement) {
            statusElement.textContent = `${item.id}/21 - ${item.label}`;
        }

        // 인디케이터 업데이트
        const indicators = document.querySelectorAll('.wheel-indicator');
        indicators.forEach((indicator, index) => {
            if (index === wheelSelectedIndex) {
                indicator.classList.add('active');
            } else {
                indicator.classList.remove('active');
            }
        });
    }

    // 휠 이동
    function moveWheel(direction) {
        if (wheelIsAnimating) return;

        wheelIsAnimating = true;
        wheelSelectedIndex = (wheelSelectedIndex + direction + agentMenuItems.length) % agentMenuItems.length;

        updateWheelPositions();
        updateWheelStatus();

        setTimeout(() => {
            wheelIsAnimating = false;
        }, 200);
    }

    // 특정 인덱스로 애니메이션
    function animateToIndex(targetIndex) {
        if (wheelIsAnimating) return;

        const diff = targetIndex - wheelSelectedIndex;
        let adjustedDiff = diff;

        if (Math.abs(diff) > agentMenuItems.length / 2) {
            adjustedDiff = diff > 0 ? diff - agentMenuItems.length : diff + agentMenuItems.length;
        }

        const steps = Math.abs(adjustedDiff);
        const direction = adjustedDiff > 0 ? 1 : -1;

        let speed = steps === 1 ? 200 : steps <= 3 ? 150 : steps <= 7 ? 100 : 70;

        for (let i = 0; i < steps; i++) {
            const easedDelay = i === 0 ? 0 : i === steps - 1 ? (i * speed) + (speed * 0.5) : i * speed;
            setTimeout(() => moveWheel(direction), easedDelay);
        }
    }

    // 마우스 휠 스크롤 처리
    function handleWheelScroll(e) {
        e.preventDefault();
        if (wheelIsAnimating) return;
        const direction = e.deltaY > 0 ? 1 : -1;
        moveWheel(direction);
    }

    // DOM이 준비되면 초기화
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAgentWheel);
    } else {
        initAgentWheel();
    }
</script>