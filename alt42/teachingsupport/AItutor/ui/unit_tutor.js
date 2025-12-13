/**
 * AI 튜터 클라이언트 로직
 * 이미지/텍스트 입력 처리 및 결과 표시
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 * @version    1.0
 */

(function() {
    'use strict';

    // DOM 요소
    const contentText = document.getElementById('content-text');
    const contentImage = document.getElementById('content-image');
    const imageUploadArea = document.getElementById('image-upload-area');
    const previewImage = document.getElementById('preview-image');
    const analyzeBtn = document.getElementById('analyze-btn');
    const resultSection = document.getElementById('result-section');
    const loadingIndicator = document.getElementById('loading-indicator');
    
    // 분석 ID가 있으면 저장된 데이터 로드
    if (typeof analysisId !== 'undefined' && analysisId !== null && analysisId !== '') {
        console.log('저장된 분석 결과 로드 시도:', analysisId);
        loadSavedAnalysis(analysisId);
    } else {
        console.log('새로운 분석 세션 시작');
    }

    // 이미지 업로드 영역 클릭 이벤트
    imageUploadArea.addEventListener('click', () => {
        contentImage.click();
    });

    // 이미지 선택 이벤트
    contentImage.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (event) => {
                previewImage.src = event.target.result;
                previewImage.style.display = 'block';
                imageUploadArea.querySelector('.upload-placeholder').style.display = 'none';
            };
            reader.readAsDataURL(file);
        }
    });

    // 드래그 앤 드롭
    imageUploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        imageUploadArea.classList.add('drag-over');
    });

    imageUploadArea.addEventListener('dragleave', () => {
        imageUploadArea.classList.remove('drag-over');
    });

    imageUploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        imageUploadArea.classList.remove('drag-over');
        
        const file = e.dataTransfer.files[0];
        if (file && file.type.startsWith('image/')) {
            contentImage.files = e.dataTransfer.files;
            const reader = new FileReader();
            reader.onload = (event) => {
                previewImage.src = event.target.result;
                previewImage.style.display = 'block';
                imageUploadArea.querySelector('.upload-placeholder').style.display = 'none';
            };
            reader.readAsDataURL(file);
        }
    });

    // 분석 버튼 클릭 이벤트
    analyzeBtn.addEventListener('click', async () => {
        const textContent = contentText.value.trim();
        const imageFile = contentImage.files[0];

        if (!textContent && !imageFile) {
            alert('텍스트나 이미지 중 하나는 입력해주세요.');
            return;
        }

        // 로딩 표시
        resultSection.style.display = 'block';
        loadingIndicator.style.display = 'block';
        analyzeBtn.disabled = true;

        try {
            // 이미지를 base64로 변환
            let imageData = '';
            if (imageFile) {
                imageData = await fileToBase64(imageFile);
            }

            // API 호출
            const response = await fetch('api/analyze_content.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    text: textContent,
                    image: imageData,
                    student_id: getStudentId()
                })
            });

            // 응답이 JSON인지 확인
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Non-JSON response:', text.substring(0, 500));
                throw new Error('서버 응답이 JSON 형식이 아닙니다. 서버 오류가 발생했을 수 있습니다.');
            }

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.error || '분석 실패');
            }

            // 저장 상태 확인
            if (result.data.save_status) {
                if (result.data.save_status.success) {
                    console.log('분석 결과 저장 성공:', result.data.analysis_id);
                } else {
                    console.warn('분석 결과 저장 실패:', result.data.save_status.error);
                    alert('주의: 분석 결과가 서버에 저장되지 않았습니다. 페이지를 새로고침하면 결과가 사라질 수 있습니다.\n\n오류: ' + (result.data.save_status.error || '알 수 없는 오류'));
                }
            }

            // 분석 ID가 있으면 URL에 추가
            if (result.data.analysis_id) {
                const url = new URL(window.location);
                url.searchParams.set('id', result.data.analysis_id);
                window.history.pushState({}, '', url);
                
                // 저장 성공 시에만 URL 표시
                if (result.data.save_status && result.data.save_status.success) {
                    console.log('저장된 분석 결과 URL:', url.toString());
                }
            }

            // 결과 표시
            displayResults(result.data);

        } catch (error) {
            console.error('Analysis error:', error);
            
            // 더 자세한 에러 정보 표시
            let errorMessage = '분석 중 오류가 발생했습니다: ' + error.message;
            
            if (error.message.includes('JSON')) {
                errorMessage += '\n\n서버 응답을 확인할 수 없습니다. 서버 로그를 확인해주세요.';
            } else if (error.message.includes('404')) {
                errorMessage += '\n\nAPI 파일을 찾을 수 없습니다. 파일 경로를 확인해주세요.';
            } else if (error.message.includes('500')) {
                errorMessage += '\n\n서버 내부 오류가 발생했습니다. 서버 로그를 확인해주세요.';
            }
            
            alert(errorMessage);
        } finally {
            loadingIndicator.style.display = 'none';
            analyzeBtn.disabled = false;
        }
    });

    /**
     * 파일을 base64로 변환
     */
    function fileToBase64(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => resolve(reader.result);
            reader.onerror = reject;
            reader.readAsDataURL(file);
        });
    }

    /**
     * 학생 ID 가져오기
     */
    function getStudentId() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('studentid') || '';
    }

    /**
     * 저장된 분석 결과 로드
     */
    async function loadSavedAnalysis(analysisId) {
        try {
            console.log('API 호출:', `api/load_analysis.php?id=${encodeURIComponent(analysisId)}`);
            
            const response = await fetch(`api/load_analysis.php?id=${encodeURIComponent(analysisId)}`);
            
            console.log('응답 상태:', response.status, response.statusText);
            
            // 응답이 JSON인지 확인
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Non-JSON response:', text.substring(0, 500));
                throw new Error('서버 응답이 JSON 형식이 아닙니다. 로그인 상태를 확인해주세요.');
            }
            
            const result = await response.json();
            console.log('API 응답:', result);
            
            if (!result.success) {
                throw new Error(result.error || '분석 결과 로드 실패');
            }
            
            // 저장된 텍스트 내용 표시
            if (result.data.text_content) {
                contentText.value = result.data.text_content;
            }
            
            // 저장된 이미지 표시
            if (result.data.image_data) {
                previewImage.src = result.data.image_data;
                previewImage.style.display = 'block';
                imageUploadArea.querySelector('.upload-placeholder').style.display = 'none';
            }
            
            // 결과 표시
            displayResults(result.data);
            
            // 결과 영역 표시
            resultSection.style.display = 'block';
            
        } catch (error) {
            console.error('Load analysis error:', error);
            alert('저장된 분석 결과를 불러오는 중 오류가 발생했습니다: ' + error.message);
        }
    }

    /**
     * 결과 표시
     */
    function displayResults(data) {
        // 포괄적 질문 표시
        displayComprehensiveQuestions(data.comprehensive_questions);
        
        // 세부 질문 표시
        displayDetailedQuestions(data.detailed_questions);
        
        // 생성된 룰 표시
        displayRules(data.teaching_rules);
        
        // 온톨로지 표시
        displayOntology(data.ontology);
        
        // 라이브 튜터링 시작
        startLiveTutoring(data);
        
        // 분석 완료 콜백 호출 (학습 시작 버튼 활성화)
        if (data.analysis_id && typeof window.onAnalysisComplete === 'function') {
            window.onAnalysisComplete(data.analysis_id);
        }
    }

    /**
     * 포괄적 질문 표시
     */
    function displayComprehensiveQuestions(questions) {
        const container = document.getElementById('comprehensive-questions-content');
        container.innerHTML = '';

        if (!questions || questions.length === 0) {
            container.innerHTML = '<p>생성된 포괄적 질문이 없습니다.</p>';
            return;
        }

        questions.forEach((q, index) => {
            const questionCard = document.createElement('div');
            questionCard.className = 'question-card comprehensive';
            questionCard.innerHTML = `
                <div class="question-header">
                    <span class="question-id">${q.id}</span>
                    <span class="question-type">포괄적 질문</span>
                </div>
                <div class="question-body">
                    <h4>${q.question}</h4>
                    ${q.focus_areas ? `
                        <div class="focus-areas">
                            <strong>집중 영역:</strong>
                            <ul>
                                ${q.focus_areas.map(area => `<li>${area}</li>`).join('')}
                            </ul>
                        </div>
                    ` : ''}
                </div>
            `;
            container.appendChild(questionCard);
        });
    }

    /**
     * 세부 질문 표시
     */
    function displayDetailedQuestions(questions) {
        const container = document.getElementById('detailed-questions-content');
        container.innerHTML = '';

        if (!questions || questions.length === 0) {
            container.innerHTML = '<p>생성된 세부 질문이 없습니다.</p>';
            return;
        }

        questions.forEach((q) => {
            const questionCard = document.createElement('div');
            questionCard.className = 'question-card detailed';
            questionCard.innerHTML = `
                <div class="question-header">
                    <span class="question-category">${q.category}</span>
                    <span class="question-type">세부 질문</span>
                </div>
                <div class="question-body">
                    <h4>${q.question}</h4>
                    ${q.suggested_approach ? `
                        <div class="suggestions">
                            <strong>제안된 접근:</strong>
                            <ul>
                                ${q.suggested_approach.map(item => `<li>${item}</li>`).join('')}
                            </ul>
                        </div>
                    ` : ''}
                    ${q.suggested_steps ? `
                        <div class="suggestions">
                            <strong>제안된 단계:</strong>
                            <ol>
                                ${q.suggested_steps.map(step => `<li>${step}</li>`).join('')}
                            </ol>
                        </div>
                    ` : ''}
                </div>
            `;
            container.appendChild(questionCard);
        });
    }

    /**
     * 룰 표시
     */
    function displayRules(rules) {
        const container = document.getElementById('generated-rules-content');
        container.innerHTML = '';

        if (!rules || rules.length === 0) {
            container.innerHTML = '<p>생성된 룰이 없습니다.</p>';
            return;
        }

        rules.forEach((rule) => {
            const ruleCard = document.createElement('div');
            ruleCard.className = 'rule-card';
            ruleCard.innerHTML = `
                <div class="rule-header">
                    <span class="rule-id">${rule.rule_id}</span>
                    <span class="rule-priority">우선순위: ${rule.priority}</span>
                </div>
                <div class="rule-body">
                    <h4>${rule.description}</h4>
                    <div class="rule-conditions">
                        <strong>조건:</strong>
                        <pre>${JSON.stringify(rule.conditions, null, 2)}</pre>
                    </div>
                    <div class="rule-actions">
                        <strong>액션:</strong>
                        <ul>
                            ${rule.action.map(action => `<li>${action}</li>`).join('')}
                        </ul>
                    </div>
                    <div class="rule-rationale">
                        <strong>근거:</strong> ${rule.rationale}
                    </div>
                </div>
            `;
            container.appendChild(ruleCard);
        });
    }

    /**
     * 온톨로지 표시
     */
    function displayOntology(ontology) {
        const container = document.getElementById('generated-ontology-content');
        container.innerHTML = '';

        if (!ontology) {
            container.innerHTML = '<p>생성된 온톨로지가 없습니다.</p>';
            return;
        }

        // Will Layer
        if (ontology.will) {
            const willCard = document.createElement('div');
            willCard.className = 'ontology-section';
            willCard.innerHTML = `
                <h4>Will Layer (시스템 가치)</h4>
                <div class="will-core">
                    ${ontology.will.core.map(w => `
                        <div class="will-item">
                            <strong>${w.value}</strong> (우선순위: ${w.priority})
                        </div>
                    `).join('')}
                </div>
            `;
            container.appendChild(willCard);
        }

        // Intent Layer
        if (ontology.intent) {
            const intentCard = document.createElement('div');
            intentCard.className = 'ontology-section';
            intentCard.innerHTML = `
                <h4>Intent Layer (상황별 목표)</h4>
                <div class="intent-content">
                    <p><strong>세션 목표:</strong> ${ontology.intent.session_goal}</p>
                    <p><strong>단기 목표:</strong> ${ontology.intent.short_term}</p>
                    <p><strong>장기 목표:</strong> ${ontology.intent.long_term}</p>
                    <div class="intent-priority">
                        <strong>우선순위:</strong>
                        <ul>
                            ${ontology.intent.priority.map(p => `<li>${p}</li>`).join('')}
                        </ul>
                    </div>
                </div>
            `;
            container.appendChild(intentCard);
        }

        // Ontology Nodes
        if (ontology.ontology && ontology.ontology.length > 0) {
            const nodesCard = document.createElement('div');
            nodesCard.className = 'ontology-section';
            nodesCard.innerHTML = `
                <h4>Ontology Nodes</h4>
                ${ontology.ontology.map(node => `
                    <div class="ontology-node">
                        <div class="node-header">
                            <span class="node-id">${node.id}</span>
                            <span class="node-class">${node.class}</span>
                            <span class="node-stage">${node.stage}</span>
                        </div>
                        <div class="node-body">
                            <pre>${JSON.stringify(node.properties, null, 2)}</pre>
                        </div>
                    </div>
                `).join('')}
            `;
            container.appendChild(nodesCard);
        }
    }

    /**
     * 라이브 튜터링 시작
     */
    function startLiveTutoring(data) {
        const container = document.getElementById('live-tutoring-content');
        container.innerHTML = '';

        // 대화 분석 결과를 기반으로 튜터링 시작
        if (data.dialogue_analysis && data.dialogue_analysis.unit) {
            const welcomeMsg = document.createElement('div');
            welcomeMsg.className = 'tutoring-message teacher';
            welcomeMsg.innerHTML = `
                <div class="message-content">
                    <strong>AI 튜터:</strong> 안녕하세요! ${data.dialogue_analysis.unit.korean} 단원 학습을 시작하겠습니다.
                </div>
            `;
            container.appendChild(welcomeMsg);

            // 포괄적 질문을 기반으로 대화 시작
            if (data.comprehensive_questions && data.comprehensive_questions.length > 0) {
                setTimeout(() => {
                    askQuestion(data.comprehensive_questions[0], container, data);
                }, 1000);
            }
        }
    }

    /**
     * 질문하기 (OpenAI API 사용)
     */
    async function askQuestion(question, container, contextData) {
        const questionMsg = document.createElement('div');
        questionMsg.className = 'tutoring-message teacher';
        questionMsg.innerHTML = `
            <div class="message-content">
                <strong>AI 튜터:</strong> ${question.question}
            </div>
        `;
        container.appendChild(questionMsg);
        container.scrollTop = container.scrollHeight;

        // 학생 입력 영역 추가
        const inputArea = document.createElement('div');
        inputArea.className = 'tutoring-input-area';
        inputArea.innerHTML = `
            <textarea id="student-response" placeholder="답변을 입력하세요..." rows="3"></textarea>
            <button onclick="submitResponse('${question.id}', contextData)">답변 제출</button>
        `;
        container.appendChild(inputArea);

        // 전역 변수에 컨텍스트 저장
        window.currentTutoringContext = contextData;
        window.currentQuestion = question;
        window.tutoringContainer = container;
    }

    /**
     * 학생 응답 제출 및 룰/온톨로지 기반 상호작용 처리
     */
    window.submitResponse = async function(questionId, contextData) {
        const studentResponse = document.getElementById('student-response').value.trim();
        if (!studentResponse) {
            alert('답변을 입력해주세요.');
            return;
        }

        const container = window.tutoringContainer || document.getElementById('live-tutoring-content');
        
        // 학생 메시지 표시
        const studentMsg = document.createElement('div');
        studentMsg.className = 'tutoring-message student';
        studentMsg.innerHTML = `
            <div class="message-content">
                <strong>학생:</strong> ${studentResponse}
            </div>
        `;
        container.appendChild(studentMsg);
        container.scrollTop = container.scrollHeight;

        // 입력 영역 제거
        const inputArea = container.querySelector('.tutoring-input-area');
        if (inputArea) inputArea.remove();

        // 로딩 표시
        const loadingMsg = document.createElement('div');
        loadingMsg.className = 'tutoring-message teacher';
        loadingMsg.innerHTML = `
            <div class="message-content">
                <strong>AI 튜터:</strong> <em>생각 중...</em>
            </div>
        `;
        container.appendChild(loadingMsg);
        container.scrollTop = container.scrollHeight;

        try {
            // 룰과 온톨로지 기반 상호작용 처리
            const interactionResult = await processRuleBasedInteraction(
                studentResponse,
                contextData
            );

            // 로딩 메시지 제거
            loadingMsg.remove();

            // 상호작용 결과 표시
            displayInteractionResult(interactionResult, container);

            // 다음 단계 진행
            if (interactionResult.next_steps && interactionResult.next_steps.length > 0) {
                setTimeout(() => {
                    continueTutoringWithSteps(interactionResult, contextData, container);
                }, 2000);
            } else {
                setTimeout(() => {
                    continueTutoring(contextData, container);
                }, 2000);
            }

        } catch (error) {
            console.error('상호작용 처리 오류:', error);
            loadingMsg.remove();
            const errorMsg = document.createElement('div');
            errorMsg.className = 'tutoring-message teacher';
            errorMsg.innerHTML = `
                <div class="message-content" style="color: red;">
                    <strong>AI 튜터:</strong> 죄송합니다. 오류가 발생했습니다. 다시 시도해주세요.
                </div>
            `;
            container.appendChild(errorMsg);
        }
    };

    /**
     * 룰과 온톨로지 기반 상호작용 처리
     */
    async function processRuleBasedInteraction(userInput, contextData) {
        const response = await fetch('api/interact.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                user_input: userInput,
                rules: contextData.teaching_rules || [],
                ontology: contextData.ontology || {},
                context: contextData.dialogue_analysis || {},
                interaction_id: window.currentInteractionId || null
            })
        });

        if (!response.ok) {
            throw new Error('상호작용 처리 실패');
        }

        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error || '상호작용 처리 실패');
        }

        return result.data;
    }

    /**
     * 상호작용 결과 표시
     */
    function displayInteractionResult(result, container) {
        // 메인 응답 표시
        const responseMsg = document.createElement('div');
        responseMsg.className = 'tutoring-message teacher';
        responseMsg.innerHTML = `
            <div class="message-content">
                <strong>AI 튜터:</strong> ${result.response.text}
            </div>
        `;
        container.appendChild(responseMsg);

        // 제안사항 표시
        if (result.response.suggestions && result.response.suggestions.length > 0) {
            result.response.suggestions.forEach(suggestion => {
                const suggestionMsg = document.createElement('div');
                suggestionMsg.className = 'tutoring-message teacher suggestion';
                suggestionMsg.innerHTML = `
                    <div class="message-content">
                        💡 ${suggestion}
                    </div>
                `;
                container.appendChild(suggestionMsg);
            });
        }

        // 질문 표시
        if (result.response.questions && result.response.questions.length > 0) {
            result.response.questions.forEach(question => {
                const questionMsg = document.createElement('div');
                questionMsg.className = 'tutoring-message teacher question';
                questionMsg.innerHTML = `
                    <div class="message-content">
                        ❓ ${question}
                    </div>
                `;
                container.appendChild(questionMsg);
            });
        }

        // 관련 컨텐츠 표시
        if (result.related_contents && result.related_contents.length > 0) {
            const contentsMsg = document.createElement('div');
            contentsMsg.className = 'tutoring-message teacher contents';
            contentsMsg.innerHTML = `
                <div class="message-content">
                    <strong>📚 관련 학습 자료:</strong>
                    <ul>
                        ${result.related_contents.map(content => 
                            `<li>${content.title || content.rule_id || '학습 자료'}</li>`
                        ).join('')}
                    </ul>
                </div>
            `;
            container.appendChild(contentsMsg);
        }

        container.scrollTop = container.scrollHeight;
    }

    /**
     * 다음 단계와 함께 튜터링 계속
     */
    function continueTutoringWithSteps(interactionResult, contextData, container) {
        if (interactionResult.next_steps && interactionResult.next_steps.length > 0) {
            const nextStep = interactionResult.next_steps[0];
            
            const stepMsg = document.createElement('div');
            stepMsg.className = 'tutoring-message teacher next-step';
            stepMsg.innerHTML = `
                <div class="message-content">
                    <strong>다음 단계:</strong> ${nextStep.content || nextStep}
                </div>
            `;
            container.appendChild(stepMsg);
            container.scrollTop = container.scrollHeight;
        }
        
        // 다음 질문이 있으면 계속
        if (contextData.detailed_questions && contextData.detailed_questions.length > 0) {
            const nextQuestion = contextData.detailed_questions.shift();
            setTimeout(() => {
                askQuestion(nextQuestion, container, contextData);
            }, 2000);
        }
    }

    /**
     * OpenAI API를 통한 튜터링 피드백 생성
     */
    async function generateTutoringFeedback(question, studentResponse, contextData) {
        const messages = [
            {
                role: 'system',
                content: `당신은 수학 교육 전문가입니다. 학생의 답변에 대해 친절하고 구체적인 피드백을 제공하세요.
- 학생의 답변이 맞으면 격려하고 다음 단계를 안내하세요.
- 학생의 답변이 틀리거나 부족하면 힌트를 주고 올바른 방향으로 이끌어주세요.
- 항상 긍정적이고 격려하는 톤을 유지하세요.
- 수학 개념을 명확하게 설명하세요.`
            },
            {
                role: 'user',
                content: `질문: ${question.question}\n\n학생 답변: ${studentResponse}\n\n컨텍스트: ${JSON.stringify(contextData.dialogue_analysis, null, 2)}\n\n위 질문에 대한 학생의 답변을 평가하고 피드백을 제공하세요.`
            }
        ];

        const response = await fetch('https://api.openai.com/v1/chat/completions', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${apikey}`
            },
            body: JSON.stringify({
                model: 'gpt-4o',
                messages: messages,
                temperature: 0.7,
                max_tokens: 500
            })
        });

        if (!response.ok) {
            throw new Error('OpenAI API 호출 실패');
        }

        const data = await response.json();
        return data.choices[0].message.content;
    }

    /**
     * 튜터링 계속하기
     */
    function continueTutoring(contextData, container) {
        // 다음 세부 질문이 있으면 계속
        if (contextData.detailed_questions && contextData.detailed_questions.length > 0) {
            const nextQuestion = contextData.detailed_questions.shift();
            setTimeout(() => {
                askQuestion(nextQuestion, container, contextData);
            }, 1000);
        } else {
            // 튜터링 종료
            const endMsg = document.createElement('div');
            endMsg.className = 'tutoring-message teacher';
            endMsg.innerHTML = `
                <div class="message-content">
                    <strong>AI 튜터:</strong> 오늘 학습을 잘 마쳤습니다! 복습을 열심히 하고 다음에 만나요! 🎉
                </div>
            `;
            container.appendChild(endMsg);
            container.scrollTop = container.scrollHeight;
        }
    }
})();

