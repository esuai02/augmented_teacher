/**
 * Agent05 학습감정 분석 - 워크플로우 로직
 * File: alt42/orchestration/agents/agent05_learning_emotion/assets/js/emotion_workflow.js
 *
 * Error Location: emotion_workflow.js
 */

(function() {
    'use strict';

    // 전역 상태
    window.Agent05State = {
        selectedActivity: null,
        selectedSubItem: null,
        emotionSelectionId: null
    };

    /**
     * 페이지 초기화
     */
    function initAgent05() {
        console.log('[emotion_workflow.js] Agent05 초기화 시작');
        renderActivityCards();
    }

    /**
     * 활동 카드 렌더링
     */
    function renderActivityCards() {
        console.log('[emotion_workflow.js:renderActivityCards] 활동 카드 렌더링 시작');

        const grid = document.getElementById('activity-cards-grid');
        if (!grid) {
            console.error('[emotion_workflow.js:renderActivityCards:31] activity-cards-grid 요소를 찾을 수 없습니다');
            return;
        }

        const categories = window.Agent05ActivityCategories.getAllCategories();
        console.log('[emotion_workflow.js:renderActivityCards:37] 카테고리 수:', categories.length);

        grid.innerHTML = '';

        categories.forEach((category, index) => {
            const card = document.createElement('div');
            card.className = 'activity-card';
            card.setAttribute('data-key', category.key);
            card.innerHTML = `
                <span class="icon">${category.icon}</span>
                <h3>${category.name}</h3>
                <div class="description">
                    ${category.subItems.length}개 세부 활동 유형
                </div>
            `;

            card.addEventListener('click', function() {
                handleActivityCardClick(category.key, category.name, category);
            });

            grid.appendChild(card);
        });

        console.log('[emotion_workflow.js:renderActivityCards:60] 활동 카드 렌더링 완료');
    }

    /**
     * 활동 카드 클릭 핸들러
     */
    function handleActivityCardClick(categoryKey, categoryName, category) {
        console.log('[emotion_workflow.js:handleActivityCardClick:67] 활동 선택:', categoryKey, categoryName);

        window.Agent05State.selectedActivity = {
            key: categoryKey,
            name: categoryName,
            category: category
        };

        showSubItemsModal(category);
    }

    /**
     * 하위 항목 모달 표시
     */
    function showSubItemsModal(category) {
        console.log('[emotion_workflow.js:showSubItemsModal:82] 모달 표시:', category.name);

        const modalOverlay = document.getElementById('modal-overlay');
        if (!modalOverlay) {
            console.error('[emotion_workflow.js:showSubItemsModal:86] modal-overlay 요소를 찾을 수 없습니다');
            return;
        }

        let modalHtml = `
            <div class="modal-content">
                <h3>${category.icon} ${category.name} - 세부 활동 선택</h3>
                <div class="sub-items-container">
        `;

        if (category.subItems && category.subItems.length > 0) {
            category.subItems.forEach((item, index) => {
                modalHtml += `
                    <button class="sub-item-btn"
                            data-index="${index}"
                            data-item="${item}">
                        ${index + 1}. ${item}
                    </button>
                `;
            });
        } else {
            modalHtml += '<p style="color: #666;">표시할 항목이 없습니다.</p>';
        }

        modalHtml += `
                </div>
                <button class="btn-close">닫기</button>
            </div>
        `;

        modalOverlay.innerHTML = modalHtml;
        modalOverlay.classList.remove('hidden');

        // 이벤트 리스너 등록
        const subItemBtns = modalOverlay.querySelectorAll('.sub-item-btn');
        subItemBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const itemName = this.getAttribute('data-item');
                const itemIndex = this.getAttribute('data-index');
                handleSubItemClick(category, itemName, itemIndex);
            });
        });

        const closeBtn = modalOverlay.querySelector('.btn-close');
        closeBtn.addEventListener('click', closeModal);

        console.log('[emotion_workflow.js:showSubItemsModal:135] 모달 표시 완료');
    }

    /**
     * 하위 항목 클릭 핸들러
     */
    function handleSubItemClick(category, itemName, itemIndex) {
        console.log('[emotion_workflow.js:handleSubItemClick:142] 하위 항목 선택:', category.name, itemName);

        window.Agent05State.selectedSubItem = {
            categoryKey: category.key,
            categoryName: category.name,
            itemName: itemName,
            itemIndex: itemIndex
        };

        closeModal();
        showTemporaryMessage();
    }

    /**
     * 모달 닫기
     */
    function closeModal() {
        console.log('[emotion_workflow.js:closeModal:159] 모달 닫기');

        const modalOverlay = document.getElementById('modal-overlay');
        if (modalOverlay) {
            modalOverlay.classList.add('hidden');
            modalOverlay.innerHTML = '';
        }
    }

    /**
     * 임시 메시지 팝업 표시
     */
    function showTemporaryMessage() {
        console.log('[emotion_workflow.js:showTemporaryMessage:173] 임시 메시지 팝업 표시');

        const state = window.Agent05State;

        if (!state.selectedActivity || !state.selectedSubItem) {
            console.error('[emotion_workflow.js:showTemporaryMessage:178] 선택된 활동 또는 하위 항목이 없습니다');
            return;
        }

        const modalOverlay = document.getElementById('modal-overlay');
        if (!modalOverlay) {
            console.error('[emotion_workflow.js:showTemporaryMessage:184] modal-overlay 요소를 찾을 수 없습니다');
            return;
        }

        const messageHtml = `
            <div class="modal-content" style="text-align: center;">
                <h3 style="color: #667eea; margin-bottom: 20px;">
                    📋 선택 완료
                </h3>
                <div style="margin-bottom: 20px;">
                    <p style="font-size: 1.1rem; margin-bottom: 10px;">
                        <strong>활동:</strong> ${state.selectedActivity.name}
                    </p>
                    <p style="font-size: 1.1rem; margin-bottom: 20px;">
                        <strong>세부 항목:</strong> ${state.selectedSubItem.itemName}
                    </p>
                    <div style="background: #f0f4ff; padding: 20px; border-radius: 10px; margin: 20px 0;">
                        <p style="color: #667eea; font-size: 1.05rem; line-height: 1.6;">
                            추후 학생의 감정유형과 관련된 설문이 추가될 예정입니다.
                        </p>
                    </div>
                </div>
                <button class="btn-close" style="background: #667eea; font-size: 1rem; padding: 12px 30px;">
                    확인
                </button>
            </div>
        `;

        modalOverlay.innerHTML = messageHtml;
        modalOverlay.classList.remove('hidden');

        const closeBtn = modalOverlay.querySelector('.btn-close');
        closeBtn.addEventListener('click', function() {
            closeModal();
            // 상태 초기화
            window.Agent05State.selectedActivity = null;
            window.Agent05State.selectedSubItem = null;
        });

        console.log('[emotion_workflow.js:showTemporaryMessage:226] 임시 메시지 팝업 표시 완료');
    }

    // 전역 함수로 노출
    window.initAgent05 = initAgent05;
    window.renderActivityCards = renderActivityCards;
    window.handleActivityCardClick = handleActivityCardClick;
    window.showSubItemsModal = showSubItemsModal;
    window.handleSubItemClick = handleSubItemClick;
    window.closeModal = closeModal;
    window.showTemporaryMessage = showTemporaryMessage;

    // DOM 로드 완료 시 초기화
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAgent05);
    } else {
        initAgent05();
    }
})();
