/**
 * Onboarding Right Panel Controller
 * File: /mnt/c/1 Project/augmented_teacher/alt42/orchestration/agents/agent01_onboarding/ui/panel.js
 * Location: Line 1
 */

console.log('📦 panel.js loading...');

(function() {
    'use strict';

    console.log('🔧 OnboardingPanel IIFE starting...');

    const OnboardingPanel = {
        panelElement: null,
        currentUserId: null,

        init: function() {
            console.log('🚀 OnboardingPanel.init() called');
            this.createPanelElement();
            this.attachEventListeners();
            console.log('✅ OnboardingPanel initialized successfully');
        },

        createPanelElement: function() {
            // Remove existing panel if any
            const existing = document.getElementById('onboardingRightPanel');
            if (existing) {
                existing.remove();
            }

            // Create panel structure
            const panel = document.createElement('div');
            panel.id = 'onboardingRightPanel';
            panel.className = 'onboarding-right-panel';
            panel.innerHTML = `
                <div class="panel-header">
                    <h2>온보딩 리포트</h2>
                    <button class="panel-close" onclick="OnboardingPanel.close()">&times;</button>
                </div>
                <div class="panel-content" id="onboardingPanelContent">
                    <div class="panel-loading">로딩 중</div>
                </div>
            `;

            document.body.appendChild(panel);
            this.panelElement = panel;
        },

        open: function(userid) {
            this.currentUserId = userid;
            this.panelElement.classList.add('active');
            this.loadPanelContent(userid);
        },

        close: function() {
            this.panelElement.classList.remove('active');
        },

        loadPanelContent: function(userid) {
            const contentDiv = document.getElementById('onboardingPanelContent');
            contentDiv.innerHTML = '<div class="panel-loading">데이터 확인 중</div>';

            // Check if report exists
            fetch('/moodle/local/augmented_teacher/alt42/orchestration/agents/agent01_onboarding/report_service.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=checkExistingReport&userid=${userid}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.exists && data.report) {
                        this.displayReport(data.report.report_content);
                    } else {
                        this.displayGenerateButton();
                    }
                } else {
                    this.displayError(data.error || 'Unknown error', data.file, data.line);
                }
            })
            .catch(error => {
                console.error('Panel load error:', error);
                this.displayError('Network error: ' + error.message);
            });
        },

        displayGenerateButton: function() {
            const contentDiv = document.getElementById('onboardingPanelContent');
            contentDiv.innerHTML = `
                <div class="generate-report-section">
                    <p>아직 온보딩 리포트가 생성되지 않았습니다.</p>
                    <button class="btn-generate-report" id="btnGenerateReport">
                        리포트 생성하기
                    </button>
                </div>
            `;

            // Attach event listener after DOM insertion
            const btnGenerate = document.getElementById('btnGenerateReport');
            if (btnGenerate) {
                btnGenerate.addEventListener('click', () => {
                    console.log('리포트 생성 버튼 클릭됨');
                    this.generateReport();
                });
            }
        },

        displayReport: function(reportHTML) {
            const contentDiv = document.getElementById('onboardingPanelContent');
            contentDiv.innerHTML = reportHTML;

            // Add MBTI management and regenerate buttons
            const actionsDiv = document.createElement('div');
            actionsDiv.className = 'panel-actions';
            actionsDiv.innerHTML = `
                <div class="mbti-section">
                    <h4>MBTI 추가/변경</h4>
                    <div class="mbti-input-group">
                        <input type="text" id="mbtiInput" maxlength="4" placeholder="ENFP, ISTJ 등" class="mbti-input">
                        <button class="btn-save-mbti" id="btnSaveMbti">
                            MBTI 저장
                        </button>
                    </div>
                </div>
                <button class="btn-regenerate-report" id="btnRegenerateReport">
                    리포트 재생성
                </button>
            `;
            contentDiv.appendChild(actionsDiv);

            // Attach event listeners after DOM insertion
            const btnSaveMbti = document.getElementById('btnSaveMbti');
            const btnRegenerate = document.getElementById('btnRegenerateReport');

            if (btnSaveMbti) {
                btnSaveMbti.addEventListener('click', () => {
                    console.log('MBTI 저장 버튼 클릭됨');
                    this.saveMBTI();
                });
            }

            if (btnRegenerate) {
                btnRegenerate.addEventListener('click', () => {
                    console.log('리포트 재생성 버튼 클릭됨');
                    this.regenerateReport();
                });
            }
        },

        saveMBTI: function() {
            const mbtiInput = document.getElementById('mbtiInput');
            const mbti = mbtiInput.value.trim();

            if (!mbti) {
                alert('MBTI를 입력해주세요 (예: ENFP, ISTJ)');
                return;
            }

            if (mbti.length !== 4) {
                alert('MBTI는 4자리여야 합니다 (예: ENFP, ISTJ)');
                return;
            }

            // Show loading
            const contentDiv = document.getElementById('onboardingPanelContent');
            const originalContent = contentDiv.innerHTML;
            contentDiv.innerHTML = '<div class="panel-loading">MBTI 저장 중</div>';

            fetch('/moodle/local/augmented_teacher/alt42/orchestration/agents/agent01_onboarding/report_service.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=saveMBTI&userid=${this.currentUserId}&mbti=${encodeURIComponent(mbti)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.showNotification('MBTI가 저장되었습니다!', 'success');
                    // Reload panel content to show updated MBTI
                    this.loadPanelContent(this.currentUserId);
                } else {
                    this.showNotification('MBTI 저장 실패: ' + (data.error || 'Unknown error'), 'error');
                    contentDiv.innerHTML = originalContent;
                }
            })
            .catch(error => {
                console.error('MBTI save error:', error);
                this.showNotification('네트워크 오류: ' + error.message, 'error');
                contentDiv.innerHTML = originalContent;
            });
        },

        generateReport: function() {
            this.performReportGeneration();
        },

        regenerateReport: function() {
            if (confirm('리포트를 재생성하시겠습니까? 기존 리포트는 보관됩니다.')) {
                this.performReportGeneration();
            }
        },

        performReportGeneration: function() {
            const contentDiv = document.getElementById('onboardingPanelContent');
            contentDiv.innerHTML = '<div class="panel-loading">리포트 생성 중</div>';

            fetch('/moodle/local/augmented_teacher/alt42/orchestration/agents/agent01_onboarding/report_generator.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=generateReport&userid=${this.currentUserId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.displayReport(data.reportHTML);

                    // Show success message
                    this.showNotification(
                        data.reportType === 'regenerated'
                            ? '리포트가 재생성되었습니다!'
                            : '리포트가 생성되었습니다!',
                        'success'
                    );
                } else {
                    this.displayError(data.error || 'Report generation failed', data.file, data.line);
                }
            })
            .catch(error => {
                console.error('Report generation error:', error);
                this.displayError('Network error: ' + error.message);
            });
        },

        displayError: function(message, file, line) {
            const contentDiv = document.getElementById('onboardingPanelContent');
            const errorInfo = (file && line) ? `<br><small>파일: ${file}, 라인: ${line}</small>` : '';
            contentDiv.innerHTML = `
                <div class="error">
                    <strong>오류 발생:</strong> ${message}${errorInfo}
                </div>
                <div class="panel-actions">
                    <button class="btn-generate-report" id="btnRetry">
                        다시 시도
                    </button>
                </div>
            `;

            // Attach event listener after DOM insertion
            const btnRetry = document.getElementById('btnRetry');
            if (btnRetry) {
                btnRetry.addEventListener('click', () => {
                    console.log('다시 시도 버튼 클릭됨, userid:', this.currentUserId);
                    this.loadPanelContent(this.currentUserId);
                });
            }
        },

        showNotification: function(message, type) {
            // Simple notification system
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 420px;
                background: ${type === 'success' ? '#10b981' : '#ef4444'};
                color: white;
                padding: 12px 20px;
                border-radius: 8px;
                z-index: 10000;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            `;
            notification.textContent = message;
            document.body.appendChild(notification);

            setTimeout(() => notification.remove(), 3000);
        },

        attachEventListeners: function() {
            // Close panel when clicking outside
            document.addEventListener('click', (e) => {
                if (this.panelElement &&
                    this.panelElement.classList.contains('active') &&
                    !this.panelElement.contains(e.target) &&
                    !e.target.closest('.agent-card')) {
                    this.close();
                }
            });

            // Close on Escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.panelElement.classList.contains('active')) {
                    this.close();
                }
            });
        }
    };

    // Make globally accessible
    console.log('✅ Assigning OnboardingPanel to window object');
    window.OnboardingPanel = OnboardingPanel;

    // Initialize when DOM is ready
    console.log('📄 Document readyState:', document.readyState);
    if (document.readyState === 'loading') {
        console.log('⏳ Waiting for DOMContentLoaded...');
        document.addEventListener('DOMContentLoaded', () => {
            console.log('✅ DOMContentLoaded fired - initializing panel');
            OnboardingPanel.init();
        });
    } else {
        console.log('✅ DOM already ready - initializing panel immediately');
        OnboardingPanel.init();
    }

    console.log('🎉 panel.js IIFE complete - OnboardingPanel exported to window');
})();
