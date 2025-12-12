/**
 * Agent 16 Interaction Preparation Panel Controller
 * File: /mnt/c/1 Project/augmented_teacher/alt42/orchestration/agents/agent16_interaction_preparation/ui/panel.js
 * Location: Line 1
 */

(function() {
    'use strict';

    // Guide mode descriptions (from orchestration_hs2/assets/js/workflow_state.js)
    const guideModeDescriptions = {
        '커리큘럼': `커리큘럼 중심 모드

학생이 할 일은 선생님이 제시한 순서를 따라가며, 메인 교재를 기준으로 진행합니다.
커리큘럼에서 이탈하거나 교재 이외의 탐구는 최소화하고,
필요시 보충 자료는 교사의 판단 하에 선별적으로 제공합니다.

중요한 점은 학생이 자기 진행 속도를 인지할 수 있도록,
평균 진도율과 현재 위치를 시각적으로 확인하게 하고,
진도에서 뒤처질 경우 별도 지원 사항(보충 수업, 개별 피드백 등)을 안내합니다.`,

        '맞춤학습': `맞춤성장 중심 모드

학생의 강점과 약점, 학습 성향을 정밀 분석하여 개인화된 학습 경로를 설계합니다.
MBTI, 학습 스타일 진단, 지능 유형 등 다양한 프레임을 활용해 최적화된 전략을 도출합니다.
강점은 더욱 극대화하고, 약점은 단계별로 보완하는 균형 잡힌 접근을 추구합니다.

개인의 생체 리듬, 집중 시간대, 환경 선호도까지 고려하여
학습 환경 자체를 학생에게 맞춰 조정합니다.
성장 과정을 데이터로 기록하여 객관적인 피드백을 제공합니다.`,

        '시험대비': `시험대비 중심 모드

D-Day 기반으로 시간 역산해서 분량을 나누고, 매일 확인 가능한 수치적 목표를 제시합니다.
기출문제 유형을 분석해 취약 유형에 집중 투입하며, 실전 시뮬레이션을 정기적으로 실시합니다.
오답 노트 작성법, 시간 관리 전략을 구체화하고,
시험 직전에는 멘탈 관리와 컨디션 조절 가이드를 제공합니다.

합격/목표 점수를 명확히 정의하고, 합격 확률을 주기적으로 업데이트하여
학생이 진행 상황을 객관적으로 인식하게 돕습니다.`,

        '단기미션': `단기미션 중심 모드

일일/주간 단위로 달성 가능한 구체적 미션을 제시하고, 즉각적인 피드백과 보상을 제공합니다.
게이미피케이션 요소(포인트, 뱃지, 리더보드 등)를 활용해 동기를 유지시킵니다.
미션 난이도는 학생의 실력에 맞춰 동적으로 조정되며,
연속 달성 스트릭을 통해 습관 형성을 돕습니다.

실패해도 재도전 기회를 제공하고, 부분 성공도 인정하여
완벽주의 압박을 줄이고 지속 가능한 학습을 추구합니다.`,

        '자기성찰': `자기성찰 중심 모드

학습 이후 행동 피드백–감정 반응–인지 성찰을 순차적으로 정리합니다.
성찰은 일지 형식으로 저장되며, 주간/월간 주기로 정제 및 요약해 학습 전략에 반영됩니다.
성찰은 자기비판이 아닌 과정과 태도에 대한 관찰 중심이며,
자신만의 반복되는 장애 패턴을 인식해 그것에 맞는 해결 루틴을 따로 설정합니다.
목표는 실행률 향상보다 학습 과정의 질적 향상에 두며, 결과는 자연스럽게 따라오게 만듭니다.`,

        '자기주도': `자기주도 중심 모드

자신의 장단점, 에너지 흐름, 집중 스타일을 반영해 학습 환경과 리듬을 스스로 설계합니다.
외부 피드백은 도입하되 맹목적으로 수용하지 않고 비판적 통합을 원칙으로 합니다.
학습 계획은 구글 캘린더, Notion 등 디지털 도구를 활용해 구조화하고,
성취 기록은 데이터베이스화하여 자기만의 알고리즘을 점진적으로 구축해 갑니다.
멘탈 관리 루틴(산책, 독서, 마인드셋 트레이닝 등)을 학습과 통합시켜, 완전한 자율 시스템을 추구합니다.`,

        '도제학습': `도제학습 중심 모드

교사나 선배의 사고 흐름을 그대로 따라가되, 복사(copy)가 아니라 변형(adapt)을 목표로 합니다.
풀이를 배운 뒤 같은 문제를 스스로 풀면서, 언어로 사고 과정을 설명하는 연습을 합니다.
다양한 풀이법을 비교 분석하고, 그 중 가장 적절한 선택 기준을 메타 인지 차원에서 기록합니다.
반복된 사고 실수를 통해 자신의 고유한 사고 버그를 파악하고,
그것을 수정하기 위한 사고 훈련(사고 체계화, 역방향 풀이 등)을 병행합니다.`,

        '시간성찰': `시간성찰 중심 모드

공부 시간 자체보다, 집중 상태와 시간 활용 밀도를 기준으로 하루를 평가합니다.
포모도로 기록, 타임트래킹 앱, 집중도 메모 등을 활용하여 시간을 수치화하고,
정성적 메모(집중력 저하 원인, 최적의 리듬)와 함께 분석합니다.
시간 성찰은 감정적으로 반응하지 않고, 학습 조건의 최적화 문제로 접근합니다.
성과가 낮은 날에는 왜 그랬는가보다 무엇을 조정할까에 초점을 둡니다.`,

        '탐구학습': `탐구학습 중심 모드

왜, 어떻게, 그럼 무엇이? 등 질문을 주도적으로 생성하며, 학습 동기를 호기심으로부터 끌어냅니다.
단순 암기가 아닌 구조적 이해를 추구하며, 스스로 탐구한 내용을 재정리하고 시각화합니다.
AI나 책, 친구, 교사 등 다양한 리소스를 조합해 지식 탐색 루트를 개척합니다.
궁금한 점은 즉각 메모해 탐구 대기열을 만들고, 주기적으로 해당 리스트를 해결해갑니다.
탐구 결과는 주변 사람과 공유하거나 정리하여 지식의 흐름을 고립시키지 않습니다.`
    };

    // GPT chat links for each guide mode (from orchestration_hs2/assets/js/workflow_state.js)
    const guideGPTLinks = {
        '커리큘럼':'https://chatgpt.com/g/g-68ac30293ca08191812d5005018220d6',
        '맞춤학습':'https://chatgpt.com/g/g-68ac302a3bdc81918a5a6925b5d2b3d4',
        '시험대비':'https://chatgpt.com/g/g-68ac302aa5008191b16185863c7cd67a',
        '단기미션':'https://chatgpt.com/g/g-68ac302b44788191a1ece0a7d5c86ce5',
        '자기성찰':'https://chatgpt.com/g/g-68ac302c1074819195c1c6372d2a6c9c',
        '자기주도':'https://chatgpt.com/g/g-68ac302c8d1c8191918c2e3762c16a5b',
        '도제학습':'https://chatgpt.com/g/g-68ac302d9040819186b20e86a7e4e59f',
        '시간성찰':'https://chatgpt.com/g/g-68ac3037bfb08191841b70986b495d4e-siganseongcal-jungsimmodeu-hwalyonghagi',
        '탐구학습':'https://chatgpt.com/g/g-68ac303885bc819182244907d98fe42b-tamguhagseub-jungsimmodeu-hwalyonghagi'
    };

    const InteractionPreparationPanel = {
        panelElement: null,
        currentUserId: null,
        currentGuideMode: null,

        init: function() {
            this.createPanelElement();
            this.attachEventListeners();
        },

        createPanelElement: function() {
            // Remove existing panel if any
            const existing = document.getElementById('interactionPrepPanel');
            if (existing) {
                existing.remove();
            }

            // Create panel structure
            const panel = document.createElement('div');
            panel.id = 'interactionPrepPanel';
            panel.className = 'interaction-prep-right-panel';
            panel.innerHTML = `
                <div class="panel-header">
                    <h2>상호작용 준비</h2>
                    <button class="panel-close" onclick="InteractionPreparationPanel.close()">&times;</button>
                </div>
                <div class="panel-tabs">
                    <button class="tab-btn active" data-tab="mode">상호작용 모드</button>
                    <button class="tab-btn" data-tab="scenario">시나리오 생성</button>
                    <button class="tab-btn" data-tab="result">생성 결과</button>
                </div>
                <div class="panel-content" id="interactionPrepPanelContent">
                    <div class="tab-content active" id="tab-mode">
                        <div class="panel-loading">로딩 중...</div>
                    </div>
                    <div class="tab-content" id="tab-scenario" style="display:none;">
                        <div class="panel-loading">로딩 중...</div>
                    </div>
                    <div class="tab-content" id="tab-result" style="display:none;">
                        <div class="panel-loading">로딩 중...</div>
                    </div>
                </div>
            `;

            document.body.appendChild(panel);
            this.panelElement = panel;
        },

        open: function(userid) {
            console.log('🎯 [Panel] open() called with userid:', userid);
            console.log('🎯 [Panel] panelElement:', this.panelElement);
            console.log('🎯 [Panel] panelElement exists in DOM:', document.body.contains(this.panelElement));

            this.currentUserId = userid;

            if (!this.panelElement) {
                console.error('❌ [Panel] panelElement is null! Calling createPanelElement()');
                this.createPanelElement();
            }

            this.panelElement.classList.add('active');
            console.log('✅ [Panel] Added "active" class. Classes:', this.panelElement.className);
            console.log('✅ [Panel] Panel style.right:', this.panelElement.style.right);
            console.log('✅ [Panel] Panel computed style:', window.getComputedStyle(this.panelElement).right);

            this.loadModeTab();
            console.log('✅ [Panel] loadModeTab() completed');
        },

        close: function() {
            this.panelElement.classList.remove('active');
        },

        attachEventListeners: function() {
            // Tab switching
            document.addEventListener('click', (e) => {
                if (e.target.classList.contains('tab-btn')) {
                    this.switchTab(e.target.dataset.tab);
                }
            });

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
        },

        switchTab: function(tabName) {
            // Switch tab buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.tab === tabName);
            });

            // Switch tab content
            document.querySelectorAll('.tab-content').forEach(content => {
                content.style.display = 'none';
            });
            document.getElementById(`tab-${tabName}`).style.display = 'block';

            // Load tab content
            if (tabName === 'mode') {
                this.loadModeTab();
            } else if (tabName === 'scenario') {
                this.loadScenarioTab();
            } else if (tabName === 'result') {
                this.loadResultTab();
            }
        },

        loadModeTab: function() {
            const tabContent = document.getElementById('tab-mode');

            let html = `
                <div class="mode-selection-section">
                    <h3>상호작용 모드 선택</h3>
                    <p style="color:#64748b; font-size:13px; margin-bottom:16px;">
                        학생의 현재 상태와 학습 목표에 가장 적합한 상호작용 모드를 선택하세요.
                    </p>

                    <div class="mode-grid">
            `;

            // Generate mode cards
            for (const [modeName, description] of Object.entries(guideModeDescriptions)) {
                const isSelected = this.currentGuideMode === modeName;
                // Get first line of description
                const shortDesc = description.split('\n')[0].trim();

                html += `
                    <div class="mode-card ${isSelected ? 'selected' : ''}"
                         data-mode="${modeName}"
                         onclick="InteractionPreparationPanel.selectMode('${modeName}')">
                        <div class="mode-card-header">
                            <h4>${modeName}</h4>
                            ${isSelected ? '<span class="selected-badge">✓</span>' : ''}
                        </div>
                        <div class="mode-card-body">
                            <p>${shortDesc}</p>
                        </div>
                        <div class="mode-card-footer">
                            <button class="btn-details" onclick="event.stopPropagation(); InteractionPreparationPanel.showModeDetails('${modeName}')">
                                상세보기
                            </button>
                        </div>
                    </div>
                `;
            }

            html += `
                    </div>

                    <div class="gpt-link-section" style="margin-top:24px; padding:16px; background:#f1f5f9; border-radius:8px;">
                        <h4 style="font-size:13px; font-weight:600; margin-bottom:8px;">🔗 GPT 대화 링크</h4>
                        <p style="font-size:12px; color:#64748b; margin-bottom:12px;">
                            선택한 모드에 대해 GPT와 대화하며 더 깊이 탐색할 수 있습니다.
                        </p>
                        <a id="gptChatLink"
                           href="https://chat.openai.com"
                           target="_blank"
                           class="btn-gpt-link">
                            GPT와 대화하기
                        </a>
                    </div>
                </div>
            `;

            tabContent.innerHTML = html;

            // Update GPT link if mode is already selected
            if (this.currentGuideMode) {
                this.updateGPTLink();
            }
        },

        selectMode: function(modeName) {
            this.currentGuideMode = modeName;

            // Update UI - remove previous selection
            document.querySelectorAll('.mode-card').forEach(card => {
                card.classList.remove('selected');
                const badge = card.querySelector('.selected-badge');
                if (badge) badge.remove();
            });

            // Add new selection
            const selectedCard = document.querySelector(`.mode-card[data-mode="${modeName}"]`);
            if (selectedCard) {
                selectedCard.classList.add('selected');
                const header = selectedCard.querySelector('.mode-card-header');
                if (header && !header.querySelector('.selected-badge')) {
                    header.innerHTML += '<span class="selected-badge">✓</span>';
                }
            }

            // Update GPT link
            this.updateGPTLink();

            console.log('✅ 모드 선택:', modeName);
        },

        updateGPTLink: function() {
            const gptLink = document.getElementById('gptChatLink');
            if (gptLink && this.currentGuideMode && guideGPTLinks[this.currentGuideMode]) {
                gptLink.href = guideGPTLinks[this.currentGuideMode];
            }
        },

        showModeDetails: function(modeName) {
            const description = guideModeDescriptions[modeName];
            if (!description) return;

            // Create modal
            const modal = document.createElement('div');
            modal.className = 'mode-details-modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>${modeName}</h3>
                        <button class="modal-close" onclick="this.closest('.mode-details-modal').remove()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <pre style="white-space: pre-wrap; font-family: inherit; line-height: 1.6;">${description}</pre>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-primary" onclick="InteractionPreparationPanel.selectMode('${modeName}'); this.closest('.mode-details-modal').remove();">
                            이 모드 선택
                        </button>
                        <button class="btn-secondary" onclick="this.closest('.mode-details-modal').remove()">
                            닫기
                        </button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        },

        loadScenarioTab: function() {
            const tabContent = document.getElementById('tab-scenario');

            // Check if a mode is selected
            if (!this.currentGuideMode) {
                tabContent.innerHTML = `
                    <div class="warning-message">
                        <div class="warning-icon">⚠️</div>
                        <h4>상호작용 모드를 먼저 선택하세요</h4>
                        <p>시나리오를 생성하기 전에 '상호작용 모드' 탭에서 적절한 모드를 선택해주세요.</p>
                        <button class="btn-primary" onclick="InteractionPreparationPanel.switchTab('mode')">
                            모드 선택하러 가기
                        </button>
                    </div>
                `;
                return;
            }

            // Scenario generation form
            tabContent.innerHTML = `
                <div class="scenario-generation-section">
                    <div class="selected-mode-info">
                        <h4>선택된 모드: <span class="mode-badge">${this.currentGuideMode}</span></h4>
                    </div>

                    <div class="prompt-section">
                        <h3>VibeCoding 프롬프트</h3>
                        <p class="prompt-description">
                            학생의 감정 상태, 학습 맥락, 성향을 고려한 맞춤형 분위기 설정 프롬프트를 입력하세요.
                        </p>
                        <textarea
                            id="vibeCodingPrompt"
                            class="prompt-textarea"
                            placeholder="예시: 학생은 현재 수학 시험을 3일 앞두고 있으며, 약간의 불안감을 보이고 있습니다. 차분하면서도 동기부여가 되는 톤으로 상호작용해주세요."
                            rows="6"
                        ></textarea>
                    </div>

                    <div class="prompt-section">
                        <h3>DBTracking 프롬프트</h3>
                        <p class="prompt-description">
                            학생의 학습 이력, 오답 패턴, 진도 현황 등 데이터베이스 추적 정보를 입력하세요.
                        </p>
                        <textarea
                            id="dbTrackingPrompt"
                            class="prompt-textarea"
                            placeholder="예시: 학생 ID: S2024001, 최근 10회 세션 중 유리수 연산 정답률 45%, 도형 영역 정답률 78%, 평균 학습 시간 45분, 선호 학습 시간대: 저녁 7-9시"
                            rows="6"
                        ></textarea>
                    </div>

                    <div class="generation-controls">
                        <button class="btn-generate" onclick="InteractionPreparationPanel.generateScenario()">
                            <span class="btn-icon">✨</span>
                            시나리오 생성
                        </button>
                        <button class="btn-reset" onclick="InteractionPreparationPanel.resetScenarioForm()">
                            초기화
                        </button>
                    </div>

                    <div id="scenarioResult" class="scenario-result" style="display:none;">
                        <div class="result-header">
                            <h4>생성된 시나리오</h4>
                            <div class="result-actions">
                                <button class="btn-copy" onclick="InteractionPreparationPanel.copyScenario()">
                                    📋 복사
                                </button>
                                <button class="btn-save" onclick="InteractionPreparationPanel.saveScenario()">
                                    💾 저장
                                </button>
                            </div>
                        </div>
                        <div id="scenarioContent" class="scenario-content">
                            <!-- Scenario content will be rendered here -->
                        </div>
                    </div>
                </div>
            `;
        },

        resetScenarioForm: function() {
            document.getElementById('vibeCodingPrompt').value = '';
            document.getElementById('dbTrackingPrompt').value = '';
            const resultDiv = document.getElementById('scenarioResult');
            if (resultDiv) {
                resultDiv.style.display = 'none';
            }
        },

        generateScenario: async function() {
            const vibeCodingPrompt = document.getElementById('vibeCodingPrompt').value.trim();
            const dbTrackingPrompt = document.getElementById('dbTrackingPrompt').value.trim();

            // Validation
            if (!vibeCodingPrompt || !dbTrackingPrompt) {
                alert('VibeCoding 프롬프트와 DBTracking 프롬프트를 모두 입력해주세요.');
                return;
            }

            if (!this.currentGuideMode) {
                alert('상호작용 모드를 먼저 선택해주세요.');
                this.switchTab('mode');
                return;
            }

            // Show loading state
            const generateBtn = document.querySelector('.btn-generate');
            const originalBtnText = generateBtn.innerHTML;
            generateBtn.disabled = true;
            generateBtn.innerHTML = '<span class="btn-icon">⏳</span> 생성 중...';

            try {
                // API call to generate scenario
                const response = await fetch('/moodle/local/augmented_teacher/alt42/orchestration/agents/agent16_interaction_preparation/api/generate_scenario.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        userid: this.currentUserId,
                        guideMode: this.currentGuideMode,
                        vibeCodingPrompt: vibeCodingPrompt,
                        dbTrackingPrompt: dbTrackingPrompt
                    })
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();

                if (data.success) {
                    // Render scenario
                    this.renderScenario(data.scenario);
                } else {
                    throw new Error(data.error || '시나리오 생성에 실패했습니다.');
                }

            } catch (error) {
                console.error('❌ 시나리오 생성 오류:', error);

                // Fallback: Generate client-side scenario
                console.log('⚠️ API 호출 실패, 클라이언트 사이드 폴백 시나리오 생성');
                const fallbackScenario = this.generateFallbackScenario(vibeCodingPrompt, dbTrackingPrompt);
                this.renderScenario(fallbackScenario);

            } finally {
                // Restore button state
                generateBtn.disabled = false;
                generateBtn.innerHTML = originalBtnText;
            }
        },

        generateFallbackScenario: function(vibeCodingPrompt, dbTrackingPrompt) {
            // Client-side fallback scenario generation
            const timestamp = new Date().toLocaleString('ko-KR');

            return `# 상호작용 시나리오 (Fallback)

## 기본 정보
- **생성 시각**: ${timestamp}
- **선택된 모드**: ${this.currentGuideMode}
- **학생 ID**: ${this.currentUserId || 'Unknown'}

## VibeCoding 맥락
${vibeCodingPrompt}

## DBTracking 데이터
${dbTrackingPrompt}

## 추천 상호작용 전략

### 1. 초기 접근
${this.currentGuideMode} 모드의 핵심 원칙을 따라 학생과의 첫 대화를 시작합니다.
학생의 현재 감정 상태를 인정하고, 안전한 학습 환경을 조성합니다.

### 2. 학습 목표 설정
학생의 데이터를 기반으로 현실적이고 달성 가능한 단기 목표를 설정합니다.
목표는 학생의 강점을 활용하면서 약점을 보완하는 방향으로 구성합니다.

### 3. 실행 계획
구체적인 학습 활동과 시간 배분을 제시합니다.
학생의 선호 학습 시간대와 집중력 패턴을 고려합니다.

### 4. 피드백 전략
학습 과정에서 즉각적이고 건설적인 피드백을 제공합니다.
실수는 학습 기회로 프레이밍하고, 성장 마인드셋을 강화합니다.

### 5. 모니터링 포인트
- 학습 진행도 추적
- 감정 상태 변화 관찰
- 집중력 유지 확인
- 목표 달성도 평가

---
*참고: 이 시나리오는 GPT-4o API 연결 실패로 인한 클라이언트 사이드 폴백 버전입니다.*
*완전한 개인화 시나리오를 위해서는 API 엔드포인트 설정이 필요합니다.*
`;
        },

        renderScenario: function(scenarioText) {
            const resultDiv = document.getElementById('scenarioResult');
            const contentDiv = document.getElementById('scenarioContent');

            if (!resultDiv || !contentDiv) return;

            // Convert markdown to HTML (simple implementation)
            const htmlContent = this.markdownToHtml(scenarioText);

            contentDiv.innerHTML = htmlContent;
            resultDiv.style.display = 'block';

            // Scroll to result
            resultDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

            // Store current scenario for copy/save operations
            this.currentScenario = scenarioText;
        },

        markdownToHtml: function(markdown) {
            // Simple markdown to HTML conversion
            let html = markdown;

            // Headers
            html = html.replace(/^### (.*$)/gim, '<h4>$1</h4>');
            html = html.replace(/^## (.*$)/gim, '<h3>$1</h3>');
            html = html.replace(/^# (.*$)/gim, '<h2>$1</h2>');

            // Bold
            html = html.replace(/\*\*(.*?)\*\*/gim, '<strong>$1</strong>');

            // Italic
            html = html.replace(/\*(.*?)\*/gim, '<em>$1</em>');

            // Lists
            html = html.replace(/^\- (.*$)/gim, '<li>$1</li>');
            html = html.replace(/(<li>.*<\/li>)/s, '<ul>$1</ul>');

            // Line breaks
            html = html.replace(/\n\n/g, '</p><p>');
            html = '<p>' + html + '</p>';

            // Clean up empty paragraphs
            html = html.replace(/<p><\/p>/g, '');
            html = html.replace(/<p>(<h[234]>)/g, '$1');
            html = html.replace(/(<\/h[234]>)<\/p>/g, '$1');
            html = html.replace(/<p>(<ul>)/g, '$1');
            html = html.replace(/(<\/ul>)<\/p>/g, '$1');

            return html;
        },

        copyScenario: function() {
            if (!this.currentScenario) {
                alert('복사할 시나리오가 없습니다.');
                return;
            }

            navigator.clipboard.writeText(this.currentScenario)
                .then(() => {
                    const copyBtn = document.querySelector('.btn-copy');
                    const originalText = copyBtn.innerHTML;
                    copyBtn.innerHTML = '✅ 복사됨!';
                    setTimeout(() => {
                        copyBtn.innerHTML = originalText;
                    }, 2000);
                })
                .catch(err => {
                    console.error('❌ 복사 실패:', err);
                    alert('클립보드 복사에 실패했습니다.');
                });
        },

        saveScenario: async function() {
            if (!this.currentScenario) {
                alert('저장할 시나리오가 없습니다.');
                return;
            }

            const saveBtn = document.querySelector('.btn-save');
            const originalText = saveBtn.innerHTML;
            saveBtn.disabled = true;
            saveBtn.innerHTML = '💾 저장 중...';

            try {
                const response = await fetch('/moodle/local/augmented_teacher/alt42/orchestration/agents/agent16_interaction_preparation/api/save_scenario.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        userid: this.currentUserId,
                        guideMode: this.currentGuideMode,
                        scenario: this.currentScenario,
                        vibeCodingPrompt: document.getElementById('vibeCodingPrompt').value,
                        dbTrackingPrompt: document.getElementById('dbTrackingPrompt').value
                    })
                });

                const data = await response.json();

                if (data.success) {
                    saveBtn.innerHTML = '✅ 저장 완료!';
                    setTimeout(() => {
                        saveBtn.innerHTML = originalText;
                        saveBtn.disabled = false;
                    }, 2000);

                    // Optionally refresh result tab
                    console.log('✅ 시나리오 저장 완료:', data.scenarioId);
                } else {
                    throw new Error(data.error || '저장에 실패했습니다.');
                }

            } catch (error) {
                console.error('❌ 시나리오 저장 오류:', error);
                alert('시나리오 저장에 실패했습니다: ' + error.message);
                saveBtn.innerHTML = originalText;
                saveBtn.disabled = false;
            }
        },

        loadResultTab: async function() {
            const tabContent = document.getElementById('tab-result');

            // Show loading state
            tabContent.innerHTML = '<div class="panel-loading">⏳ 저장된 시나리오를 불러오는 중...</div>';

            try {
                // Fetch saved scenarios
                const response = await fetch(`/moodle/local/augmented_teacher/alt42/orchestration/agents/agent16_interaction_preparation/api/list_scenarios.php?userid=${this.currentUserId}`);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.error || '시나리오 목록을 불러오는데 실패했습니다.');
                }

                // Render scenarios list
                this.renderScenariosList(data.scenarios);

            } catch (error) {
                console.error('❌ 시나리오 목록 로드 오류:', error);
                tabContent.innerHTML = `
                    <div class="error-message">
                        <div class="error-icon">❌</div>
                        <h4>시나리오 목록을 불러올 수 없습니다</h4>
                        <p>${error.message}</p>
                        <button class="btn-primary" onclick="InteractionPreparationPanel.loadResultTab()">
                            다시 시도
                        </button>
                    </div>
                `;
            }
        },

        renderScenariosList: function(scenarios) {
            const tabContent = document.getElementById('tab-result');

            if (!scenarios || scenarios.length === 0) {
                tabContent.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-icon">📭</div>
                        <h4>저장된 시나리오가 없습니다</h4>
                        <p>시나리오 생성 탭에서 새로운 시나리오를 생성하고 저장해보세요.</p>
                        <button class="btn-primary" onclick="InteractionPreparationPanel.switchTab('scenario')">
                            시나리오 생성하기
                        </button>
                    </div>
                `;
                return;
            }

            // Build scenarios list HTML
            let html = `
                <div class="results-section">
                    <div class="results-header">
                        <h3>저장된 시나리오 (${scenarios.length}개)</h3>
                        <button class="btn-refresh" onclick="InteractionPreparationPanel.loadResultTab()">
                            🔄 새로고침
                        </button>
                    </div>

                    <div class="scenarios-list">
            `;

            scenarios.forEach(scenario => {
                const previewText = this.getScenarioPreview(scenario.scenario);

                html += `
                    <div class="scenario-item" data-scenario-id="${scenario.id}">
                        <div class="scenario-item-header">
                            <div class="scenario-meta">
                                <span class="scenario-mode-badge">${scenario.guideMode}</span>
                                <span class="scenario-date">${scenario.createdAt}</span>
                            </div>
                            <div class="scenario-actions">
                                <button class="btn-icon" title="상세보기" onclick="InteractionPreparationPanel.viewScenarioDetail(${scenario.id})">
                                    👁️
                                </button>
                                <button class="btn-icon" title="복사" onclick="InteractionPreparationPanel.copyScenarioById(${scenario.id})">
                                    📋
                                </button>
                                <button class="btn-icon btn-delete" title="삭제" onclick="InteractionPreparationPanel.deleteScenarioById(${scenario.id})">
                                    🗑️
                                </button>
                            </div>
                        </div>
                        <div class="scenario-item-body">
                            <p class="scenario-preview">${previewText}</p>
                        </div>
                    </div>
                `;
            });

            html += `
                    </div>
                </div>
            `;

            tabContent.innerHTML = html;

            // Store scenarios for later reference
            this.savedScenarios = scenarios;
        },

        getScenarioPreview: function(scenarioText) {
            // Extract first meaningful paragraph from scenario
            const lines = scenarioText.split('\n').filter(line => line.trim().length > 0);

            // Find first paragraph that's not a header
            for (const line of lines) {
                if (!line.startsWith('#') && !line.startsWith('- ') && !line.startsWith('*') && line.length > 20) {
                    const preview = line.substring(0, 150);
                    return preview.length < line.length ? preview + '...' : preview;
                }
            }

            return scenarioText.substring(0, 150) + '...';
        },

        viewScenarioDetail: function(scenarioId) {
            const scenario = this.savedScenarios.find(s => s.id === scenarioId);

            if (!scenario) {
                alert('시나리오를 찾을 수 없습니다.');
                return;
            }

            // Create modal with full scenario
            const modal = document.createElement('div');
            modal.className = 'scenario-detail-modal';

            const htmlContent = this.markdownToHtml(scenario.scenario);

            modal.innerHTML = `
                <div class="modal-content modal-large">
                    <div class="modal-header">
                        <div>
                            <h3>${scenario.guideMode} 모드 시나리오</h3>
                            <p class="modal-subtitle">${scenario.createdAt}</p>
                        </div>
                        <button class="modal-close" onclick="this.closest('.scenario-detail-modal').remove()">&times;</button>
                    </div>
                    <div class="modal-body modal-scrollable">
                        ${htmlContent}
                    </div>
                    <div class="modal-footer">
                        <button class="btn-primary" onclick="InteractionPreparationPanel.copyScenarioById(${scenarioId}); this.closest('.scenario-detail-modal').remove();">
                            📋 복사
                        </button>
                        <button class="btn-secondary" onclick="this.closest('.scenario-detail-modal').remove()">
                            닫기
                        </button>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);
        },

        copyScenarioById: function(scenarioId) {
            const scenario = this.savedScenarios.find(s => s.id === scenarioId);

            if (!scenario) {
                alert('시나리오를 찾을 수 없습니다.');
                return;
            }

            navigator.clipboard.writeText(scenario.scenario)
                .then(() => {
                    // Visual feedback
                    const scenarioItem = document.querySelector(`.scenario-item[data-scenario-id="${scenarioId}"]`);
                    if (scenarioItem) {
                        const originalBg = scenarioItem.style.backgroundColor;
                        scenarioItem.style.backgroundColor = '#d4edda';
                        setTimeout(() => {
                            scenarioItem.style.backgroundColor = originalBg;
                        }, 1000);
                    }

                    console.log('✅ 시나리오 복사 완료:', scenarioId);
                })
                .catch(err => {
                    console.error('❌ 복사 실패:', err);
                    alert('클립보드 복사에 실패했습니다.');
                });
        },

        deleteScenarioById: async function(scenarioId) {
            // Confirmation
            if (!confirm('이 시나리오를 정말 삭제하시겠습니까?')) {
                return;
            }

            try {
                const response = await fetch('/moodle/local/augmented_teacher/alt42/orchestration/agents/agent16_interaction_preparation/api/delete_scenario.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        scenarioId: scenarioId
                    })
                });

                const data = await response.json();

                if (data.success) {
                    console.log('✅ 시나리오 삭제 완료:', scenarioId);

                    // Remove from UI with animation
                    const scenarioItem = document.querySelector(`.scenario-item[data-scenario-id="${scenarioId}"]`);
                    if (scenarioItem) {
                        scenarioItem.style.opacity = '0';
                        scenarioItem.style.transform = 'translateX(-20px)';
                        setTimeout(() => {
                            scenarioItem.remove();

                            // Check if list is now empty
                            const remainingItems = document.querySelectorAll('.scenario-item');
                            if (remainingItems.length === 0) {
                                this.loadResultTab();
                            }
                        }, 300);
                    }

                    // Remove from cached data
                    if (this.savedScenarios) {
                        this.savedScenarios = this.savedScenarios.filter(s => s.id !== scenarioId);
                    }
                } else {
                    throw new Error(data.error || '삭제에 실패했습니다.');
                }

            } catch (error) {
                console.error('❌ 시나리오 삭제 오류:', error);
                alert('시나리오 삭제에 실패했습니다: ' + error.message);
            }
        }
    };

    // Make globally accessible
    window.InteractionPreparationPanel = InteractionPreparationPanel;

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => InteractionPreparationPanel.init());
    } else {
        InteractionPreparationPanel.init();
    }
})();
