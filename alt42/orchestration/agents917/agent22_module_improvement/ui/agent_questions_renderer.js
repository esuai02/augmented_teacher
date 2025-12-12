/**
 * 에이전트별 질문 렌더링 모듈
 * 포괄형 질문과 데이터 기반 질문을 모두 표시
 */

(function() {
    'use strict';
    
    /**
     * 에이전트별 질문 목록 표시 (포괄형 + 데이터 기반)
     */
    function displayAgentQuestions(agentId) {
        const questionsData = window.AGENT_QUESTIONS_DATA;
        const dataBasedQuestions = window.dataBasedQuestionSets || {};
        
        if (!questionsData || !questionsData[agentId]) {
            // 질문 데이터가 없으면 기본 메시지 표시
            const welcomeEl = document.querySelector('.agent-garden__welcome');
            if (welcomeEl) {
                const questionsDiv = welcomeEl.querySelector('#comprehensiveQuestions');
                if (questionsDiv) {
                    questionsDiv.innerHTML = '<p style="color: #999;">이 에이전트의 포괄형 질문 데이터가 아직 준비되지 않았습니다.</p>';
                }
            }
            return;
        } 
        
        const agentData = questionsData[agentId];
        const questionsDiv = document.getElementById('comprehensiveQuestions');
        if (!questionsDiv) return;
        
        // 환영 메시지가 숨겨져 있으면 다시 표시
        const welcomeEl = document.getElementById('welcomeSection');
        if (welcomeEl && welcomeEl.style.display === 'none') {
            welcomeEl.style.display = 'block';
        }
        
        // 기존 내용 제거
        questionsDiv.innerHTML = '';
        
        // 제목 추가
        const title = document.createElement('h3');
        title.style.cssText = 'margin-bottom: 1rem; color: #667eea; font-size: 1.2em;';
        title.textContent = `📋 ${agentData.name} 에이전트 질문 목록`;
        questionsDiv.appendChild(title);
        
        // 포괄형 질문 섹션
        const comprehensiveSection = document.createElement('div');
        comprehensiveSection.style.marginBottom = '2rem';
        
        const comprehensiveTitle = document.createElement('h4');
        comprehensiveTitle.style.cssText = 'margin-bottom: 0.75rem; color: #4f46e5; font-size: 1.1em; font-weight: 600;';
        comprehensiveTitle.textContent = '📌 포괄형 질문';
        comprehensiveSection.appendChild(comprehensiveTitle);
        
        // 각 포괄형 질문 그룹 생성
        agentData.questions.forEach((q, index) => {
            const qId = `q${index + 1}`;
            const questionGroup = createComprehensiveQuestionGroup(q, qId, index + 1);
            comprehensiveSection.appendChild(questionGroup);
        });
        
        questionsDiv.appendChild(comprehensiveSection);
        
        // 모든 포괄형 질문을 기본적으로 펼쳐진 상태로 강제 설정 (DOM이 완전히 렌더링된 후)
        // 여러 번 시도하여 확실하게 적용
        const forceExpandQuestions = () => {
            agentData.questions.forEach((q, index) => {
                const qId = `q${index + 1}`;
                const content = document.getElementById(`${qId}-content`);
                const icon = document.getElementById(`${qId}-icon`);
                const group = content ? content.closest('.question-group') : null;
                
                if (content && group) {
                    // 강제로 표시 상태로 설정 (!important 사용)
                    content.setAttribute('style', 'display: block !important;');
                    group.classList.add('expanded');
                    // 아이콘도 회전된 상태로 설정
                    if (icon) {
                        icon.setAttribute('style', 'transform: rotate(90deg);');
                    }
                }
            });
        };
        
        // 즉시 실행
        forceExpandQuestions();
        // DOM 렌더링 후 다시 실행
        setTimeout(forceExpandQuestions, 50);
        setTimeout(forceExpandQuestions, 200);
        
        // 데이터 기반 질문 섹션
        const agentDataBased = dataBasedQuestions[agentId];
        if (agentDataBased && Object.keys(agentDataBased).length > 0) {
            const dataBasedSection = document.createElement('div');
            dataBasedSection.style.marginTop = '2rem';
            dataBasedSection.style.paddingTop = '1.5rem';
            dataBasedSection.style.borderTop = '2px solid #e5e7eb';
            
            const dataBasedTitle = document.createElement('h4');
            dataBasedTitle.style.cssText = 'margin-bottom: 0.75rem; color: #059669; font-size: 1.1em; font-weight: 600;';
            dataBasedTitle.textContent = '📊 데이터 기반 질문';
            dataBasedSection.appendChild(dataBasedTitle);
            
            // 각 포괄형 질문에 대한 데이터 기반 질문 세트 표시
            agentData.questions.forEach((q, index) => {
                const questionNum = index + 1;
                const dataBasedSet = agentDataBased[questionNum];
                
                if (dataBasedSet && dataBasedSet.questionSets && dataBasedSet.questionSets.length > 0) {
                    const dbGroup = createDataBasedQuestionGroup(q, questionNum, dataBasedSet);
                    dataBasedSection.appendChild(dbGroup);
                }
            });
            
            questionsDiv.appendChild(dataBasedSection);
        }
    }
    
    /**
     * 포괄형 질문 그룹 생성
     */
    function createComprehensiveQuestionGroup(q, qId, questionNum) {
        // 질문 그룹 컨테이너
        const group = document.createElement('div');
        group.className = 'question-group';
        
        // 헤더
        const header = document.createElement('div');
        header.className = 'question-header';
        header.onclick = () => toggleQuestion(qId);
        
        const icon = document.createElement('span');
        icon.className = 'question-icon';
        icon.id = `${qId}-icon`;
        // 기본적으로 펼쳐진 상태이므로 아이콘도 회전된 상태로 표시
        icon.textContent = '▶';
        icon.style.transform = 'rotate(90deg)';
        
        const titleSpan = document.createElement('span');
        titleSpan.className = 'question-title';
        titleSpan.textContent = `Q${questionNum}. ${q.title}`;
        
        header.appendChild(icon);
        header.appendChild(titleSpan);
        group.appendChild(header);
        
        // 콘텐츠
        const content = document.createElement('div');
        content.className = 'question-content';
        content.id = `${qId}-content`;
        // Q1, Q2, Q3 모두 기본적으로 펼쳐진 상태로 표시
        // setAttribute를 사용하여 인라인 스타일을 더 강력하게 설정
        content.setAttribute('style', 'display: block !important;');
        group.classList.add('expanded');
        
        // 메인 질문
        const mainQ = document.createElement('div');
        mainQ.className = 'question-main';
        mainQ.onclick = () => selectQuestion(q.mainQuestion);
        const mainText = document.createElement('span');
        mainText.className = 'question-text';
        mainText.textContent = `• ${q.mainQuestion}`;
        mainQ.appendChild(mainText);
        content.appendChild(mainQ);
        
        // 구분선
        const divider = document.createElement('div');
        divider.className = 'question-divider';
        content.appendChild(divider);
        
        // 포괄질문 답변 요청 (기본)
        const primarySub = document.createElement('div');
        primarySub.className = 'question-sub question-sub-primary';
        primarySub.onclick = () => selectQuestion(q.mainQuestion);
        const primaryText = document.createElement('span');
        primaryText.className = 'question-text';
        primaryText.textContent = '  📌 포괄질문에 대한 답변 요청하기';
        primarySub.appendChild(primaryText);
        content.appendChild(primarySub);
        
        // 하위 질문들
        if (q.subQuestions && q.subQuestions.length > 0) {
            q.subQuestions.forEach(subQ => {
                const subDiv = document.createElement('div');
                subDiv.className = 'question-sub';
                subDiv.onclick = () => selectQuestion(subQ);
                const subText = document.createElement('span');
                subText.className = 'question-text';
                subText.textContent = `  - ${subQ}`;
                subDiv.appendChild(subText);
                content.appendChild(subDiv);
            });
        }
        
        group.appendChild(content);
        return group;
    }
    
    /**
     * 데이터 기반 질문 그룹 생성
     */
    function createDataBasedQuestionGroup(q, questionNum, dataBasedSet) {
        const dbGroupId = `db-q${questionNum}`;
        const group = document.createElement('div');
        group.className = 'question-group';
        group.style.marginBottom = '1rem';
        
        // 헤더 (접기/펼치기 가능)
        const header = document.createElement('div');
        header.className = 'question-header';
        header.style.cssText = 'background: #ecfdf5; border-color: #10b981;';
        header.onclick = () => toggleQuestion(dbGroupId);
        
        const icon = document.createElement('span');
        icon.className = 'question-icon';
        icon.id = `${dbGroupId}-icon`;
        icon.textContent = '▶';
        
        const titleSpan = document.createElement('span');
        titleSpan.className = 'question-title';
        titleSpan.style.color = '#059669';
        titleSpan.textContent = `Q${questionNum} 데이터 기반 질문 세트`;
        
        header.appendChild(icon);
        header.appendChild(titleSpan);
        group.appendChild(header);
        
        // 콘텐츠
        const content = document.createElement('div');
        content.className = 'question-content';
        content.id = `${dbGroupId}-content`;
        content.style.display = 'none';
        content.style.background = '#f0fdf4';
        
        // 각 질문 세트 표시
        if (dataBasedSet.questionSets && dataBasedSet.questionSets.length > 0) {
            dataBasedSet.questionSets.forEach((questionSet, setIndex) => {
                // 질문 세트 제목
                if (questionSet.title) {
                    const setTitle = document.createElement('div');
                    setTitle.style.cssText = 'padding: 8px 16px; font-weight: 500; color: #047857; font-size: 0.9em; background: #d1fae5;';
                    setTitle.textContent = `📊 ${questionSet.title}`;
                    content.appendChild(setTitle);
                }
                
                // 질문들
                if (questionSet.questions && questionSet.questions.length > 0) {
                    questionSet.questions.forEach((dbQuestion, qIndex) => {
                        const questionDiv = document.createElement('div');
                        questionDiv.className = 'question-sub';
                        questionDiv.style.cssText = 'padding: 8px 16px 8px 32px; cursor: pointer; transition: background 0.2s; border-top: 1px solid #d1fae5; font-size: 0.85em; color: #065f46;';
                        questionDiv.onclick = () => selectQuestion(dbQuestion.text);
                        questionDiv.onmouseover = function() { this.style.background = '#a7f3d0'; };
                        questionDiv.onmouseout = function() { this.style.background = 'transparent'; };
                        
                        const questionText = document.createElement('span');
                        questionText.className = 'question-text';
                        questionText.textContent = `${qIndex + 1}. ${dbQuestion.text}`;
                        questionDiv.appendChild(questionText);
                        
                        // 데이터 소스 표시 (있는 경우, 작은 글씨로)
                        if (dbQuestion.dataSources && dbQuestion.dataSources.length > 0) {
                            const dataSourceSpan = document.createElement('div');
                            dataSourceSpan.style.cssText = 'margin-top: 4px; font-size: 0.75em; color: #6b7280; font-style: italic; padding-left: 8px;';
                            const shortSources = dbQuestion.dataSources.slice(0, 3).join(', ');
                            const moreCount = dbQuestion.dataSources.length > 3 ? ` 외 ${dbQuestion.dataSources.length - 3}개` : '';
                            dataSourceSpan.textContent = `[데이터: ${shortSources}${moreCount}]`;
                            questionDiv.appendChild(dataSourceSpan);
                        }
                        
                        content.appendChild(questionDiv);
                    });
                }
            });
        }
        
        group.appendChild(content);
        return group;
    }
    
    /**
     * 질문 선택 및 자동 요청 (내부 함수)
     */
    function selectQuestion(questionText) {
        // 전역 selectQuestion 함수가 있으면 사용
        if (typeof window.selectQuestion === 'function') {
            window.selectQuestion(questionText);
        } else {
            // 직접 처리
            const selectedAgentEl = document.getElementById('selectedAgent');
            if (!selectedAgentEl || selectedAgentEl.textContent.includes('에이전트를 선택하세요')) {
                alert('먼저 에이전트를 선택해주세요.');
                return;
            }
            
            // 환영 메시지 숨기기
            const welcomeEl = document.getElementById('welcomeSection');
            if (welcomeEl) {
                welcomeEl.style.display = 'none';
            }
            
            // 질문을 입력란에 설정
            const messageInput = document.getElementById('messageInput');
            if (messageInput) {
                messageInput.value = questionText;
            }
            
            // 약간의 지연 후 전송
            setTimeout(function() {
                if (typeof window.sendMessage === 'function') {
                    window.sendMessage();
                } else {
                    const sendButton = document.getElementById('sendButton');
                    if (sendButton) {
                        sendButton.click();
                    }
                }
            }, 100);
        }
    }
    
    /**
     * 질문 펼치기/접기 (내부 함수)
     */
    function toggleQuestion(qId) {
        // 전역 toggleQuestion 함수가 있으면 사용
        if (typeof window.toggleQuestion === 'function') {
            window.toggleQuestion(qId);
        } else {
            // 직접 처리
            const content = document.getElementById(qId + '-content');
            const icon = document.getElementById(qId + '-icon');
            const group = content ? content.closest('.question-group') : null;
            
            if (content && group) {
                if (content.style.display === 'none') {
                    content.style.display = 'block';
                    group.classList.add('expanded');
                } else {
                    content.style.display = 'none';
                    group.classList.remove('expanded');
                }
            }
        }
    }
    
    // 전역 함수로 노출
    window.displayAgentQuestions = displayAgentQuestions;
    
})();

