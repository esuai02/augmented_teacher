/**
 * File: alt42/orchestration/agents/agent04_problem_activity/ui/activity_panel.js
 * Agent04: 활동 선택 UI 패널 컴포넌트
 */

window.Agent04ActivityPanel = {
    currentModal: null,
    selectedCategory: null,
    selectedSubItem: null,

    /**
     * 메인 카테고리 선택 처리
     */
    async selectCategory(categoryKey) {
        console.log('🎯 활동 카테고리 선택:', categoryKey);

        const category = window.Agent04ActivityCategories.getCategory(categoryKey);
        if (!category) {
            console.error('❌ 카테고리를 찾을 수 없습니다:', categoryKey);
            alert('카테고리를 찾을 수 없습니다.');
            return;
        }

        this.selectedCategory = categoryKey;
        this.showSubItemsModal(category, categoryKey);
    },

    /**
     * 하위 항목 선택 모달 표시
     */
    showSubItemsModal(category, categoryKey) {
        console.log('📋 하위 항목 모달 표시:', category);

        // 기존 모달 제거
        this.closeModal();

        // 모달 HTML 생성
        const modalHtml = `
            <div class="agent04-modal-overlay" id="agent04-activity-modal">
                <div class="agent04-modal-content">
                    <div class="agent04-modal-header">
                        <h3>${category.icon} ${category.name} - 세부 활동 선택</h3>
                        <button class="agent04-close-btn" onclick="Agent04ActivityPanel.closeModal()">
                            ✕
                        </button>
                    </div>
                    <div class="agent04-modal-body">
                        <div class="agent04-sub-items-grid">
                            ${this.renderSubItems(category.subItems, categoryKey)}
                        </div>
                    </div>
                    <div class="agent04-modal-footer">
                        <button class="agent04-btn-cancel" onclick="Agent04ActivityPanel.closeModal()">
                            취소
                        </button>
                    </div>
                </div>
            </div>
        `;

        // DOM에 추가
        const modalDiv = document.createElement('div');
        modalDiv.innerHTML = modalHtml;
        document.body.appendChild(modalDiv.firstElementChild);

        this.currentModal = document.getElementById('agent04-activity-modal');
    },

    /**
     * 하위 항목 버튼들 렌더링
     */
    renderSubItems(subItems, categoryKey) {
        return subItems.map((item, index) => `
            <button class="agent04-sub-item-btn"
                    data-category="${categoryKey}"
                    data-item="${item}"
                    onclick="Agent04ActivityPanel.selectSubItem('${categoryKey}', '${item}')">
                <span class="item-number">${index + 1}</span>
                <span class="item-text">${item}</span>
            </button>
        `).join('');
    },

    /**
     * 하위 항목 선택 처리
     */
    async selectSubItem(categoryKey, subItem) {
        console.log('✅ 하위 활동 선택:', categoryKey, subItem);

        this.selectedSubItem = subItem;

        try {
            // 1. DB에 저장
            const saveResult = await window.Agent04ActivityCategories.saveSelection(
                categoryKey,
                subItem,
                window.currentUserId
            );

            // 2. 성공 메시지 표시
            this.showSuccessMessage(categoryKey, subItem);

            // 3. 잠시 후 모달 닫기
            setTimeout(() => {
                this.closeModal();
            }, 2000);

        } catch (error) {
            console.error('❌ 하위 활동 저장 실패:', error);
            alert('활동 저장에 실패했습니다. 다시 시도해주세요.');
        }
    },

    /**
     * 성공 메시지 표시
     */
    showSuccessMessage(categoryKey, subItem) {
        const category = window.Agent04ActivityCategories.getCategory(categoryKey);

        const messageHtml = `
            <div class="agent04-success-message">
                <div class="success-icon">✓</div>
                <div class="success-text">
                    <p><strong>${category.icon} ${category.name}</strong></p>
                    <p>${subItem}</p>
                    <p class="future-notice">
                        추후 학생의 행동유형과 관련된 설문이 추가될 예정입니다.
                    </p>
                </div>
            </div>
        `;

        const modalBody = this.currentModal?.querySelector('.agent04-modal-body');
        if (modalBody) {
            modalBody.innerHTML = messageHtml;
        }
    },

    /**
     * 모달 닫기
     */
    closeModal() {
        if (this.currentModal) {
            this.currentModal.remove();
            this.currentModal = null;
        }

        // 다른 방식으로 생성된 모달도 제거
        const existingModal = document.getElementById('agent04-activity-modal');
        if (existingModal) {
            existingModal.remove();
        }
    },

    /**
     * 우측 패널 결과 영역 업데이트
     */
    updateResultDisplay(categoryKey, subItem) {
        const category = window.Agent04ActivityCategories.getCategory(categoryKey);
        if (!category) return;

        const resultArea = document.querySelector('.agent04-result-area') ||
                          document.querySelector('#step-4 .step-result-display');

        if (resultArea) {
            resultArea.innerHTML = `
                <div class="agent04-selection-result">
                    <h4>선택된 활동</h4>
                    <div class="result-card">
                        <div class="result-main">
                            ${category.icon} <strong>${category.name}</strong>
                        </div>
                        <div class="result-sub">
                            ${subItem}
                        </div>
                        <div class="result-timestamp">
                            ${new Date().toLocaleString('ko-KR')}
                        </div>
                    </div>
                </div>
            `;
        }
    }
};

// ESC 키로 모달 닫기
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && Agent04ActivityPanel.currentModal) {
        Agent04ActivityPanel.closeModal();
    }
});

console.log('✅ Agent04 Activity Panel UI 로드 완료');
