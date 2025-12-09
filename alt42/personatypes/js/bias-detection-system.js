/**
 * 🌌 실시간 편향 감지 시스템
 * 선생님 메모, 학생 로그, 실시간 입력 분석을 통한 인지편향 감지
 */

class BiasDetectionSystem {
    constructor() {
        this.userId = null;
        this.currentSession = null;
        this.biasPatterns = this.initializeBiasPatterns();
        this.teacherMemos = new Map();
        this.studentLogs = [];
        this.realTimeData = {
            typingSpeed: [],
            pausePatterns: [],
            correctionCount: 0,
            emotionalMarkers: []
        };
        
        this.init();
    }

    /**
     * 🧠 60개 편향 패턴 초기화
     */
    initializeBiasPatterns() {
        return {
            // Level 1: 기초 인식편향 (20개)
            level1: {
                확증편향: {
                    keywords: ['역시', '당연히', '또', '늘 그래', '맞다고 했지'],
                    patterns: ['selective_attention', 'confirmation_seeking'],
                    severity: 'medium',
                    cosmicMetaphor: '확증편향 블랙홀이 다른 별빛을 삼키고 있어요'
                },
                선택적주의: {
                    keywords: ['못봤어', '안들려', '그것만', '다른건'],
                    patterns: ['tunnel_vision', 'attention_bias'],
                    severity: 'medium',
                    cosmicMetaphor: '시야를 가리는 성운을 걷어내야 해요'
                },
                자기과소평가: {
                    keywords: ['못해', '바보', '안돼', '불가능', '포기'],
                    patterns: ['self_deprecation', 'learned_helplessness'],
                    severity: 'high',
                    cosmicMetaphor: '당신의 별빛이 먹구름에 가려져 있어요'
                },
                고정마인드셋: {
                    keywords: ['타고난', '원래', '못하는', '안변해', '그런거야'],
                    patterns: ['fixed_mindset', 'static_thinking'],
                    severity: 'high',
                    cosmicMetaphor: '성장하는 별이 되기를 거부하고 있어요'
                },
                재앙화사고: {
                    keywords: ['끝났다', '망했다', '최악', '아무것도', '절대'],
                    patterns: ['catastrophizing', 'worst_case_thinking'],
                    severity: 'high',
                    cosmicMetaphor: '작은 운석을 행성 충돌로 보고 있어요'
                },
                흑백사고: {
                    keywords: ['완전히', '전혀', '항상', '절대', '무조건'],
                    patterns: ['all_or_nothing', 'binary_thinking'],
                    severity: 'medium',
                    cosmicMetaphor: '우주의 무한한 색깔을 흑백으로만 보고 있어요'
                }
            },
            
            // Level 2: 학습과정편향 (25개)
            level2: {
                앵커링: {
                    keywords: ['처음에', '첫번째', '그대로', '고정'],
                    patterns: ['first_impression_stuck', 'initial_anchor'],
                    severity: 'medium',
                    cosmicMetaphor: '첫 번째 별에만 정박하고 항해를 멈췄어요'
                },
                가용성휴리스틱: {
                    keywords: ['최근에', '기억나는', '쉬운', '익숙한'],
                    patterns: ['availability_bias', 'recent_memory'],
                    severity: 'low',
                    cosmicMetaphor: '가장 밝은 별만 보고 멀리 있는 은하를 놓치고 있어요'
                },
                회상편향: {
                    keywords: ['그때는', '예전에', '원래', '항상'],
                    patterns: ['hindsight_bias', 'memory_distortion'],
                    severity: 'medium',
                    cosmicMetaphor: '과거의 별빛으로 현재를 해석하고 있어요'
                }
            },
            
            // Level 3: 고차원편향 (15개)
            level3: {
                던닝크루거효과: {
                    keywords: ['쉬워', '당연히', '다 알아', '간단해'],
                    patterns: ['overconfidence', 'competence_illusion'],
                    severity: 'medium',
                    cosmicMetaphor: '작은 행성에서 전 우주를 본다고 착각하고 있어요'
                },
                편향맹점: {
                    keywords: ['나는 객관적', '편견없이', '공정하게'],
                    patterns: ['bias_blind_spot', 'objectivity_illusion'],
                    severity: 'high',
                    cosmicMetaphor: '자신의 중력장을 느끼지 못하는 별처럼'
                }
            }
        };
    }

    /**
     * 🚀 시스템 초기화
     */
    init() {
        this.setupEventListeners();
        this.startRealTimeMonitoring();
        this.loadExistingData();
    }

    /**
     * 📝 선생님 메모 입력 인터페이스 생성
     */
    createTeacherMemoInterface() {
        const memoInterface = document.createElement('div');
        memoInterface.id = 'teacher-memo-interface';
        memoInterface.className = 'teacher-memo-interface';
        memoInterface.innerHTML = `
            <div class="memo-header">
                <h3>🧑‍🏫 선생님 관찰 메모</h3>
                <button class="toggle-memo" onclick="toggleMemoInterface()">📝</button>
            </div>
            <div class="memo-content" id="memoContent">
                <div class="student-selector">
                    <label>학생 선택:</label>
                    <select id="studentSelect">
                        <option value="">학생을 선택하세요</option>
                        <!-- 동적으로 학생 목록 로드 -->
                    </select>
                </div>
                
                <div class="bias-quick-check">
                    <h4>🔍 관찰된 편향 패턴</h4>
                    <div class="bias-checkboxes">
                        <label><input type="checkbox" value="확증편향"> 확증편향 (같은 방법만 고집)</label>
                        <label><input type="checkbox" value="재앙화사고"> 재앙화사고 (작은 실수를 크게 봄)</label>
                        <label><input type="checkbox" value="자기과소평가"> 자기과소평가 (능력 의심)</label>
                        <label><input type="checkbox" value="완벽주의"> 완벽주의 (완벽하지 않으면 불안)</label>
                        <label><input type="checkbox" value="흑백사고"> 흑백사고 (중간단계 인정 안함)</label>
                        <label><input type="checkbox" value="회피행동"> 회피행동 (어려우면 포기)</label>
                    </div>
                </div>
                
                <div class="emotional-state">
                    <h4>😊 감정 상태</h4>
                    <div class="emotion-buttons">
                        <button class="emotion-btn" data-emotion="confident">자신감 ⭐</button>
                        <button class="emotion-btn" data-emotion="anxious">불안 😰</button>
                        <button class="emotion-btn" data-emotion="frustrated">좌절 😤</button>
                        <button class="emotion-btn" data-emotion="curious">호기심 🤔</button>
                        <button class="emotion-btn" data-emotion="proud">뿌듯함 🎉</button>
                    </div>
                </div>
                
                <div class="detailed-observation">
                    <h4>📋 상세 관찰 내용</h4>
                    <textarea id="detailedMemo" placeholder="구체적인 행동, 말, 반응을 기록해주세요...
예시: 
- 문제를 보자마자 '또 어려운 거네' 라고 중얼거림
- 첫 번째 방법이 막히자 다른 시도 없이 바로 포기
- 친구가 다른 방법 제안해도 '그건 안 될 거야' 라고 거부"></textarea>
                </div>
                
                <div class="context-info">
                    <h4>📍 상황 맥락</h4>
                    <div class="context-inputs">
                        <label>수업 단계: 
                            <select id="lessonPhase">
                                <option>도입</option>
                                <option>전개</option>
                                <option>정리</option>
                                <option>개별지도</option>
                            </select>
                        </label>
                        <label>문제 난이도: 
                            <select id="difficulty">
                                <option>쉬움</option>
                                <option>보통</option>
                                <option>어려움</option>
                                <option>매우 어려움</option>
                            </select>
                        </label>
                        <label>협업 상황: 
                            <select id="collaboration">
                                <option>개별 작업</option>
                                <option>짝 활동</option>
                                <option>모둠 활동</option>
                                <option>전체 토론</option>
                            </select>
                        </label>
                    </div>
                </div>
                
                <div class="memo-actions">
                    <button class="save-memo-btn" onclick="saveTeacherMemo()">💾 관찰 기록 저장</button>
                    <button class="urgent-alert-btn" onclick="createUrgentAlert()">🚨 즉시 개입 요청</button>
                </div>
            </div>
        `;
        
        // 페이지에 추가 (고정 위치)
        document.body.appendChild(memoInterface);
        this.loadStudentList();
    }

    /**
     * 📊 학생 로그 수집 시스템
     */
    startStudentLogging(userId) {
        this.userId = userId;
        this.currentSession = {
            sessionId: Date.now(),
            startTime: new Date(),
            actions: [],
            biasDetections: [],
            emotionalJourney: []
        };

        // 모든 사용자 행동 로깅
        this.logUserActions();
        this.logTypingPatterns();
        this.logEmotionalMarkers();
    }

    /**
     * 🎯 실시간 사용자 행동 로깅
     */
    logUserActions() {
        // 문제 선택 로깅
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('journey-node')) {
                this.addToLog('node_selection', {
                    nodeId: e.target.id,
                    timestamp: Date.now(),
                    hesitationTime: this.measureHesitation()
                });
            }
        });

        // 답안 입력 패턴 로깅
        const answerInput = document.getElementById('answerInput');
        if (answerInput) {
            answerInput.addEventListener('input', (e) => {
                this.analyzeTypingPattern(e);
                this.detectRealTimeBias(e.target.value);
            });

            answerInput.addEventListener('paste', () => {
                this.addToLog('paste_action', {
                    timestamp: Date.now(),
                    behavior: 'copy_paste_reliance'
                });
            });
        }

        // 패널 닫기 행동 (회피 패턴)
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('close-panel')) {
                this.addToLog('avoidance_behavior', {
                    timestamp: Date.now(),
                    timeSpent: this.calculateTimeSpent(),
                    inputLength: this.getInputLength()
                });
            }
        });
    }

    /**
     * ⌨️ 타이핑 패턴 분석 (편향 감지 단서)
     */
    analyzeTypingPattern(event) {
        const now = Date.now();
        const text = event.target.value;
        
        // 타이핑 속도 계산
        if (this.lastKeystroke) {
            const interval = now - this.lastKeystroke;
            this.realTimeData.typingSpeed.push(interval);
        }
        this.lastKeystroke = now;

        // 긴 멈춤 감지 (고민 또는 회피 신호)
        if (this.realTimeData.typingSpeed.length > 0) {
            const lastInterval = this.realTimeData.typingSpeed[this.realTimeData.typingSpeed.length - 1];
            if (lastInterval > 3000) { // 3초 이상 멈춤
                this.addToLog('long_pause', {
                    timestamp: now,
                    pauseDuration: lastInterval,
                    currentText: text,
                    possibleBias: this.inferBiasFromPause(text, lastInterval)
                });
            }
        }

        // 수정 횟수 추적 (완벽주의 또는 불안 신호)
        const corrections = this.detectCorrections(text);
        if (corrections > this.realTimeData.correctionCount) {
            this.realTimeData.correctionCount = corrections;
            this.addToLog('excessive_correction', {
                timestamp: now,
                correctionCount: corrections,
                possibleBias: ['완벽주의', '자기의심']
            });
        }
    }

    /**
     * 🔍 실시간 편향 감지
     */
    detectRealTimeBias(inputText) {
        const detectedBiases = [];
        
        // 각 편향 레벨별 분석
        Object.entries(this.biasPatterns).forEach(([level, biases]) => {
            Object.entries(biases).forEach(([biasName, biasData]) => {
                const biasScore = this.calculateBiasScore(inputText, biasData);
                if (biasScore > 0.6) { // 임계값 이상
                    detectedBiases.push({
                        name: biasName,
                        level: level,
                        score: biasScore,
                        evidence: this.extractEvidence(inputText, biasData.keywords),
                        cosmicMetaphor: biasData.cosmicMetaphor
                    });
                }
            });
        });

        // 즉시 개입이 필요한 편향 감지
        const urgentBiases = detectedBiases.filter(bias => 
            bias.score > 0.8 || ['재앙화사고', '자기과소평가'].includes(bias.name)
        );

        if (urgentBiases.length > 0) {
            this.triggerUrgentIntervention(urgentBiases);
        }

        // 로그에 기록
        if (detectedBiases.length > 0) {
            this.addToLog('bias_detection', {
                timestamp: Date.now(),
                detectedBiases: detectedBiases,
                inputText: inputText,
                confidence: Math.max(...detectedBiases.map(b => b.score))
            });
        }

        return detectedBiases;
    }

    /**
     * 📊 편향 점수 계산
     */
    calculateBiasScore(text, biasData) {
        let score = 0;
        let matchCount = 0;

        // 키워드 매칭
        biasData.keywords.forEach(keyword => {
            if (text.includes(keyword)) {
                matchCount++;
                score += 0.2;
            }
        });

        // 패턴 매칭 (더 정교한 분석)
        if (biasData.patterns) {
            biasData.patterns.forEach(pattern => {
                if (this.detectPattern(text, pattern)) {
                    score += 0.3;
                }
            });
        }

        // 문맥적 강화 (이전 로그와 연결)
        score = this.applyContextualAmplification(score, biasData, text);

        return Math.min(score, 1.0); // 최대 1.0
    }

    /**
     * 🚨 긴급 개입 시스템
     */
    triggerUrgentIntervention(urgentBiases) {
        // 우주적 개입 메시지 생성
        const interventionMessage = this.generateCosmicIntervention(urgentBiases);
        
        // 실시간 팝업 또는 아바타 메시지
        this.displayUrgentIntervention(interventionMessage);
        
        // 선생님에게 알림
        this.notifyTeacher(urgentBiases);
        
        // 로그 기록
        this.addToLog('urgent_intervention', {
            timestamp: Date.now(),
            biases: urgentBiases,
            intervention: interventionMessage
        });
    }

    /**
     * 🌌 우주적 개입 메시지 생성
     */
    generateCosmicIntervention(biases) {
        const primaryBias = biases[0];
        const messages = {
            재앙화사고: {
                detection: "⚠️ 재앙화사고 소행성이 감지되었습니다!",
                guidance: "작은 실수를 거대한 재앙으로 보고 계시는군요. 잠깐, 이 우주에서 실수는 새로운 별이 탄생하는 과정입니다. 🌟",
                action: "3번의 깊은 숨을 쉬고, 이 문제를 다른 각도에서 바라보세요."
            },
            자기과소평가: {
                detection: "🌑 자기과소평가 블랙홀이 당신의 빛을 삼키고 있어요!",
                guidance: "당신 안의 별빛이 얼마나 밝은지 모르고 계시는군요. 이미 여기까지 온 것만으로도 대단한 여행자입니다.",
                action: "지금까지 해결한 문제들을 떠올려보세요. 그것들이 당신의 별자리입니다. ⭐"
            },
            확증편향: {
                detection: "🕳️ 확증편향의 중력장에 갇혀계시네요!",
                guidance: "한 가지 방법만 보이는 것은 시야가 좁아진 것입니다. 우주에는 무수한 길이 있어요.",
                action: "다른 행성에서는 이 문제를 어떻게 풀까요? 새로운 관점을 탐험해보세요! 🚀"
            }
        };

        return messages[primaryBias.name] || {
            detection: "🌌 편향 패턴이 감지되었습니다",
            guidance: "잠시 멈춰서 다른 가능성을 생각해보세요",
            action: "우주는 무한한 가능성으로 가득합니다 ✨"
        };
    }

    /**
     * 💾 로그 저장 시스템
     */
    addToLog(eventType, data) {
        const logEntry = {
            eventType: eventType,
            timestamp: Date.now(),
            sessionId: this.currentSession.sessionId,
            userId: this.userId,
            data: data
        };

        this.studentLogs.push(logEntry);
        this.currentSession.actions.push(logEntry);

        // 실시간으로 서버에 전송 (나중에 DB 연동)
        this.sendLogToServer(logEntry);
    }

    /**
     * 🔄 DB 연동 준비 - 서버 전송
     */
    async sendLogToServer(logEntry) {
        // TODO: DB 연동 시 실제 API 호출
        console.log('🔄 서버 전송 준비:', logEntry);
        
        // 임시 로컬스토리지 저장
        const existingLogs = JSON.parse(localStorage.getItem('studentLogs') || '[]');
        existingLogs.push(logEntry);
        localStorage.setItem('studentLogs', JSON.stringify(existingLogs));
    }

    /**
     * 📋 선생님 메모 저장
     */
    async saveTeacherMemo() {
        const studentId = document.getElementById('studentSelect').value;
        const selectedBiases = Array.from(document.querySelectorAll('.bias-checkboxes input:checked'))
            .map(cb => cb.value);
        const emotion = document.querySelector('.emotion-btn.selected')?.dataset.emotion;
        const detailedMemo = document.getElementById('detailedMemo').value;
        const context = {
            lessonPhase: document.getElementById('lessonPhase').value,
            difficulty: document.getElementById('difficulty').value,
            collaboration: document.getElementById('collaboration').value
        };

        const memo = {
            teacherId: 'current_teacher_id', // TODO: 실제 교사 ID
            studentId: studentId,
            timestamp: Date.now(),
            observedBiases: selectedBiases,
            emotionalState: emotion,
            detailedObservation: detailedMemo,
            context: context,
            urgency: selectedBiases.some(bias => ['재앙화사고', '자기과소평가'].includes(bias)) ? 'high' : 'normal'
        };

        // 메모리에 저장
        this.teacherMemos.set(`${studentId}_${Date.now()}`, memo);

        // TODO: DB 저장
        console.log('👩‍🏫 선생님 메모 저장:', memo);
        
        // 성공 알림
        this.showSuccessMessage('관찰 기록이 저장되었습니다! 🌟');
        
        // 폼 초기화
        this.resetMemoForm();
    }

    /**
     * 🔄 기존 데이터 로드
     */
    loadExistingData() {
        // 로컬스토리지에서 기존 로그 불러오기
        const existingLogs = JSON.parse(localStorage.getItem('studentLogs') || '[]');
        this.studentLogs = existingLogs;

        // TODO: DB에서 선생님 메모 불러오기
        console.log('📊 기존 데이터 로드 완료');
    }

    /**
     * 🎨 UI 헬퍼 메소드들
     */
    displayUrgentIntervention(message) {
        // 기존 아바타 메시지 업데이트
        const avatarSpeech = document.getElementById('avatarSpeech');
        if (avatarSpeech) {
            avatarSpeech.innerHTML = `
                <div class="urgent-intervention">
                    <div class="detection-alert">${message.detection}</div>
                    <div class="cosmic-guidance">${message.guidance}</div>
                    <div class="action-suggestion">${message.action}</div>
                </div>
            `;
            avatarSpeech.classList.add('urgent-glow');
            
            // 3초 후 일반 모드로 복귀
            setTimeout(() => {
                avatarSpeech.classList.remove('urgent-glow');
            }, 3000);
        }
    }

    showSuccessMessage(message) {
        // 성공 메시지 표시
        const successDiv = document.createElement('div');
        successDiv.className = 'success-message';
        successDiv.textContent = message;
        document.body.appendChild(successDiv);
        
        setTimeout(() => {
            successDiv.remove();
        }, 3000);
    }

    resetMemoForm() {
        document.getElementById('detailedMemo').value = '';
        document.querySelectorAll('.bias-checkboxes input').forEach(cb => cb.checked = false);
        document.querySelectorAll('.emotion-btn').forEach(btn => btn.classList.remove('selected'));
    }

    loadStudentList() {
        // TODO: 실제 학생 목록 로드
        const studentSelect = document.getElementById('studentSelect');
        const mockStudents = [
            {id: 1, name: '김민수'},
            {id: 2, name: '이지은'},
            {id: 3, name: '박철수'},
            {id: 4, name: '최영희'}
        ];
        
        mockStudents.forEach(student => {
            const option = document.createElement('option');
            option.value = student.id;
            option.textContent = student.name;
            studentSelect.appendChild(option);
        });
    }
}

// 전역 함수들
function toggleMemoInterface() {
    const memoContent = document.getElementById('memoContent');
    memoContent.style.display = memoContent.style.display === 'none' ? 'block' : 'none';
}

function saveTeacherMemo() {
    if (window.biasDetectionSystem) {
        window.biasDetectionSystem.saveTeacherMemo();
    }
}

function createUrgentAlert() {
    const studentId = document.getElementById('studentSelect').value;
    if (!studentId) {
        alert('먼저 학생을 선택해주세요.');
        return;
    }
    
    // 긴급 알림 생성
    const alertMessage = `${studentId}번 학생에게 즉시 개입이 필요합니다!`;
    console.log('🚨 긴급 알림:', alertMessage);
    
    // TODO: 실시간 알림 시스템 구현
    alert('긴급 개입 요청이 전송되었습니다! 🚨');
}

// 감정 버튼 선택
document.addEventListener('click', (e) => {
    if (e.target.classList.contains('emotion-btn')) {
        document.querySelectorAll('.emotion-btn').forEach(btn => btn.classList.remove('selected'));
        e.target.classList.add('selected');
    }
});

// 전역 인스턴스 생성
window.biasDetectionSystem = new BiasDetectionSystem();