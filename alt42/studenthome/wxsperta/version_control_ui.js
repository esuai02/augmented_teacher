/**
 * WXsperta 버전 관리 UI 컴포넌트
 * 버전 타임라인, Diff 뷰어, 롤백 기능 제공
 */

class VersionControlUI {
    constructor(config) {
        this.apiUrl = config.apiUrl || 'version_api.php';
        this.container = config.container || document.body;
        this.userRole = config.userRole || 'student';
        this.userId = config.userId;
        
        this.currentVersion = null;
        this.versions = [];
        this.selectedVersions = [];
        
        this.init();
    }
    
    init() {
        this.createUI();
        this.loadVersions();
        this.attachEventListeners();
    }
    
    createUI() {
        // 버전 관리 아이콘 추가
        const versionIcon = document.createElement('div');
        versionIcon.className = 'version-control-icon';
        versionIcon.innerHTML = `
            <button class="version-btn" title="버전 관리">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M12 8V12L15 15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
                </svg>
                <span class="version-badge" style="display: none;">0</span>
            </button>
        `;
        
        // 사이드 패널
        const sidePanel = document.createElement('div');
        sidePanel.className = 'version-control-panel';
        sidePanel.innerHTML = `
            <div class="panel-header">
                <h3>버전 관리</h3>
                <button class="close-btn">&times;</button>
            </div>
            
            <div class="panel-tabs">
                <button class="tab-btn active" data-tab="timeline">타임라인</button>
                <button class="tab-btn" data-tab="diff">변경사항</button>
                ${this.userRole === 'teacher' ? '<button class="tab-btn" data-tab="rollback">롤백</button>' : ''}
            </div>
            
            <div class="panel-content">
                <div class="tab-panel active" id="timeline-panel">
                    <div class="version-actions">
                        <button class="btn-primary" id="commit-btn">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                <path d="M8 0L6.545 1.455l5.506 5.506H0v2.078h12.052l-5.507 5.506L8 16l8-8z"/>
                            </svg>
                            현재 상태 커밋
                        </button>
                    </div>
                    <div class="version-list" id="version-list">
                        <!-- 버전 목록이 여기에 표시됩니다 -->
                    </div>
                    <div class="load-more">
                        <button id="load-more-btn">더 보기</button>
                    </div>
                </div>
                
                <div class="tab-panel" id="diff-panel">
                    <div class="diff-controls">
                        <select id="diff-from" class="version-select">
                            <option value="">이전 버전 선택</option>
                        </select>
                        <span class="diff-arrow">→</span>
                        <select id="diff-to" class="version-select">
                            <option value="">이후 버전 선택</option>
                        </select>
                        <button class="btn-secondary" id="compare-btn">비교</button>
                    </div>
                    <div class="diff-viewer" id="diff-viewer">
                        <!-- Diff 결과가 여기에 표시됩니다 -->
                    </div>
                </div>
                
                ${this.userRole === 'teacher' ? `
                <div class="tab-panel" id="rollback-panel">
                    <div class="rollback-warning">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="#f59e0b">
                            <path d="M10 2L2 18h16L10 2zm0 4l1.5 6h-3L10 6zm0 8a1 1 0 100 2 1 1 0 000-2z"/>
                        </svg>
                        <p>롤백은 모든 사용자의 현재 작업에 영향을 미칩니다. 신중하게 진행하세요.</p>
                    </div>
                    <div class="rollback-form">
                        <label>대상 버전:</label>
                        <select id="rollback-target" class="version-select">
                            <option value="">롤백할 버전 선택</option>
                        </select>
                        <label>롤백 사유:</label>
                        <textarea id="rollback-reason" placeholder="롤백 사유를 입력하세요..."></textarea>
                        <button class="btn-danger" id="rollback-btn">롤백 실행</button>
                    </div>
                </div>
                ` : ''}
            </div>
        `;
        
        // CSS 스타일 추가
        this.addStyles();
        
        // DOM에 추가
        document.body.appendChild(versionIcon);
        document.body.appendChild(sidePanel);
        
        this.elements = {
            icon: versionIcon,
            panel: sidePanel,
            versionList: sidePanel.querySelector('#version-list'),
            diffViewer: sidePanel.querySelector('#diff-viewer'),
            commitBtn: sidePanel.querySelector('#commit-btn'),
            compareBtn: sidePanel.querySelector('#compare-btn'),
            loadMoreBtn: sidePanel.querySelector('#load-more-btn'),
            rollbackBtn: sidePanel.querySelector('#rollback-btn'),
            versionBadge: versionIcon.querySelector('.version-badge')
        };
    }
    
    addStyles() {
        const style = document.createElement('style');
        style.textContent = `
            /* 버전 관리 아이콘 */
            .version-control-icon {
                position: fixed;
                left: 20px;
                bottom: 20px;
                z-index: 1000;
            }
            
            .version-btn {
                width: 56px;
                height: 56px;
                border-radius: 50%;
                background: #3b82f6;
                color: white;
                border: none;
                cursor: pointer;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                transition: all 0.3s ease;
                position: relative;
            }
            
            .version-btn:hover {
                transform: scale(1.1);
                box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
            }
            
            .version-badge {
                position: absolute;
                top: -4px;
                right: -4px;
                background: #ef4444;
                color: white;
                font-size: 12px;
                font-weight: bold;
                padding: 2px 6px;
                border-radius: 10px;
                min-width: 20px;
                text-align: center;
            }
            
            /* 사이드 패널 */
            .version-control-panel {
                position: fixed;
                right: 0;
                top: 0;
                width: 25vw; /* 채팅 패널과 동일한 폭 */
                height: 100vh;
                background: white;
                box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
                transform: translateX(100%);
                transition: transform 0.3s ease;
                z-index: 50;
                display: flex;
                flex-direction: column;
            }
            
            @media (max-width: 1024px) {
                .version-control-panel {
                    width: 40vw;
                }
            }
            
            @media (max-width: 768px) {
                .version-control-panel {
                    width: 100vw;
                }
            }
            
            .version-control-panel.open {
                transform: translateX(0);
            }
            
            .panel-header {
                padding: 20px;
                border-bottom: 1px solid #e5e7eb;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .panel-header h3 {
                margin: 0;
                font-size: 20px;
                font-weight: 600;
            }
            
            .close-btn {
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
                color: #6b7280;
                padding: 0;
                width: 32px;
                height: 32px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 4px;
                transition: background 0.2s;
            }
            
            .close-btn:hover {
                background: #f3f4f6;
            }
            
            /* 탭 */
            .panel-tabs {
                display: flex;
                border-bottom: 1px solid #e5e7eb;
                padding: 0 20px;
            }
            
            .tab-btn {
                background: none;
                border: none;
                padding: 12px 16px;
                cursor: pointer;
                font-size: 14px;
                font-weight: 500;
                color: #6b7280;
                border-bottom: 2px solid transparent;
                transition: all 0.2s;
            }
            
            .tab-btn:hover {
                color: #374151;
            }
            
            .tab-btn.active {
                color: #3b82f6;
                border-bottom-color: #3b82f6;
            }
            
            /* 패널 내용 */
            .panel-content {
                flex: 1;
                overflow-y: auto;
                padding: 20px;
            }
            
            .tab-panel {
                display: none;
            }
            
            .tab-panel.active {
                display: block;
            }
            
            /* 버전 액션 */
            .version-actions {
                margin-bottom: 20px;
            }
            
            .btn-primary {
                background: #3b82f6;
                color: white;
                border: none;
                padding: 10px 16px;
                border-radius: 6px;
                font-size: 14px;
                font-weight: 500;
                cursor: pointer;
                display: flex;
                align-items: center;
                gap: 8px;
                transition: background 0.2s;
            }
            
            .btn-primary:hover {
                background: #2563eb;
            }
            
            .btn-secondary {
                background: #6b7280;
                color: white;
                border: none;
                padding: 8px 16px;
                border-radius: 6px;
                font-size: 14px;
                cursor: pointer;
                transition: background 0.2s;
            }
            
            .btn-secondary:hover {
                background: #4b5563;
            }
            
            .btn-danger {
                background: #ef4444;
                color: white;
                border: none;
                padding: 10px 16px;
                border-radius: 6px;
                font-size: 14px;
                font-weight: 500;
                cursor: pointer;
                transition: background 0.2s;
                width: 100%;
                margin-top: 16px;
            }
            
            .btn-danger:hover {
                background: #dc2626;
            }
            
            /* 버전 목록 */
            .version-list {
                space-y: 12px;
            }
            
            .version-item {
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                padding: 16px;
                margin-bottom: 12px;
                cursor: pointer;
                transition: all 0.2s;
            }
            
            .version-item:hover {
                border-color: #3b82f6;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            }
            
            .version-item.selected {
                border-color: #3b82f6;
                background: #eff6ff;
            }
            
            .version-header {
                display: flex;
                justify-content: space-between;
                align-items: start;
                margin-bottom: 8px;
            }
            
            .version-id {
                font-family: monospace;
                font-size: 12px;
                color: #6b7280;
                background: #f3f4f6;
                padding: 2px 6px;
                border-radius: 4px;
            }
            
            .version-time {
                font-size: 12px;
                color: #6b7280;
            }
            
            .version-author {
                font-size: 14px;
                font-weight: 500;
                color: #374151;
                margin-bottom: 4px;
            }
            
            .version-message {
                font-size: 14px;
                color: #4b5563;
                margin-bottom: 8px;
            }
            
            .version-tags {
                display: flex;
                gap: 6px;
                flex-wrap: wrap;
            }
            
            .version-tag {
                font-size: 11px;
                padding: 2px 8px;
                border-radius: 12px;
                background: #e5e7eb;
                color: #374151;
            }
            
            .version-tag.milestone {
                background: #fbbf24;
                color: #78350f;
            }
            
            /* Diff 뷰어 */
            .diff-controls {
                display: flex;
                align-items: center;
                gap: 12px;
                margin-bottom: 20px;
            }
            
            .version-select {
                flex: 1;
                padding: 8px 12px;
                border: 1px solid #e5e7eb;
                border-radius: 6px;
                font-size: 14px;
            }
            
            .diff-arrow {
                color: #6b7280;
                font-size: 18px;
            }
            
            .diff-viewer {
                background: #f9fafb;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                padding: 16px;
                min-height: 200px;
                font-family: monospace;
                font-size: 13px;
                overflow-x: auto;
            }
            
            .diff-added {
                background: #d1fae5;
                color: #065f46;
                padding: 2px 4px;
                border-radius: 3px;
            }
            
            .diff-modified {
                background: #fef3c7;
                color: #78350f;
                padding: 2px 4px;
                border-radius: 3px;
            }
            
            .diff-deleted {
                background: #fee2e2;
                color: #991b1b;
                padding: 2px 4px;
                border-radius: 3px;
                text-decoration: line-through;
            }
            
            /* 롤백 */
            .rollback-warning {
                background: #fffbeb;
                border: 1px solid #fbbf24;
                border-radius: 8px;
                padding: 16px;
                display: flex;
                gap: 12px;
                margin-bottom: 20px;
            }
            
            .rollback-warning p {
                margin: 0;
                font-size: 14px;
                color: #78350f;
            }
            
            .rollback-form label {
                display: block;
                font-size: 14px;
                font-weight: 500;
                margin-bottom: 6px;
                margin-top: 16px;
                color: #374151;
            }
            
            .rollback-form textarea {
                width: 100%;
                padding: 8px 12px;
                border: 1px solid #e5e7eb;
                border-radius: 6px;
                font-size: 14px;
                resize: vertical;
                min-height: 80px;
            }
            
            /* 로딩 상태 */
            .loading {
                text-align: center;
                padding: 40px;
                color: #6b7280;
            }
            
            .loading::after {
                content: '';
                display: inline-block;
                width: 20px;
                height: 20px;
                margin-left: 8px;
                border: 2px solid #e5e7eb;
                border-top-color: #3b82f6;
                border-radius: 50%;
                animation: spin 0.8s linear infinite;
            }
            
            @keyframes spin {
                to { transform: rotate(360deg); }
            }
            
            /* 모달 */
            .modal-overlay {
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
                opacity: 0;
                visibility: hidden;
                transition: all 0.3s ease;
            }
            
            .modal-overlay.show {
                opacity: 1;
                visibility: visible;
            }
            
            .modal-content {
                background: white;
                border-radius: 12px;
                padding: 24px;
                max-width: 400px;
                width: 90%;
                transform: scale(0.9);
                transition: transform 0.3s ease;
            }
            
            .modal-overlay.show .modal-content {
                transform: scale(1);
            }
            
            .modal-title {
                font-size: 18px;
                font-weight: 600;
                margin-bottom: 16px;
            }
            
            .modal-body {
                margin-bottom: 24px;
            }
            
            .modal-footer {
                display: flex;
                gap: 12px;
                justify-content: flex-end;
            }
            
            /* 토스트 알림 */
            .toast {
                position: fixed;
                bottom: 20px;
                left: 50%;
                transform: translateX(-50%) translateY(100px);
                background: #374151;
                color: white;
                padding: 12px 24px;
                border-radius: 6px;
                font-size: 14px;
                opacity: 0;
                transition: all 0.3s ease;
                z-index: 9999;
            }
            
            .toast.show {
                transform: translateX(-50%) translateY(0);
                opacity: 1;
            }
            
            .toast.success {
                background: #10b981;
            }
            
            .toast.error {
                background: #ef4444;
            }
        `;
        document.head.appendChild(style);
    }
    
    attachEventListeners() {
        // 아이콘 클릭 - 패널 열기
        this.elements.icon.querySelector('.version-btn').addEventListener('click', () => {
            this.togglePanel();
        });
        
        // 패널 닫기
        this.elements.panel.querySelector('.close-btn').addEventListener('click', () => {
            this.closePanel();
        });
        
        // 탭 전환
        this.elements.panel.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.switchTab(e.target.dataset.tab);
            });
        });
        
        // 커밋 버튼
        this.elements.commitBtn.addEventListener('click', () => {
            this.showCommitModal();
        });
        
        // 비교 버튼
        this.elements.compareBtn.addEventListener('click', () => {
            this.compareDiff();
        });
        
        // 더 보기 버튼
        this.elements.loadMoreBtn.addEventListener('click', () => {
            this.loadMoreVersions();
        });
        
        // 롤백 버튼
        if (this.elements.rollbackBtn) {
            this.elements.rollbackBtn.addEventListener('click', () => {
                this.performRollback();
            });
        }
        
        // ESC 키로 패널 닫기
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.elements.panel.classList.contains('open')) {
                this.closePanel();
            }
        });
    }
    
    // API 메서드들
    async loadVersions(offset = 0) {
        try {
            const response = await fetch(`${this.apiUrl}?action=versions&limit=20&offset=${offset}`);
            const data = await response.json();
            
            if (data.success) {
                if (offset === 0) {
                    this.versions = data.versions;
                    this.renderVersionList();
                } else {
                    this.versions.push(...data.versions);
                    this.appendVersionsToList(data.versions);
                }
                
                // 버전 수 배지 업데이트
                this.updateVersionBadge(data.total);
                
                // 더 보기 버튼 표시/숨김
                if (this.versions.length >= data.total) {
                    this.elements.loadMoreBtn.style.display = 'none';
                }
            }
        } catch (error) {
            console.error('Failed to load versions:', error);
            this.showToast('버전 목록을 불러오는데 실패했습니다.', 'error');
        }
    }
    
    async createCommit(message, isMilestone = false) {
        try {
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'commit',
                    commit_msg: message,
                    is_milestone: isMilestone
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showToast('버전이 성공적으로 커밋되었습니다.', 'success');
                this.loadVersions(); // 목록 새로고침
                return data.version_id;
            } else {
                throw new Error(data.error);
            }
        } catch (error) {
            console.error('Commit failed:', error);
            this.showToast('커밋에 실패했습니다: ' + error.message, 'error');
        }
    }
    
    async getDiff(fromVersion, toVersion) {
        try {
            const response = await fetch(
                `${this.apiUrl}?action=diff&from=${fromVersion}&to=${toVersion}`
            );
            const data = await response.json();
            
            if (data.success) {
                return data;
            } else {
                throw new Error(data.error);
            }
        } catch (error) {
            console.error('Diff failed:', error);
            this.showToast('비교에 실패했습니다.', 'error');
        }
    }
    
    async rollback(targetVersionId, reason) {
        try {
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'rollback',
                    version_id: targetVersionId,
                    reason: reason
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showToast('성공적으로 롤백되었습니다.', 'success');
                // 3초 후 페이지 새로고침
                setTimeout(() => {
                    window.location.reload();
                }, 3000);
            } else {
                throw new Error(data.error);
            }
        } catch (error) {
            console.error('Rollback failed:', error);
            this.showToast('롤백에 실패했습니다: ' + error.message, 'error');
        }
    }
    
    // UI 메서드들
    togglePanel() {
        this.elements.panel.classList.toggle('open');
        const mainContainer = document.getElementById('mainContainer');
        
        if (this.elements.panel.classList.contains('open')) {
            // 채팅 패널이 열려있으면 닫기
            if (typeof window.handleChatClose === 'function') {
                window.handleChatClose();
            }
            
            // 메인 컨테이너 이동
            if (mainContainer) {
                mainContainer.classList.add('shifted');
            }
            
            this.loadVersions();
        } else {
            // 패널 닫을 때 메인 컨테이너 원위치
            if (mainContainer) {
                mainContainer.classList.remove('shifted');
            }
        }
    }
    
    closePanel() {
        this.elements.panel.classList.remove('open');
        const mainContainer = document.getElementById('mainContainer');
        if (mainContainer) {
            mainContainer.classList.remove('shifted');
        }
    }
    
    switchTab(tabName) {
        // 탭 버튼 활성화
        this.elements.panel.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.tab === tabName);
        });
        
        // 탭 패널 표시
        this.elements.panel.querySelectorAll('.tab-panel').forEach(panel => {
            panel.classList.toggle('active', panel.id === `${tabName}-panel`);
        });
        
        // 탭별 초기화
        if (tabName === 'diff') {
            this.initDiffTab();
        } else if (tabName === 'rollback') {
            this.initRollbackTab();
        }
    }
    
    renderVersionList() {
        this.elements.versionList.innerHTML = '';
        this.versions.forEach(version => {
            this.elements.versionList.appendChild(this.createVersionItem(version));
        });
    }
    
    appendVersionsToList(versions) {
        versions.forEach(version => {
            this.elements.versionList.appendChild(this.createVersionItem(version));
        });
    }
    
    createVersionItem(version) {
        const item = document.createElement('div');
        item.className = 'version-item';
        item.dataset.versionId = version.version_id;
        
        const createdAt = new Date(version.created_at);
        const timeStr = createdAt.toLocaleString('ko-KR');
        
        item.innerHTML = `
            <div class="version-header">
                <div>
                    <div class="version-author">${version.author_name || 'Unknown'}</div>
                    <div class="version-time">${timeStr}</div>
                </div>
                <div class="version-id">${version.version_id.substring(0, 8)}</div>
            </div>
            <div class="version-message">${version.commit_msg}</div>
            <div class="version-tags">
                ${version.is_milestone ? '<span class="version-tag milestone">마일스톤</span>' : ''}
                ${version.tags ? version.tags.map(tag => 
                    `<span class="version-tag">${tag.tag_name}</span>`
                ).join('') : ''}
            </div>
        `;
        
        item.addEventListener('click', () => {
            this.selectVersion(version);
        });
        
        return item;
    }
    
    selectVersion(version) {
        // 선택 상태 토글
        const item = this.elements.versionList.querySelector(
            `[data-version-id="${version.version_id}"]`
        );
        
        if (item.classList.contains('selected')) {
            item.classList.remove('selected');
            this.selectedVersions = this.selectedVersions.filter(
                v => v.version_id !== version.version_id
            );
        } else {
            if (this.selectedVersions.length >= 2) {
                // 최대 2개까지만 선택 가능
                const firstSelected = this.elements.versionList.querySelector('.selected');
                if (firstSelected) {
                    firstSelected.classList.remove('selected');
                    this.selectedVersions.shift();
                }
            }
            item.classList.add('selected');
            this.selectedVersions.push(version);
        }
    }
    
    initDiffTab() {
        // Diff 탭 초기화
        const fromSelect = this.elements.panel.querySelector('#diff-from');
        const toSelect = this.elements.panel.querySelector('#diff-to');
        
        // 버전 목록으로 셀렉트 박스 채우기
        fromSelect.innerHTML = '<option value="">이전 버전 선택</option>';
        toSelect.innerHTML = '<option value="">이후 버전 선택</option>';
        
        this.versions.forEach(version => {
            const optionText = `${version.version_id.substring(0, 8)} - ${version.commit_msg.substring(0, 30)}`;
            fromSelect.innerHTML += `<option value="${version.version_id}">${optionText}</option>`;
            toSelect.innerHTML += `<option value="${version.version_id}">${optionText}</option>`;
        });
        
        // 선택된 버전이 있으면 자동 선택
        if (this.selectedVersions.length === 2) {
            fromSelect.value = this.selectedVersions[0].version_id;
            toSelect.value = this.selectedVersions[1].version_id;
        }
    }
    
    initRollbackTab() {
        // 롤백 탭 초기화
        const targetSelect = this.elements.panel.querySelector('#rollback-target');
        
        targetSelect.innerHTML = '<option value="">롤백할 버전 선택</option>';
        
        this.versions.forEach(version => {
            const optionText = `${version.version_id.substring(0, 8)} - ${version.commit_msg.substring(0, 30)}`;
            targetSelect.innerHTML += `<option value="${version.version_id}">${optionText}</option>`;
        });
    }
    
    async compareDiff() {
        const fromVersion = this.elements.panel.querySelector('#diff-from').value;
        const toVersion = this.elements.panel.querySelector('#diff-to').value;
        
        if (!fromVersion || !toVersion) {
            this.showToast('비교할 두 버전을 선택해주세요.', 'error');
            return;
        }
        
        this.elements.diffViewer.innerHTML = '<div class="loading">비교 중...</div>';
        
        const diffData = await this.getDiff(fromVersion, toVersion);
        if (diffData) {
            this.renderDiff(diffData.diff);
        }
    }
    
    renderDiff(diff) {
        let html = '<div class="diff-content">';
        
        // 추가된 항목
        if (diff.added && Object.keys(diff.added).length > 0) {
            html += '<h4 style="color: #065f46;">추가됨:</h4>';
            for (const [key, value] of Object.entries(diff.added)) {
                html += `<div class="diff-added">${key}: ${JSON.stringify(value)}</div>`;
            }
        }
        
        // 수정된 항목
        if (diff.modified && Object.keys(diff.modified).length > 0) {
            html += '<h4 style="color: #78350f;">수정됨:</h4>';
            for (const [key, changes] of Object.entries(diff.modified)) {
                html += `<div class="diff-modified">
                    ${key}: ${JSON.stringify(changes.old)} → ${JSON.stringify(changes.new)}
                </div>`;
            }
        }
        
        // 삭제된 항목
        if (diff.deleted && Object.keys(diff.deleted).length > 0) {
            html += '<h4 style="color: #991b1b;">삭제됨:</h4>';
            for (const [key, value] of Object.entries(diff.deleted)) {
                html += `<div class="diff-deleted">${key}: ${JSON.stringify(value)}</div>`;
            }
        }
        
        html += '</div>';
        this.elements.diffViewer.innerHTML = html;
    }
    
    showCommitModal() {
        const modal = this.createModal({
            title: '새 버전 커밋',
            content: `
                <div class="modal-body">
                    <label style="display: block; margin-bottom: 8px; font-weight: 500;">
                        커밋 메시지:
                    </label>
                    <textarea id="commit-message" 
                        style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 6px; min-height: 80px;"
                        placeholder="변경 사항을 설명하세요..."></textarea>
                    <label style="display: block; margin-top: 16px;">
                        <input type="checkbox" id="is-milestone" style="margin-right: 8px;">
                        주요 마일스톤으로 표시
                    </label>
                </div>
            `,
            onConfirm: async () => {
                const message = document.getElementById('commit-message').value.trim();
                const isMilestone = document.getElementById('is-milestone').checked;
                
                if (!message) {
                    this.showToast('커밋 메시지를 입력해주세요.', 'error');
                    return false;
                }
                
                await this.createCommit(message, isMilestone);
                return true;
            }
        });
        
        // 메시지 입력란에 포커스
        setTimeout(() => {
            document.getElementById('commit-message').focus();
        }, 100);
    }
    
    async performRollback() {
        const targetVersion = this.elements.panel.querySelector('#rollback-target').value;
        const reason = this.elements.panel.querySelector('#rollback-reason').value.trim();
        
        if (!targetVersion) {
            this.showToast('롤백할 버전을 선택해주세요.', 'error');
            return;
        }
        
        if (!reason) {
            this.showToast('롤백 사유를 입력해주세요.', 'error');
            return;
        }
        
        // 확인 모달
        const modal = this.createModal({
            title: '롤백 확인',
            content: `
                <div class="modal-body">
                    <p style="color: #991b1b; font-weight: 500;">
                        정말로 선택한 버전으로 롤백하시겠습니까?
                    </p>
                    <p style="margin-top: 12px; font-size: 14px; color: #6b7280;">
                        이 작업은 모든 사용자의 현재 작업에 영향을 미칩니다.
                        롤백 전 현재 상태는 자동으로 백업됩니다.
                    </p>
                </div>
            `,
            confirmText: '롤백 실행',
            confirmClass: 'btn-danger',
            onConfirm: async () => {
                await this.rollback(targetVersion, reason);
                return true;
            }
        });
    }
    
    loadMoreVersions() {
        const offset = this.versions.length;
        this.loadVersions(offset);
    }
    
    updateVersionBadge(count) {
        const badge = this.elements.versionBadge;
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = 'block';
        } else {
            badge.style.display = 'none';
        }
    }
    
    // 유틸리티 메서드들
    createModal({title, content, onConfirm, confirmText = '확인', confirmClass = 'btn-primary'}) {
        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        
        overlay.innerHTML = `
            <div class="modal-content">
                <div class="modal-title">${title}</div>
                ${content}
                <div class="modal-footer">
                    <button class="btn-secondary" id="modal-cancel">취소</button>
                    <button class="${confirmClass}" id="modal-confirm">${confirmText}</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(overlay);
        
        // 애니메이션을 위한 지연
        setTimeout(() => overlay.classList.add('show'), 10);
        
        const cleanup = () => {
            overlay.classList.remove('show');
            setTimeout(() => overlay.remove(), 300);
        };
        
        overlay.querySelector('#modal-cancel').addEventListener('click', cleanup);
        overlay.querySelector('#modal-confirm').addEventListener('click', async () => {
            const shouldClose = await onConfirm();
            if (shouldClose !== false) {
                cleanup();
            }
        });
        
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                cleanup();
            }
        });
        
        return overlay;
    }
    
    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        // 애니메이션을 위한 지연
        setTimeout(() => toast.classList.add('show'), 10);
        
        // 3초 후 제거
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
}

// 전역 초기화 함수
window.initVersionControl = function(config) {
    return new VersionControlUI(config);
};