/**
 * Agent Garden JavaScript
 * File: alt42/orchestration/agents/agent22_module_improvement/ui/agent_garden.js
 * 
 * 에이전트 가든 UI JavaScript
 */

(function() {
    'use strict'; 

    // API 경로 설정 (서버에서 전달된 경로 우선 사용)
    let API_BASE = 'agent_garden.controller.php'; // 기본값
    
    if (window.AGENT_GARDEN_CONFIG && window.AGENT_GARDEN_CONFIG.apiBase) {
        API_BASE = window.AGENT_GARDEN_CONFIG.apiBase;
    } else {
        // 폴백: 현재 스크립트의 디렉토리 경로를 기준으로 API 경로 설정
        const currentPath = window.location.pathname;
        const currentDir = currentPath.substring(0, currentPath.lastIndexOf('/') + 1);
        API_BASE = currentDir + 'agent_garden.controller.php';
    }
    
    // 디버깅: 경로 확인
    console.log('[Agent Garden] Current path:', window.location.pathname);
    console.log('[Agent Garden] API_BASE:', API_BASE);
    let selectedAgentId = null;
    let selectedAgentName = null;
    
    // 에이전트별 대화 히스토리 저장소
    const agentChatHistory = {};
    
    // URL 파라미터에서 userid 가져오기 (우선순위 1)
    const urlParams = new URLSearchParams(window.location.search);
    let targetUserId = urlParams.get('userid');
     
    // window.AGENT_GARDEN_CONFIG에서 가져오기 (우선순위 2)
    if (!targetUserId && window.AGENT_GARDEN_CONFIG && window.AGENT_GARDEN_CONFIG.targetUserId) {
        targetUserId = window.AGENT_GARDEN_CONFIG.targetUserId;
    }
    
    // 디버깅: targetUserId 확인
    console.log('[Agent Garden] targetUserId from URL:', urlParams.get('userid'));
    console.log('[Agent Garden] targetUserId from config:', window.AGENT_GARDEN_CONFIG && window.AGENT_GARDEN_CONFIG.targetUserId);
    console.log('[Agent Garden] Final targetUserId:', targetUserId);
  
    // DOM 요소
    const agentListEl = document.getElementById('agentList');
    const selectedAgentEl = document.getElementById('selectedAgent');
    const messagesEl = document.getElementById('messages');
    const messageInputEl = document.getElementById('messageInput');
    const sendButtonEl = document.getElementById('sendButton');

    /**
     * 초기화
     */
    function init() {
        loadAgentList();
        setupEventListeners();
    }

    /**
     * 에이전트 목록 로드
     */
    async function loadAgentList() {
        try {
            const url = `${API_BASE}?action=get_agents`;
            console.log('[Agent Garden] Fetching agent list from:', url);
            const response = await fetch(url);
            
            if (!response.ok) {
                console.error('[Agent Garden] Response not OK:', response.status, response.statusText);
                const text = await response.text();
                console.error('[Agent Garden] Response text:', text.substring(0, 500));
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const result = await response.json();

            if (result.success && result.data) {
                renderAgentList(result.data);
            } else {
                showError('에이전트 목록을 불러오는데 실패했습니다.');
            }
        } catch (error) {
            console.error('Error loading agent list:', error);
            showError('에이전트 목록을 불러오는데 실패했습니다.');
        }
    }

    /**
     * 에이전트 목록 렌더링
     */
    function renderAgentList(agents) {
        agentListEl.innerHTML = '';
        
        agents.forEach(agent => {
            const agentItem = document.createElement('div');
            agentItem.className = 'agent-garden__agent-item';
            agentItem.dataset.agentId = agent.id;
            
            agentItem.innerHTML = `
                <span class="agent-garden__agent-icon">${agent.icon}</span>
                <div class="agent-garden__agent-info">
                    <div class="agent-garden__agent-name">${agent.name}</div>
                    <div class="agent-garden__agent-desc">${agent.description}</div>
                </div>
            `;

            agentItem.addEventListener('click', () => selectAgent(agent));
            agentListEl.appendChild(agentItem);
        });
    }

    /**
     * 현재 대화 히스토리 저장
     */
    function saveCurrentChat() {
        if (!selectedAgentId) return;
        
        // 환영 메시지 제외한 모든 메시지 저장
        const messages = [];
        const messageElements = messagesEl.querySelectorAll('.agent-garden__message');
        
        messageElements.forEach(msgEl => {
            const messageContent = msgEl.querySelector('.agent-garden__message-content');
            if (!messageContent) return;
            
            const type = msgEl.classList.contains('agent-garden__message--user') ? 'user' :
                        msgEl.classList.contains('agent-garden__message--agent') ? 'agent' :
                        msgEl.classList.contains('agent-garden__message--system') ? 'system' :
                        msgEl.classList.contains('agent-garden__message--error') ? 'error' : 'unknown';
            
            // 텍스트 추출 (HTML 리포트 제외)
            let text = '';
            const textPart = messageContent.querySelector('div[style*="white-space: pre-wrap"]');
            if (textPart) {
                text = textPart.textContent || '';
            } else {
                // HTML 리포트가 없는 경우 전체 텍스트
                const reportPart = messageContent.querySelector('.agent-garden__detailed-report');
                if (!reportPart) {
                    text = messageContent.textContent || '';
                } else {
                    // 리포트가 있으면 리포트 앞의 텍스트만
                    const clone = messageContent.cloneNode(true);
                    const reportClone = clone.querySelector('.agent-garden__detailed-report');
                    if (reportClone) {
                        reportClone.remove();
                    }
                    text = clone.textContent || '';
                }
            }
            
            // HTML 리포트 추출
            const htmlContent = messageContent.querySelector('.agent-garden__detailed-report');
            const html = htmlContent ? htmlContent.innerHTML : null;
            
            messages.push({
                type: type,
                text: text.trim(),
                htmlContent: html,
                id: msgEl.id
            });
        });
        
        agentChatHistory[selectedAgentId] = {
            messages: messages,
            savedAt: Date.now()
        };
        
        console.log(`[Agent Garden] Saved chat history for ${selectedAgentId}:`, messages.length, 'messages');
    }
    
    /**
     * 에이전트의 대화 히스토리 로드
     */
    function loadAgentChat(agentId) {
        // 환영 메시지 제외한 모든 메시지 제거
        const messageElements = messagesEl.querySelectorAll('.agent-garden__message');
        messageElements.forEach(msgEl => msgEl.remove());
        
        // 저장된 대화가 있으면 복원
        if (agentChatHistory[agentId] && agentChatHistory[agentId].messages.length > 0) {
            console.log(`[Agent Garden] Loading chat history for ${agentId}:`, agentChatHistory[agentId].messages.length, 'messages');
            
            agentChatHistory[agentId].messages.forEach(msg => {
                addMessage(msg.type, msg.text, false, msg.htmlContent);
            });
            
            // 스크롤을 맨 아래로
            setTimeout(() => {
                messagesEl.scrollTop = messagesEl.scrollHeight;
            }, 100);
        } else {
            console.log(`[Agent Garden] No saved chat history for ${agentId}`);
        }
    }
    
    /**
     * 에이전트 선택
     */
    function selectAgent(agent) {
        // 이전 에이전트의 대화 저장
        if (selectedAgentId && selectedAgentId !== agent.id) {
            saveCurrentChat();
        }
        
        selectedAgentId = agent.id;
        selectedAgentName = agent.name;
        
        // 전역 변수로 설정 (index.php의 selectQuestion 함수에서 사용)
        window.selectedAgentId = agent.id;

        // UI 업데이트
        document.querySelectorAll('.agent-garden__agent-item').forEach(item => {
            item.classList.remove('agent-garden__agent-item--active');
        });
        
        const selectedItem = document.querySelector(`[data-agent-id="${agent.id}"]`);
        if (selectedItem) {
            selectedItem.classList.add('agent-garden__agent-item--active');
        }

        selectedAgentEl.textContent = `${agent.icon} ${agent.name}`;
        
        console.log('[Agent Garden] Agent selected:', agent.id, agent.name);
        
        // 저장된 대화가 있으면 복원, 없으면 질문 목록 표시
        if (agentChatHistory[agent.id] && agentChatHistory[agent.id].messages.length > 0) {
            // 대화가 있으면 환영 메시지 숨기고 대화 표시
            const welcomeEl = document.getElementById('welcomeSection');
            if (welcomeEl) {
                welcomeEl.style.display = 'none';
            }
            loadAgentChat(agent.id);
        } else {
            // 대화가 없으면 질문 목록 표시
            displayAgentQuestions(agent.id);
            switchToAgentView();
        }
    }
    
    /**
     * 에이전트 화면으로 전환
     */
    function switchToAgentView() {
        // 환영 메시지 표시 (숨겨져 있으면 다시 표시)
        const welcomeEl = document.getElementById('welcomeSection');
        if (welcomeEl) {
            welcomeEl.style.display = 'block';
            
            // 질문 목록으로 스크롤 이동
            setTimeout(() => {
                const questionsDiv = document.getElementById('comprehensiveQuestions');
                if (questionsDiv) {
                    questionsDiv.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'start' 
                    });
                }
            }, 100);
        }
    }
    
    /**
     * HTML 이스케이프 함수
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    /**
     * JavaScript 문자열 이스케이프 함수
     */
    function escapeJs(text) {
        return text.replace(/\\/g, '\\\\')
                   .replace(/'/g, "\\'")
                   .replace(/"/g, '\\"')
                   .replace(/\n/g, '\\n')
                   .replace(/\r/g, '\\r');
    }
    
    /**
     * 에이전트별 포괄형 질문 목록 표시
     * agent_questions_renderer.js의 displayAgentQuestions 함수 사용
     */
    function displayAgentQuestions(agentId) {
        // 별도 파일로 분리된 렌더러 함수 사용
        if (typeof window.displayAgentQuestions === 'function') {
            window.displayAgentQuestions(agentId);
        } else {
            // 폴백: 기본 메시지 표시
            const welcomeEl = messagesEl.querySelector('.agent-garden__welcome');
            if (welcomeEl) {
                const questionsDiv = welcomeEl.querySelector('#comprehensiveQuestions');
                if (questionsDiv) {
                    questionsDiv.innerHTML = '<p style="color: #999;">질문 렌더러를 로드하는 중...</p>';
                }
            }
        }
    }

    /**
     * 메시지 전송 (전역 함수로 노출)
     */
    window.sendMessage = async function sendMessage() {
        if (!selectedAgentId) {
            alert('에이전트를 먼저 선택하세요.');
            return;
        }

        const request = messageInputEl.value.trim();
        if (!request) {
            return;
        }

        // 사용자 메시지 표시
        addMessage('user', request);
        messageInputEl.value = '';

        // 로딩 표시 (애니메이션 효과)
        const loadingId = addMessage('agent', '처리 중...', true);
        
        // 로딩 메시지 업데이트 (5초마다)
        let loadingCounter = 0;
        const loadingInterval = setInterval(() => {
            loadingCounter++;
            const loadingEl = document.getElementById(loadingId);
            if (loadingEl) {
                const dots = '.'.repeat((loadingCounter % 4));
                const messageContent = loadingEl.querySelector('.agent-garden__message-content');
                if (messageContent) {
                    messageContent.textContent = `처리 중${dots}`;
                }
            }
        }, 500);

        try {
            console.log('[Agent Garden] Sending request:', {
                agent_id: selectedAgentId,
                request: request,
                student_id: targetUserId
            });
            
            // 요청 본문 구성 (userid 우선 포함)
            const requestBody = {
                agent_id: selectedAgentId,
                request: request
            };
            
            // targetUserId가 있으면 포함 (없으면 서버에서 $USER->id 사용)
            if (targetUserId) {
                requestBody.student_id = parseInt(targetUserId);
            }
            // targetUserId가 없어도 서버에서 $USER->id를 자동으로 사용하므로 명시적으로 포함하지 않아도 됨
            
            // 타임아웃 설정 (90초 - 리포트 생성 시간 고려)
            const controller = new AbortController();
            const timeoutId = setTimeout(() => {
                console.error('[Agent Garden] Request timeout after 90 seconds');
                controller.abort();
            }, 90000);
            
            const startTime = Date.now();
            let response;
            try {
                // URL에 userid가 있으면 포함 (없으면 서버에서 $USER->id 사용)
                const urlParams = targetUserId ? `&userid=${targetUserId}` : '';
                console.log('[Agent Garden] Fetching:', `${API_BASE}?action=execute${urlParams}`);
                response = await fetch(`${API_BASE}?action=execute${urlParams}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json; charset=utf-8'
                    },
                    body: JSON.stringify(requestBody),
                    signal: controller.signal
                });
                clearTimeout(timeoutId);
                const elapsedTime = Date.now() - startTime;
                console.log('[Agent Garden] Response received in', elapsedTime, 'ms');
            } catch (error) {
                clearTimeout(timeoutId);
                const elapsedTime = Date.now() - startTime;
                console.error('[Agent Garden] Fetch error after', elapsedTime, 'ms:', error);
                if (error.name === 'AbortError') {
                    throw new Error('요청 시간이 초과되었습니다. (90초) 리포트 생성에 시간이 오래 걸릴 수 있습니다.');
                }
                throw error;
            }

            // 응답 상태 확인
            console.log('[Agent Garden] Response status:', response.status, response.statusText);
            
            if (!response.ok) {
                const errorText = await response.text();
                console.error('[Agent Garden] HTTP Error:', response.status, errorText);
                throw new Error(`서버 오류 (${response.status}): ${errorText}`);
            }

            const responseText = await response.text();
            console.log('[Agent Garden] Response text length:', responseText.length);
            console.log('[Agent Garden] Response preview:', responseText.substring(0, 500));
            
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (parseError) {
                console.error('[Agent Garden] JSON parse error:', parseError);
                console.error('[Agent Garden] Response text:', responseText);
                throw new Error('서버 응답을 파싱할 수 없습니다: ' + parseError.message);
            }
            
            console.log('[Agent Garden] Parsed result:', result);
            
            // JSON 파싱 오류 확인
            if (!result) {
                throw new Error('서버 응답이 비어있습니다.');
            }

            // 로딩 인터벌 정리
            clearInterval(loadingInterval);
            
            // 로딩 메시지 제거
            const loadingEl = document.getElementById(loadingId);
            if (loadingEl) {
                loadingEl.remove();
            }

            if (result.success) {
                // reportHTML이 직접 있는 경우 (fallback 리포트)
                if (result.reportHTML) {
                    const reportText = '학생의 입력사항을 토대로 상세 분석 리포트를 생성했습니다. 아래 리포트를 확인해주세요.';
                    addMessage('agent', reportText, false, result.reportHTML);
                }
                // 일반 응답 구조 (result.data.response)
                else if (result.data && result.data.response) {
                    const responseData = result.data.response;
                    let responseText = '';

                    if (typeof responseData === 'string') {
                        responseText = responseData;
                    } else if (responseData.message) {
                        responseText = responseData.message;
                    } else if (responseData.fallback_message) {
                        // 에러 발생 시 fallback 메시지 표시
                        responseText = responseData.fallback_message;
                        if (responseData.message) {
                            console.error('Agent error:', responseData.message);
                        }
                    } else {
                        responseText = JSON.stringify(responseData, null, 2);
                    }

                    // PyYAML 설치 URL이 있으면 링크 추가
                    if (responseData && responseData.install_url) {
                        responseText += '\n\n🔧 PyYAML 설치 페이지: ' + responseData.install_url;
                    }

                    // 적용된 룰 정보 및 사용된 룰 목록 표시
                    let ruleInfoHtml = '';
                    if (result.data && result.data.response) {
                        const response = result.data.response;
                        
                        // 사용된 룰 목록이 있으면 표시
                        if (response.used_rules && Array.isArray(response.used_rules) && response.used_rules.length > 0) {
                            let rulesHtml = '<div style="margin-bottom: 8px; padding: 8px 12px; background: #f0f9ff; border-left: 3px solid #2563eb; border-radius: 4px; font-size: 0.85em; line-height: 1.5;">';
                            rulesHtml += '<strong>🔹 사용된 룰 (rules.yaml):</strong><br>';
                            
                            response.used_rules.forEach((r, index) => {
                                const conf = r.confidence ? ` <span style="color: #059669;">(${Math.round(r.confidence * 100)}%)</span>` : '';
                                rulesHtml += `<div style="margin-top: ${index > 0 ? '6px' : '4px'}; padding-left: 8px;">`;
                                rulesHtml += `<strong>${r.display}</strong>${conf}`;
                                if (r.description) {
                                    rulesHtml += `<br><span style="color: #6b7280; font-size: 0.9em;">${r.description}</span>`;
                                }
                                if (r.rationale) {
                                    rulesHtml += `<br><span style="color: #9ca3af; font-size: 0.85em; font-style: italic;">→ ${r.rationale}</span>`;
                                }
                                rulesHtml += '</div>';
                            });
                            
                            rulesHtml += '</div>';
                            ruleInfoHtml = rulesHtml;
                        } else if (result.data.matched_rule) {
                            // matched_rule만 있는 경우
                            const ruleId = result.data.matched_rule;
                            const ruleDisplay = ruleId.replace(/^([A-Z]\d+).*$/, '$1').replace(/_/g, ' ');
                            ruleInfoHtml = `<div style="margin-bottom: 8px; padding: 6px 10px; background: #f0f9ff; border-left: 3px solid #2563eb; border-radius: 4px; font-size: 0.85em;">
                                <strong>🔹 적용 룰 (rules.yaml):</strong> ${ruleDisplay}
                            </div>`;
                        }
                    }
                    
                    // 온톨로지 결과 HTML 생성
                    let ontologyHtml = '';
                    
                    // 온톨로지 전략 표시
                    if (responseData.ontology_strategy) {
                        const strategy = responseData.ontology_strategy;
                        ontologyHtml += '<div style="margin-top: 1rem; padding: 1rem; background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border-left: 4px solid #2563eb; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">';
                        ontologyHtml += '<strong style="color: #1e40af; font-size: 1.1em; display: block; margin-bottom: 0.75rem;">📋 온톨로지 기반 첫 수업 전략</strong>';
                        
                        // 학습 스타일 섹션
                        let hasStyleInfo = false;
                        let styleHtml = '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.5rem; margin-bottom: 0.75rem;">';
                        
                        const learningStyle = strategy['mk:hasMathLearningStyle'] || strategy['mathLearningStyle'];
                        if (learningStyle) {
                            styleHtml += `<div style="padding: 0.5rem; background: white; border-radius: 4px;"><strong>🎯 학습 스타일:</strong> ${escapeHtml(learningStyle)}</div>`;
                            hasStyleInfo = true;
                        }
                        const studyStyle = strategy['mk:hasStudyStyle'] || strategy['studyStyle'];
                        if (studyStyle) {
                            styleHtml += `<div style="padding: 0.5rem; background: white; border-radius: 4px;"><strong>📚 공부 스타일:</strong> ${escapeHtml(studyStyle)}</div>`;
                            hasStyleInfo = true;
                        }
                        const examStyle = strategy['mk:hasExamStyle'] || strategy['examPreparationStyle'];
                        if (examStyle) {
                            styleHtml += `<div style="padding: 0.5rem; background: white; border-radius: 4px;"><strong>📝 시험 대비:</strong> ${escapeHtml(examStyle)}</div>`;
                            hasStyleInfo = true;
                        }
                        const confidence = strategy['mk:hasMathConfidence'] || strategy['mathSelfConfidence'];
                        if (confidence !== undefined && confidence !== null) {
                            const confLevel = parseInt(confidence);
                            const confEmoji = confLevel >= 7 ? '💪' : confLevel >= 4 ? '👍' : '🌱';
                            styleHtml += `<div style="padding: 0.5rem; background: white; border-radius: 4px;"><strong>${confEmoji} 수학 자신감:</strong> ${confidence}/10</div>`;
                            hasStyleInfo = true;
                        }
                        styleHtml += '</div>';
                        if (hasStyleInfo) ontologyHtml += styleHtml;
                        
                        // 진도 정보 섹션
                        let hasProgressInfo = false;
                        let progressHtml = '<div style="margin-bottom: 0.75rem; padding: 0.75rem; background: white; border-radius: 4px;">';
                        progressHtml += '<strong style="color: #1e40af;">📈 학습 진도</strong><br>';
                        
                        const conceptProgress = strategy['mk:hasConceptProgress'] || strategy['conceptProgressLevel'];
                        if (conceptProgress) {
                            progressHtml += `<div style="margin-top: 0.25rem;">• 개념 진도: ${escapeHtml(conceptProgress)}</div>`;
                            hasProgressInfo = true;
                        }
                        const advancedProgress = strategy['mk:hasAdvancedProgress'] || strategy['advancedProgressLevel'];
                        if (advancedProgress) {
                            progressHtml += `<div style="margin-top: 0.25rem;">• 심화 진도: ${escapeHtml(advancedProgress)}</div>`;
                            hasProgressInfo = true;
                        }
                        const unitMastery = strategy['mk:hasUnitMastery'];
                        if (unitMastery) {
                            progressHtml += `<div style="margin-top: 0.25rem;">• 단원 숙달도: ${escapeHtml(unitMastery)}</div>`;
                            hasProgressInfo = true;
                        }
                        progressHtml += '</div>';
                        if (hasProgressInfo) ontologyHtml += progressHtml;
                        
                        // 추천 정보 섹션
                        let hasRecommendInfo = false;
                        let recommendHtml = '<div style="padding: 0.75rem; background: #fef3c7; border-radius: 4px; border-left: 3px solid #f59e0b;">';
                        recommendHtml += '<strong style="color: #92400e;">💡 추천 사항</strong><br>';
                        
                        const recommendsUnits = strategy['mk:recommendsUnits'];
                        if (recommendsUnits) {
                            const units = Array.isArray(recommendsUnits) ? recommendsUnits.join(', ') : recommendsUnits;
                            recommendHtml += `<div style="margin-top: 0.25rem;">• 추천 단원: ${escapeHtml(units)}</div>`;
                            hasRecommendInfo = true;
                        }
                        const recommendsDifficulty = strategy['mk:recommendsDifficulty'];
                        if (recommendsDifficulty) {
                            recommendHtml += `<div style="margin-top: 0.25rem;">• 추천 난이도: ${escapeHtml(recommendsDifficulty)}</div>`;
                            hasRecommendInfo = true;
                        }
                        const introRoutine = strategy['mk:recommendsIntroductionRoutine'];
                        if (introRoutine) {
                            recommendHtml += `<div style="margin-top: 0.25rem;">• 도입 루틴: ${escapeHtml(introRoutine)}</div>`;
                            hasRecommendInfo = true;
                        }
                        const explainStrategy = strategy['mk:recommendsExplanationStrategy'];
                        if (explainStrategy) {
                            recommendHtml += `<div style="margin-top: 0.25rem;">• 설명 전략: ${escapeHtml(explainStrategy)}</div>`;
                            hasRecommendInfo = true;
                        }
                        const materialType = strategy['mk:recommendsMaterialType'];
                        if (materialType) {
                            recommendHtml += `<div style="margin-top: 0.25rem;">• 자료 유형: ${escapeHtml(materialType)}</div>`;
                            hasRecommendInfo = true;
                        }
                        recommendHtml += '</div>';
                        if (hasRecommendInfo) ontologyHtml += recommendHtml;
                        
                        ontologyHtml += '</div>';
                    }
                    
                    // 온톨로지 절차 표시
                    if (responseData.ontology_procedure && Array.isArray(responseData.ontology_procedure)) {
                        const procedureSteps = responseData.ontology_procedure;
                        if (procedureSteps.length > 0) {
                            ontologyHtml += '<div style="margin-top: 1rem; padding: 1rem; background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-left: 4px solid #10b981; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">';
                            ontologyHtml += '<strong style="color: #065f46; font-size: 1.1em; display: block; margin-bottom: 0.75rem;">📝 수업 절차 (온톨로지 기반)</strong>';
                            
                            procedureSteps.forEach((step, index) => {
                                const order = step['mk:stepOrder'] || step['order'] || (index + 1);
                                const type = step['mk:stepType'] || step['type'] || '';
                                const desc = step['mk:stepDescription'] || step['description'] || '';
                                const duration = step['mk:stepDuration'] || step['duration'] || '';
                                
                                const typeColor = {
                                    'introduction': '#3b82f6',
                                    'explanation': '#8b5cf6',
                                    'practice': '#10b981',
                                    'review': '#f59e0b',
                                    'closing': '#6b7280'
                                }[type.toLowerCase()] || '#6b7280';
                                
                                ontologyHtml += `<div style="margin-top: 0.5rem; padding: 0.75rem; background: white; border-radius: 6px; display: flex; align-items: flex-start; gap: 0.75rem;">`;
                                ontologyHtml += `<span style="background: ${typeColor}; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.85em; font-weight: bold; white-space: nowrap;">${order}. ${escapeHtml(type)}</span>`;
                                ontologyHtml += `<div style="flex: 1;"><span>${escapeHtml(desc)}</span>`;
                                if (duration) {
                                    ontologyHtml += `<span style="color: #6b7280; font-size: 0.85em; margin-left: 0.5rem;">(${escapeHtml(duration)})</span>`;
                                }
                                ontologyHtml += `</div></div>`;
                            });
                            
                            ontologyHtml += '</div>';
                        }
                    }
                    
                    // 온톨로지 디버그 정보 표시
                    if (responseData && responseData.ontology_debug) {
                        const debug = responseData.ontology_debug;
                        ontologyHtml += '<div style="margin-top: 1.5rem; padding: 1rem; background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-left: 4px solid #d97706; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">';
                        ontologyHtml += '<strong style="color: #92400e; font-size: 1.1em; display: block; margin-bottom: 0.75rem;">🔍 온톨로지 사용 정보 (JSON-LD 디버그)</strong>';
                        
                        // 파이프라인 상태
                        ontologyHtml += `<div style="margin-bottom: 0.5rem; padding: 0.5rem; background: white; border-radius: 4px;">`;
                        ontologyHtml += `<strong>파이프라인 상태:</strong> ${debug.pipeline_success ? '✅ 성공' : '❌ 실패'}`;
                        ontologyHtml += `</div>`;
                        
                        // 스키마 정보
                        if (debug.schema_info && !debug.schema_info.error) {
                            ontologyHtml += `<div style="margin-bottom: 0.5rem; padding: 0.5rem; background: white; border-radius: 4px;">`;
                            ontologyHtml += `<strong>📄 스키마 파일:</strong> 온톨로지.jsonld<br>`;
                            ontologyHtml += `<strong>클래스 수:</strong> ${debug.schema_info.class_count || 0}개, `;
                            ontologyHtml += `<strong>프로퍼티 수:</strong> ${debug.schema_info.property_count || 0}개`;
                            ontologyHtml += `</div>`;
                            
                            // 사용된 클래스
                            if (debug.schema_info.classes_used) {
                                ontologyHtml += `<div style="margin-bottom: 0.5rem; padding: 0.5rem; background: white; border-radius: 4px;">`;
                                ontologyHtml += `<strong>사용된 클래스:</strong><br>`;
                                ontologyHtml += `<code style="font-size: 0.85em; color: #2563eb;">${debug.schema_info.classes_used.join(', ')}</code>`;
                                ontologyHtml += `</div>`;
                            }
                        }
                        
                        // 생성된 인스턴스
                        if (debug.instances_created && debug.instances_created.length > 0) {
                            ontologyHtml += `<div style="margin-bottom: 0.5rem; padding: 0.5rem; background: white; border-radius: 4px;">`;
                            ontologyHtml += `<strong>생성된 인스턴스 (${debug.instances_created.length}개):</strong>`;
                            ontologyHtml += `<ul style="margin: 0.5rem 0 0 1rem; padding: 0; font-size: 0.85em;">`;
                            debug.instances_created.forEach(inst => {
                                const shortId = inst.instance_id.length > 50 ? inst.instance_id.substring(0, 50) + '...' : inst.instance_id;
                                ontologyHtml += `<li><strong>${escapeHtml(inst.stage)}:</strong> <code>${escapeHtml(shortId)}</code></li>`;
                            });
                            ontologyHtml += `</ul></div>`;
                        }
                        
                        // JSON-LD 데이터 (접기/펼치기)
                        if (debug.jsonld_data) {
                            ontologyHtml += `<details style="margin-top: 0.5rem; background: white; border-radius: 4px; padding: 0.5rem;">`;
                            ontologyHtml += `<summary style="cursor: pointer; font-weight: bold; color: #92400e;">📋 JSON-LD 데이터 보기 (클릭하여 펼치기)</summary>`;
                            
                            if (debug.jsonld_data.strategy) {
                                ontologyHtml += `<div style="margin-top: 0.5rem;">`;
                                ontologyHtml += `<strong style="color: #2563eb;">전략 (FirstClassStrategy):</strong>`;
                                ontologyHtml += `<pre style="background: #1e293b; color: #22c55e; padding: 0.75rem; border-radius: 4px; overflow-x: auto; font-size: 0.8em; max-height: 300px; overflow-y: auto;">${escapeHtml(JSON.stringify(debug.jsonld_data.strategy.data, null, 2))}</pre>`;
                                ontologyHtml += `</div>`;
                            }
                            
                            if (debug.jsonld_data.procedure) {
                                ontologyHtml += `<div style="margin-top: 0.5rem;">`;
                                ontologyHtml += `<strong style="color: #10b981;">절차 (LessonProcedure) - ${debug.jsonld_data.procedure.steps_count || 0}단계:</strong>`;
                                ontologyHtml += `<pre style="background: #1e293b; color: #22c55e; padding: 0.75rem; border-radius: 4px; overflow-x: auto; font-size: 0.8em; max-height: 300px; overflow-y: auto;">${escapeHtml(JSON.stringify(debug.jsonld_data.procedure.data, null, 2))}</pre>`;
                                ontologyHtml += `</div>`;
                            }
                            
                            ontologyHtml += `</details>`;
                        }
                        
                        ontologyHtml += '</div>';
                    }
                    
                    // 상세 리포트가 있으면 HTML로 표시
                    if (responseData && responseData.has_detailed_report && responseData.detailed_report) {
                        // 리포트 앞에 룰 정보와 온톨로지 정보 추가
                        const reportWithRule = ruleInfoHtml + ontologyHtml + responseData.detailed_report;
                        addMessage('agent', responseText, false, reportWithRule);
                    } else {
                        // 온보딩 정보가 있으면 추가 표시
                        let finalResponseText = responseText;
                        if (responseData && responseData.onboarding_info && responseData.onboarding_info.summary) {
                            finalResponseText += '\n\n' + responseData.onboarding_info.summary;
                        }
                        
                        // 룰 정보를 텍스트로 추가
                        if (ruleInfoHtml) {
                            const tempDiv = document.createElement('div');
                            tempDiv.innerHTML = ruleInfoHtml;
                            const ruleText = tempDiv.textContent || tempDiv.innerText || '';
                            finalResponseText += '\n\n' + ruleText;
                        }
                        
                        // 온톨로지 정보가 있으면 HTML로 표시
                        if (ontologyHtml) {
                            addMessage('agent', finalResponseText, false, ontologyHtml);
                        } else {
                            addMessage('agent', finalResponseText);
                        }
                    }
                }
                // result.data가 없는 경우 (직접 메시지)
                else if (result.message) {
                    addMessage('agent', result.message);
                } else {
                    console.warn('[Agent Garden] Unexpected response structure:', result);
                    addMessage('agent', '응답을 처리하는 중 오류가 발생했습니다. 응답 구조를 확인해주세요.');
                }
            } else {
                // 에러 응답 처리 - 상세 메시지 표시
                let errorMessage = '에이전트 실행에 실패했습니다.';
                if (result.message) {
                    errorMessage += '\n\n상세 정보: ' + result.message;
                }
                if (result.error) {
                    errorMessage += '\n오류: ' + result.error;
                }
                console.error('Agent execution failed:', result);
                addMessage('error', errorMessage);
            }
        } catch (error) {
            console.error('Error executing agent:', error);
            
            // 로딩 인터벌 정리
            if (typeof loadingInterval !== 'undefined') {
                clearInterval(loadingInterval);
            }
            
            // 로딩 메시지 제거
            const loadingEl = document.getElementById(loadingId);
            if (loadingEl) {
                loadingEl.remove();
            }

            let errorMessage = '에이전트 실행 중 오류가 발생했습니다.';
            if (error.message) {
                errorMessage += '\n\n상세 정보: ' + error.message;
            }
            
            // 타임아웃 에러인 경우 특별 처리
            if (error.message && error.message.includes('시간이 초과')) {
                errorMessage += '\n\nPython 스크립트 실행이 너무 오래 걸리고 있습니다. 서버 로그를 확인해주세요.';
            }
            
            addMessage('error', errorMessage);
        }
    }

    /**
     * 메시지 추가
     */
    function addMessage(type, text, isLoading = false, htmlContent = null) {
        // 환영 메시지가 보이면 숨기기 (대화가 시작되면)
        if (type === 'user' || type === 'agent') {
            const welcomeEl = document.getElementById('welcomeSection');
            if (welcomeEl && welcomeEl.style.display !== 'none') {
                welcomeEl.style.display = 'none';
            }
        }
        
        const messageId = 'msg_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        const messageEl = document.createElement('div');
        messageEl.id = messageId;
        messageEl.className = `agent-garden__message agent-garden__message--${type}`;
        
        if (isLoading) {
            messageEl.classList.add('agent-garden__message--loading');
        }

        const messageContent = document.createElement('div');
        messageContent.className = 'agent-garden__message-content';
        
        // 리포트 콘텐츠가 있으면 마크다운으로 렌더링
        if (htmlContent) {
            // 텍스트 메시지 먼저 표시
            if (text && text.trim()) {
                const textPart = document.createElement('div');
                textPart.style.whiteSpace = 'pre-wrap';
                textPart.style.marginBottom = '0.5rem';
                textPart.textContent = text;
                messageContent.appendChild(textPart);
            }
            
            // 마크다운 리포트 렌더링
            const reportPart = document.createElement('div');
            reportPart.className = 'agent-garden__detailed-report agent-garden__markdown-report';
            reportPart.style.marginTop = '0.5rem';
            
            // 마크다운을 HTML로 변환
            const markdownHtml = markdownToHtml(htmlContent);
            reportPart.innerHTML = markdownHtml;
            messageContent.appendChild(reportPart);
        } else {
            messageContent.style.whiteSpace = 'pre-wrap';
            messageContent.textContent = text;
        }
        
        messageEl.appendChild(messageContent);

        messagesEl.appendChild(messageEl);
        messagesEl.scrollTop = messagesEl.scrollHeight;

        return messageId;
    }

    /**
     * 마크다운을 HTML로 변환
     */
    function markdownToHtml(markdown) {
        if (!markdown) return '';
        
        // CSS 스타일 블록 제거 (예: .class{...}, body{...} 등)
        let html = markdown.replace(/\.[a-zA-Z0-9_-]+\s*\{[^}]*\}/gs, '');
        html = html.replace(/[a-zA-Z0-9_-]+\s*\{[^}]*\}/gs, '');
        html = html.replace(/\{[^}]*\}/gs, '');
        
        // <style> 태그와 내용 제거
        html = html.replace(/<style[^>]*>.*?<\/style>/gis, '');
        
        // HTML 태그 제거 (혹시 포함된 경우)
        html = html.replace(/<[^>]+>/g, '');
        
        // HTML 엔티티 디코딩
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;
        html = tempDiv.textContent || tempDiv.innerText || html;
        
        // 코드블록 제거 (혹시 남아있는 경우)
        html = html.replace(/```[a-z]*\s*\n?/gi, '');
        html = html.replace(/```\s*\n?/g, '');
        html = html.replace(/```/g, '');
        
        // CSS 관련 키워드가 포함된 줄 제거
        let lines = html.split('\n');
        const cleanedLines = [];
        for (const line of lines) {
            const trimmed = line.trim();
            // CSS 스타일 관련 키워드가 포함된 줄 제거
            if (/^(\.|@media|body|html|font-family|background|color|margin|padding|border|display|grid|flex)/i.test(trimmed)) {
                continue;
            }
            // CSS 속성이 포함된 줄 제거
            if (/\{[^}]*\}/.test(trimmed) && /[:;]/.test(trimmed)) {
                continue;
            }
            cleanedLines.push(line);
        }
        html = cleanedLines.join('\n');
        
        // 줄 단위로 처리하기 위해 분할
        lines = html.split('\n');
        const processedLines = [];
        let inList = false;
        let listType = null; // 'ul' or 'ol'
        
        for (let i = 0; i < lines.length; i++) {
            const line = lines[i];
            const trimmedLine = line.trim();
            
            // 헤더 처리
            if (trimmedLine.match(/^####\s+(.+)$/)) {
                if (inList) {
                    processedLines.push(listType === 'ol' ? '</ol>' : '</ul>');
                    inList = false;
                    listType = null;
                }
                processedLines.push('<h4>' + trimmedLine.replace(/^####\s+/, '') + '</h4>');
                continue;
            } else if (trimmedLine.match(/^###\s+(.+)$/)) {
                if (inList) {
                    processedLines.push(listType === 'ol' ? '</ol>' : '</ul>');
                    inList = false;
                    listType = null;
                }
                processedLines.push('<h3>' + trimmedLine.replace(/^###\s+/, '') + '</h3>');
                continue;
            } else if (trimmedLine.match(/^##\s+(.+)$/)) {
                if (inList) {
                    processedLines.push(listType === 'ol' ? '</ol>' : '</ul>');
                    inList = false;
                    listType = null;
                }
                processedLines.push('<h2>' + trimmedLine.replace(/^##\s+/, '') + '</h2>');
                continue;
            } else if (trimmedLine.match(/^#\s+(.+)$/)) {
                if (inList) {
                    processedLines.push(listType === 'ol' ? '</ol>' : '</ul>');
                    inList = false;
                    listType = null;
                }
                processedLines.push('<h1>' + trimmedLine.replace(/^#\s+/, '') + '</h1>');
                continue;
            }
            
            // 번호 목록: 1. item
            const numberedMatch = trimmedLine.match(/^(\d+)\.\s+(.+)$/);
            if (numberedMatch) {
                if (!inList || listType !== 'ol') {
                    if (inList) {
                        processedLines.push('</ul>');
                    }
                    processedLines.push('<ol>');
                    inList = true;
                    listType = 'ol';
                }
                processedLines.push('<li>' + numberedMatch[2] + '</li>');
                continue;
            }
            
            // 불릿 목록: - item 또는 * item
            const bulletMatch = trimmedLine.match(/^[\-\*]\s+(.+)$/);
            if (bulletMatch) {
                if (!inList || listType !== 'ul') {
                    if (inList) {
                        processedLines.push('</ol>');
                    }
                    processedLines.push('<ul>');
                    inList = true;
                    listType = 'ul';
                }
                processedLines.push('<li>' + bulletMatch[1] + '</li>');
                continue;
            }
            
            // 빈 줄이면 목록 종료
            if (trimmedLine === '') {
                if (inList) {
                    processedLines.push(listType === 'ol' ? '</ol>' : '</ul>');
                    inList = false;
                    listType = null;
                }
                processedLines.push('');
                continue;
            }
            
            // 일반 텍스트
            if (inList) {
                processedLines.push(listType === 'ol' ? '</ol>' : '</ul>');
                inList = false;
                listType = null;
            }
            processedLines.push(line);
        }
        
        // 마지막 목록 닫기
        if (inList) {
            processedLines.push(listType === 'ol' ? '</ol>' : '</ul>');
        }
        
        html = processedLines.join('\n');
        
        // 볼드: **text**
        html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        
        // 이탤릭: *text* (볼드가 아닌 경우만, 단어 경계 확인)
        html = html.replace(/(?<!\*)\*([^*\s][^*]*?[^*\s])\*(?!\*)/g, '<em>$1</em>');
        
        // 인라인 코드: `code`
        html = html.replace(/`([^`\n]+?)`/g, '<code>$1</code>');
        
        // 링크: [text](url)
        html = html.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank">$1</a>');
        
        // 연속된 빈 줄 제거 (3개 이상 -> 2개로, 2개 이상 -> 1개로)
        html = html.replace(/\n{3,}/g, '\n\n');
        html = html.replace(/\n\n\n+/g, '\n\n');
        
        // 헤더 바로 앞의 빈 줄 제거 (최대 1개만 허용)
        html = html.replace(/\n{2,}(<h[1-4]>)/g, '\n$1');
        html = html.replace(/\n+(<h[1-4]>)/g, '\n$1');
        
        // 헤더 바로 뒤의 빈 줄 제거 (최대 1개만 허용)
        html = html.replace(/(<\/h[1-4]>)\n{2,}/g, '$1\n');
        
        // 목록 바로 앞의 빈 줄 제거
        html = html.replace(/\n{2,}(<ul>|<ol>)/g, '\n$1');
        html = html.replace(/\n+(<ul>|<ol>)/g, '\n$1');
        
        // 목록 바로 뒤의 빈 줄 제거
        html = html.replace(/(<\/ul>|<\/ol>)\n{2,}/g, '$1\n');
        
        // 줄바꿈 처리: 두 개의 줄바꿈은 문단 구분
        html = html.replace(/\n\n+/g, '</p><p>');
        html = '<p>' + html + '</p>';
        
        // 빈 문단 제거
        html = html.replace(/<p>\s*<\/p>/g, '');
        html = html.replace(/<p>(<h[1-4]>)/g, '$1');
        html = html.replace(/(<\/h[1-4]>)<\/p>/g, '$1');
        html = html.replace(/<p>(<ul>|<ol>)/g, '$1');
        html = html.replace(/(<\/ul>|<\/ol>)<\/p>/g, '$1');
        
        // 단일 줄바꿈은 <br>로 변환 (문단 내에서만, 헤더/목록 제외)
        html = html.replace(/([^\n>])\n([^\n<])/g, '$1<br>$2');
        
        // 최종 빈 줄 정리
        html = html.replace(/\n{3,}/g, '\n\n');
        
        return html;
    }
    
    /**
     * 에러 메시지 표시
     */
    function showError(message) {
        addMessage('error', message);
    }

    /**
     * 이벤트 리스너 설정
     */
    function setupEventListeners() {
        sendButtonEl.addEventListener('click', sendMessage);
        
        messageInputEl.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
    }

    // 초기화 실행
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

