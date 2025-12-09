/**
 * 수학 학습 패턴 시스템 (60personas.txt 기반)
 * DB 연동 버전 - 음성 파일 통합
 */

class MathPersonaSystem {
    constructor() {
        this.isOpen = false;
        this.currentGroup = 'all';
        this.currentView = 'grid';
        this.selectedPatternId = null;
        this.patterns = [];
        this.categories = [];
        this.userProgress = {};
        this.currentAudio = null;
        
        // 사용자 정보
        this.userId = document.querySelector('meta[name="user-id"]')?.content || '1';
        this.userRole = document.querySelector('meta[name="user-role"]')?.content || 'student';
        
        // 아이콘 매핑 (데이터베이스의 텍스트를 이모지로 변환)
        this.iconMap = {
            'brain': '🧠',
            'anxious': '😰',
            'error': '❌',
            'target': '🎯',
            'book': '📚',
            'clock': '⏰',
            'check': '✔️',
            'tool': '🔧'
        };
        
        this.init();
    }

    /**
     * 초기화
     */
    async init() {
        await this.loadDataFromDB();
        this.createLibraryInterface();
        this.bindEventListeners();
    }
    
    /**
     * 아이콘 텍스트를 이모지로 변환
     */
    getIcon(iconText) {
        // 이미 이모지인 경우 그대로 반환
        if (iconText && iconText.length <= 2) {
            return iconText;
        }
        // 텍스트인 경우 매핑에서 찾아 반환
        return this.iconMap[iconText] || iconText || '📚';
    }

    /**
     * DB에서 데이터 로드
     */
    async loadDataFromDB() {
        try {
            console.log('API 호출 시작, user_id:', this.userId);
            
            // 절대 경로로 변경
            const apiUrl = '/moodle/local/augmented_teacher/alt42/shiningstars/api/get_math_patterns.php';
            console.log('API URL:', apiUrl);
            
            const response = await fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_id: this.userId
                })
            });

            console.log('API 응답 상태:', response.status, response.ok);
            
            // 응답 텍스트 먼저 확인
            const responseText = await response.text();
            console.log('API 원본 응답:', responseText);

            if (!response.ok) {
                throw new Error(`데이터 로드 실패: ${response.status} ${response.statusText}`);
            }

            const data = JSON.parse(responseText);
            console.log('API 파싱된 데이터:', data);
            
            this.patterns = data.patterns || [];
            this.categories = data.categories || [];
            this.userProgress = data.progress || {};
            
            // 카테고리의 이모지도 변환
            this.categories = this.categories.map(cat => ({
                ...cat,
                emoji: this.getIcon(cat.emoji)
            }));

            console.log('패턴 데이터 로드 완료:', {
                patterns: this.patterns.length,
                categories: this.categories.length,
                progress: Object.keys(this.userProgress).length
            });
            
            // 데이터가 로드된 후 패턴 렌더링
            if (this.patterns.length > 0) {
                console.log('패턴 렌더링 시작...');
                this.renderPatterns();
                this.updateProgressDisplay();
            } else {
                console.warn('패턴 데이터가 비어있습니다. 기본 데이터를 사용합니다.');
                this.loadDefaultData();
            }
        } catch (error) {
            console.error('데이터 로드 오류:', error);
            // 오류 시 기본 데이터 사용
            this.loadDefaultData();
        }
    }

    /**
     * 기본 데이터 로드 (백업용)
     */
    loadDefaultData() {
        console.log('기본 데이터를 로드합니다...');
        
        this.categories = [
            { id: 1, code: 'cognitive_overload', name: '인지 과부하', order: 1, color: '#667eea', emoji: '🧠' },
            { id: 2, code: 'confidence_distortion', name: '자신감 왜곡', order: 2, color: '#764ba2', emoji: '😰' },
            { id: 3, code: 'mistake_patterns', name: '실수 패턴', order: 3, color: '#f59e0b', emoji: '❌' },
            { id: 4, code: 'approach_errors', name: '접근 전략 오류', order: 4, color: '#ef4444', emoji: '🎯' },
            { id: 5, code: 'study_habits', name: '학습 습관', order: 5, color: '#10b981', emoji: '📚' },
            { id: 6, code: 'time_pressure', name: '시간/압박 관리', order: 6, color: '#3b82f6', emoji: '⏰' },
            { id: 7, code: 'verification_absence', name: '검증/확인 부재', order: 7, color: '#8b5cf6', emoji: '✔️' },
            { id: 8, code: 'other_obstacles', name: '기타 장애', order: 8, color: '#6b7280', emoji: '🔧' }
        ];
        
        // 샘플 패턴 데이터 추가
        this.patterns = [
            {
                pattern_id: 1,
                pattern_name: "정보 폭주로 인한 혼란",
                pattern_desc: "한 번에 너무 많은 개념을 배우려고 할 때 발생하는 인지적 과부하",
                category_id: 1,
                category_name: "인지 과부하",
                category_code: "cognitive_overload",
                icon: "🧠",
                priority: "high",
                audio_time: "2:30",
                action: "복잡한 문제를 작은 단위로 나누어 단계별로 해결하세요.",
                check_method: "각 단계별로 이해도를 확인하고 다음 단계로 진행하세요.",
                audio_script: "정보가 너무 많아 혼란스러울 때는 잠시 멈추고 하나씩 정리해보세요.",
                teacher_dialog: "학생이 한 번에 모든 것을 이해하려고 하지 않도록 단계별로 가르쳐주세요."
            },
            {
                pattern_id: 2,
                pattern_name: "수학 공포증",
                pattern_desc: "수학 문제를 보면 자동으로 '못한다'고 생각하는 부정적 자아상",
                category_id: 2,
                category_name: "자신감 왜곡",
                category_code: "confidence_distortion",
                icon: "😰",
                priority: "high",
                audio_time: "3:00",
                action: "작은 성공 경험을 쌓아가며 점진적으로 자신감을 회복하세요.",
                check_method: "매일 해결한 문제를 기록하고 진전을 확인하세요.",
                audio_script: "수학을 두려워하지 마세요. 모든 사람은 자신만의 속도로 배울 수 있습니다.",
                teacher_dialog: "학생의 작은 성취도 칭찬하고 격려해주세요."
            }
        ];
        
        // 렌더링
        this.renderPatterns();
    }

    /**
     * 도감 인터페이스 생성
     */
    createLibraryInterface() {
        const libraryHTML = `
            <div id="math-persona-library" class="math-persona-library-overlay">
                <div class="math-persona-library-container">
                    <!-- 헤더 -->
                    <div class="library-header">
                        <div>
                            <h2>📚 수학 인지관성 도감</h2>
                            <p class="library-subtitle">60개의 수학 학습 패턴을 정복하세요</p>
                        </div>
                        <button class="close-library-btn" onclick="window.mathPersonaSystem?.close()">×</button>
                    </div>

                    <!-- 진행 상황 -->
                    <div class="progress-section">
                        <div class="progress-stats">
                            <div class="stat-item">
                                <span class="stat-label">수집한 패턴</span>
                                <span class="stat-value">${this.getCollectedCount()}/60</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">완성도</span>
                                <span class="stat-value">${Math.floor(this.getCollectedCount() / 60 * 100)}%</span>
                            </div>
                        </div>
                        <div class="progress-bar-container">
                            <div class="progress-bar-fill" style="width: ${this.getCollectedCount() / 60 * 100}%"></div>
                        </div>
                    </div>

                    <!-- 카테고리 탭 -->
                    <div class="library-tabs">
                        <button class="tab-btn active" data-category="all" onclick="window.mathPersonaSystem?.selectCategory('all')">
                            전체 (60)
                        </button>
                        ${this.categories.map(cat => `
                            <button class="tab-btn" data-category="${cat.code}" 
                                    onclick="window.mathPersonaSystem?.selectCategory('${cat.code}')">
                                ${cat.emoji || ''} ${cat.name} (${this.getPatternsByCategory(cat.code).length})
                            </button>
                        `).join('')}
                    </div>

                    <!-- 카드 그리드 -->
                    <div class="library-content">
                        <div id="patterns-grid" class="math-persona-cards-grid">
                            <!-- 패턴 카드들이 여기에 렌더링됨 -->
                        </div>
                    </div>

                    <!-- 푸터 -->
                    <div class="library-footer">
                        <div class="collection-progress">
                            <p>학습 진행도: ${this.getOverallProgress()}%</p>
                        </div>
                    </div>
                </div>
            </div>
        `;

        if (!document.getElementById('math-persona-library')) {
            document.body.insertAdjacentHTML('beforeend', libraryHTML);
        }

        this.renderPatterns();
    }

    /**
     * 패턴 카드 렌더링
     */
    renderPatterns() {
        console.log('renderPatterns 호출됨');
        console.log('전체 패턴 데이터:', this.patterns);
        console.log('현재 그룹:', this.currentGroup);
        console.log('카테고리:', this.categories);
        
        const container = document.getElementById('patterns-grid');
        if (!container) {
            console.error('patterns-grid 컨테이너를 찾을 수 없습니다.');
            return;
        }

        let patterns = this.currentGroup === 'all' 
            ? this.patterns 
            : this.getPatternsByCategory(this.currentGroup);

        console.log('필터링된 패턴 수:', patterns.length);
        console.log('필터링된 패턴:', patterns);

        if (patterns.length === 0) {
            container.innerHTML = '<div style="text-align: center; color: #999; padding: 40px;">패턴 데이터가 없습니다.<br>데이터를 로드하는 중 문제가 발생했을 수 있습니다.</div>';
            return;
        }

        container.innerHTML = patterns.map(pattern => this.renderPatternCard(pattern)).join('');
    }

    /**
     * 진행 상황 표시 업데이트
     */
    updateProgressDisplay() {
        const collectedCount = this.getCollectedCount();
        const progressPercent = Math.floor(collectedCount / 60 * 100);
        
        // 수집한 패턴 수 업데이트
        const statValue = document.querySelector('.stat-value');
        if (statValue) {
            statValue.textContent = `${collectedCount}/60`;
        }
        
        // 완성도 업데이트
        const completionStat = document.querySelectorAll('.stat-value')[1];
        if (completionStat) {
            completionStat.textContent = `${progressPercent}%`;
        }
        
        // 진행바 업데이트
        const progressBar = document.querySelector('.progress-bar-fill');
        if (progressBar) {
            progressBar.style.width = `${progressPercent}%`;
        }
    }

    /**
     * 개별 패턴 카드 렌더링
     */
    renderPatternCard(pattern) {
        const progress = this.userProgress[pattern.pattern_id] || {};
        const isCollected = progress.is_collected || false;
        const masteryLevel = progress.mastery_level || 0;
        const category = this.categories.find(c => c.code === pattern.category_code) || {};
        const priorityColor = {
            'high': '#ef4444',
            'medium': '#f59e0b',
            'low': '#10b981'
        };

        return `
            <div class="math-persona-card ${isCollected ? 'collected' : 'locked'}" 
                 data-pattern-id="${pattern.pattern_id}"
                 onclick="window.mathPersonaSystem?.showPatternDetail(${pattern.pattern_id})"
                 style="--group-color: ${priorityColor[pattern.priority] || category.color || '#667eea'}">
                <div class="card-number">#${String(pattern.pattern_id).padStart(2, '0')}</div>
                
                ${isCollected ? `
                    <div class="card-content">
                        <div class="card-name">${pattern.pattern_name}</div>
                        <div class="card-group">${pattern.category_name}</div>
                        ${masteryLevel > 0 ? `
                            <div class="mastery-indicator">
                                숙달도: ${masteryLevel}%
                            </div>
                        ` : ''}
                    </div>
                    <div class="card-math-icon">${this.getIcon(pattern.icon)}</div>
                ` : `
                    <div class="lock-icon">🔒</div>
                    <div class="card-content">
                        <div class="card-name">???</div>
                        <div class="card-group">미발견</div>
                    </div>
                `}
            </div>
        `;
    }

    /**
     * 패턴 상세 보기
     */
    async showPatternDetail(patternId) {
        const pattern = this.patterns.find(p => p.pattern_id === patternId);
        if (!pattern) return;

        const progress = this.userProgress[patternId] || {};
        const isCollected = progress.is_collected || false;

        if (!isCollected && this.userRole === 'student') {
            this.showLockedMessage();
            return;
        }

        this.selectedPatternId = patternId;
        this.showPatternPopup(pattern);
    }

    /**
     * 패턴 팝업 표시
     */
    showPatternPopup(pattern) {
        const progress = this.userProgress[pattern.pattern_id] || {};
        const category = this.categories.find(c => c.code === pattern.category_code) || {};
        // 음성 파일 URL - 단순히 번호.wav 형식으로 변경
        const audioUrl = `https://mathking.kr/Contents/personas/인지관성 유형분석/${pattern.pattern_id}.wav`;

        const popupHTML = `
            <div id="pattern-popup" class="math-persona-popup">
                <div class="popup-container">
                    <button class="close-popup" onclick="window.mathPersonaSystem?.closePopup()">×</button>
                    
                    <div class="popup-header" style="background: linear-gradient(135deg, ${category.color || '#667eea'}, ${category.color || '#764ba2'}88);">
                        <div class="popup-number">#${String(pattern.pattern_id).padStart(2, '0')}</div>
                        <div class="popup-group">${this.getIcon(pattern.icon)} ${pattern.category_name}</div>
                    </div>
                    
                    <div class="popup-content">
                        <h2 class="popup-title">${pattern.pattern_name}</h2>
                        <p class="popup-english">${pattern.pattern_desc}</p>
                        
                        <div class="popup-section">
                            <h3>📋 패턴 설명</h3>
                            <p>${pattern.pattern_desc}</p>
                            <div class="priority-badge" style="background: ${this.getPriorityColor(pattern.priority)}">
                                우선순위: ${this.getPriorityText(pattern.priority)}
                            </div>
                        </div>
                        
                        <div class="popup-section">
                            <h3>💡 해결 방법</h3>
                            <p>${pattern.action || '해결 방법을 로드하는 중...'}</p>
                        </div>
                        
                        <div class="popup-section">
                            <h3>✅ 확인 방법</h3>
                            <p>${pattern.check_method || '확인 방법을 로드하는 중...'}</p>
                        </div>
                        
                        <!-- 음성 가이드 섹션 -->
                        <div class="popup-section audio-section">
                            <h3>🎧 음성 가이드</h3>
                            <div class="audio-controls">
                                <button class="play-audio-btn" onclick="window.mathPersonaSystem?.playAudio('${audioUrl}', ${pattern.pattern_id})">
                                    <span>🔊 음성 재생</span>
                                </button>
                                <span class="audio-status" id="audio-status">준비됨 (${pattern.audio_time})</span>
                            </div>
                            <div class="audio-player" id="audio-player-${pattern.pattern_id}" style="display: none;">
                                <audio id="audio-element-${pattern.pattern_id}" controls style="width: 100%;">
                                    <source src="${audioUrl}" type="audio/wav">
                                    브라우저가 오디오 재생을 지원하지 않습니다.
                                </audio>
                            </div>
                            ${pattern.audio_script ? `
                                <div class="audio-transcript" style="margin-top: 10px; padding: 10px; background: rgba(0,0,0,0.1); border-radius: 5px;">
                                    <small>${pattern.audio_script}</small>
                                </div>
                            ` : ''}
                        </div>
                        
                        <!-- 교사 대화 가이드 (교사 모드) -->
                        ${this.userRole === 'teacher' && pattern.teacher_dialog ? `
                            <div class="popup-section">
                                <h3>👩‍🏫 교사 대화 가이드</h3>
                                <p>${pattern.teacher_dialog}</p>
                            </div>
                        ` : ''}
                        
                        <!-- 연습 섹션 -->
                        <div class="popup-section practice-section">
                            <h3>📝 연습하기</h3>
                            <button class="practice-btn" onclick="window.mathPersonaSystem?.startPractice(${pattern.pattern_id})">
                                이 패턴 연습 시작
                            </button>
                        </div>
                    </div>
                    
                    <div class="popup-footer">
                        <div class="collection-status">
                            ${progress.is_collected ? 
                                `<span class="collected">✅ 수집 완료</span>` : 
                                `<span class="not-collected">🔒 미수집</span>`
                            }
                        </div>
                        ${progress.mastery_level > 0 ? `
                            <div class="mastery-level">
                                숙달도: ${progress.mastery_level}%
                            </div>
                        ` : ''}
                        ${progress.practice_count > 0 ? `
                            <div class="practice-count">
                                연습 횟수: ${progress.practice_count}회
                            </div>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', popupHTML);
        
        // 애니메이션
        setTimeout(() => {
            document.getElementById('pattern-popup')?.classList.add('show');
        }, 10);

        // 추가 데이터 로드
        this.loadPatternDetails(pattern.pattern_id);
    }

    /**
     * 패턴 상세 정보 로드
     */
    async loadPatternDetails(patternId) {
        try {
            const response = await fetch('api/get_pattern_details.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    pattern_id: patternId,
                    user_id: this.userId
                })
            });

            if (response.ok) {
                const data = await response.json();
                // 팝업 내용 업데이트
                this.updatePopupContent(data);
            }
        } catch (error) {
            console.error('패턴 상세 정보 로드 오류:', error);
        }
    }

    /**
     * 음성 재생
     */
    playAudio(audioUrl, patternId) {
        const statusEl = document.getElementById('audio-status');
        const playBtn = document.querySelector('.play-audio-btn span');
        const playerDiv = document.getElementById(`audio-player-${patternId}`);
        const audioEl = document.getElementById(`audio-element-${patternId}`);
        
        if (!audioEl) {
            console.error('오디오 엘리먼트를 찾을 수 없습니다:', patternId);
            return;
        }
        
        // 음성 파일 URL 직접 설정 (이미 전달된 URL 사용)
        console.log('음성 파일 URL:', audioUrl);
        
        if (playerDiv.style.display === 'none') {
            // 플레이어 표시
            playerDiv.style.display = 'block';
            playBtn.textContent = '⏸️ 숨기기';
            
            // 로딩 상태 표시
            if (statusEl) {
                statusEl.textContent = '🔄 음성 파일을 불러오는 중...';
                statusEl.className = 'audio-status';
            }
            
            // 오디오 재생 시도
            audioEl.play()
                .then(() => {
                    if (statusEl) {
                        statusEl.textContent = '▶️ 재생 중...';
                        statusEl.className = 'audio-status audio-playing';
                    }
                    playBtn.textContent = '⏸️ 일시정지';
                    playBtn.style.background = '#9333ea';
                })
                .catch((error) => {
                    console.error('음성 재생 오류:', error);
                    if (statusEl) {
                        statusEl.textContent = '❌ 음성 파일을 재생할 수 없습니다.';
                        statusEl.className = 'audio-status error';
                    }
                    playBtn.textContent = '🔊 음성 재생';
                    playBtn.style.background = '#667eea';
                });
            
            // 오디오 이벤트 리스너 (한 번만 추가)
            audioEl.addEventListener('play', () => {
                if (statusEl) {
                    statusEl.textContent = '▶️ 재생 중...';
                    statusEl.className = 'audio-status audio-playing';
                }
            }, { once: true });
            
            audioEl.addEventListener('pause', () => {
                if (statusEl) {
                    statusEl.textContent = '⏸️ 일시정지됨';
                    statusEl.className = 'audio-status';
                }
            }, { once: true });
            
            audioEl.addEventListener('ended', () => {
                if (statusEl) {
                    statusEl.textContent = '✅ 재생 완료';
                    statusEl.className = 'audio-status';
                }
                playBtn.textContent = '🔊 음성 재생';
                playBtn.style.background = '#667eea';
                // 연습 횟수 증가
                this.recordAudioPlay(patternId);
            }, { once: true });
            
            audioEl.addEventListener('error', (e) => {
                console.error('음성 재생 오류:', e);
                if (statusEl) {
                    statusEl.textContent = '❌ 음성 파일을 재생할 수 없습니다.';
                    statusEl.className = 'audio-status error';
                }
                playBtn.textContent = '🔊 음성 재생';
                playBtn.style.background = '#667eea';
            }, { once: true });
        } else {
            // 플레이어 숨기기
            playerDiv.style.display = 'none';
            playBtn.textContent = '🔊 음성 재생';
            playBtn.style.background = '#667eea';
            audioEl.pause();
        }
    }

    /**
     * 음성 재생 기록
     */
    async recordAudioPlay(patternId) {
        try {
            await fetch('api/record_audio_play.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_id: this.userId,
                    pattern_id: patternId
                })
            });
        } catch (error) {
            console.error('음성 재생 기록 오류:', error);
        }
    }

    /**
     * 연습 시작
     */
    startPractice(patternId) {
        const pattern = this.patterns.find(p => p.pattern_id === patternId);
        if (!pattern) return;
        
        // 연습 모달 표시
        this.showPracticeModal(pattern);
    }

    /**
     * 연습 모달 표시
     */
    showPracticeModal(pattern) {
        const practiceHTML = `
            <div id="practice-modal" class="practice-modal">
                <div class="practice-container">
                    <button class="close-practice" onclick="window.mathPersonaSystem?.closePractice()">×</button>
                    
                    <h2>🎯 ${pattern.pattern_name} 극복 연습</h2>
                    
                    <div class="practice-problem">
                        <h3>📋 상황 설명</h3>
                        <p class="problem-text">
                            ${pattern.pattern_desc}
                        </p>
                    </div>
                    
                    <div class="practice-hint">
                        <h3>💡 극복 방법</h3>
                        <p>${pattern.action || '해결 방법을 적용해보세요.'}</p>
                    </div>
                    
                    <div class="practice-input">
                        <h3>✍️ 나의 실천 계획</h3>
                        <textarea id="practice-answer" placeholder="이 패턴을 극복하기 위한 나의 구체적인 실천 계획을 적어보세요..."></textarea>
                        <button onclick="window.mathPersonaSystem?.submitPractice(${pattern.pattern_id})">제출하기</button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', practiceHTML);
        setTimeout(() => {
            document.getElementById('practice-modal')?.classList.add('show');
        }, 10);
    }

    /**
     * 연습 제출
     */
    async submitPractice(patternId) {
        const answer = document.getElementById('practice-answer')?.value;
        if (!answer || answer.trim().length < 10) {
            alert('실천 계획을 10자 이상 작성해주세요.');
            return;
        }

        try {
            // 연습 기록 저장
            const response = await fetch('api/save_practice_log.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_id: this.userId,
                    pattern_id: patternId,
                    practice_type: 'self',
                    answer: answer,
                    duration_seconds: 180 // 기본 3분
                })
            });

            if (response.ok) {
                this.showNotification('연습이 완료되었습니다! 계속 연습하여 패턴을 극복하세요.');
                this.closePractice();
                
                // 진행 상황 업데이트
                if (!this.userProgress[patternId]) {
                    this.userProgress[patternId] = {};
                }
                this.userProgress[patternId].practice_count = (this.userProgress[patternId].practice_count || 0) + 1;
                
                // 패턴 수집 (첫 연습시)
                if (!this.userProgress[patternId].is_collected) {
                    this.collectPattern(patternId);
                }
            }
        } catch (error) {
            console.error('연습 제출 오류:', error);
            alert('연습 기록 저장에 실패했습니다.');
        }
    }

    /**
     * 패턴 수집
     */
    async collectPattern(patternId) {
        try {
            const response = await fetch('api/collect_pattern.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_id: this.userId,
                    pattern_id: patternId
                })
            });

            if (response.ok) {
                this.userProgress[patternId].is_collected = true;
                this.renderPatterns(); // 카드 업데이트
                this.showNotification('🎉 새로운 패턴을 발견했습니다!');
            }
        } catch (error) {
            console.error('패턴 수집 오류:', error);
        }
    }

    /**
     * 우선순위 색상
     */
    getPriorityColor(priority) {
        const colors = {
            'high': '#ef4444',
            'medium': '#f59e0b',
            'low': '#10b981'
        };
        return colors[priority] || '#667eea';
    }

    /**
     * 우선순위 텍스트
     */
    getPriorityText(priority) {
        const texts = {
            'high': '높음',
            'medium': '보통',
            'low': '낮음'
        };
        return texts[priority] || '보통';
    }

    /**
     * 카테고리 선택
     */
    selectCategory(categoryCode) {
        this.currentGroup = categoryCode;
        
        // 탭 활성화 상태 업데이트
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.category === categoryCode);
        });
        
        this.renderPatterns();
    }

    /**
     * 카테고리별 패턴 가져오기
     */
    getPatternsByCategory(categoryCode) {
        return this.patterns.filter(p => p.category_code === categoryCode);
    }

    /**
     * 수집한 패턴 수
     */
    getCollectedCount() {
        return Object.values(this.userProgress).filter(p => p.is_collected).length;
    }

    /**
     * 전체 진행도
     */
    getOverallProgress() {
        const totalMastery = Object.values(this.userProgress)
            .reduce((sum, p) => sum + (p.mastery_level || 0), 0);
        return Math.floor(totalMastery / 60);
    }

    /**
     * 팝업 닫기
     */
    closePopup() {
        const popup = document.getElementById('pattern-popup');
        if (popup) {
            popup.classList.remove('show');
            setTimeout(() => popup.remove(), 300);
        }
        
        // 오디오 정지
        const audioEl = document.getElementById(`audio-element-${this.selectedPatternId}`);
        if (audioEl) {
            audioEl.pause();
        }
    }

    /**
     * 연습 모달 닫기
     */
    closePractice() {
        const modal = document.getElementById('practice-modal');
        if (modal) {
            modal.classList.remove('show');
            setTimeout(() => modal.remove(), 300);
        }
    }

    /**
     * 잠긴 메시지 표시
     */
    showLockedMessage() {
        const message = document.createElement('div');
        message.className = 'locked-message';
        message.innerHTML = `
            <div style="text-align: center;">
                <div style="font-size: 2rem; margin-bottom: 10px;">🔒</div>
                <p>이 패턴은 아직 발견하지 못했습니다.</p>
                <p style="font-size: 0.9rem; opacity: 0.8;">학습을 진행하며 패턴을 수집해보세요!</p>
            </div>
        `;
        
        document.body.appendChild(message);
        
        setTimeout(() => {
            message.classList.add('show');
        }, 10);
        
        setTimeout(() => {
            message.classList.remove('show');
            setTimeout(() => message.remove(), 300);
        }, 3000);
    }

    /**
     * 알림 표시
     */
    showNotification(text) {
        const notification = document.createElement('div');
        notification.className = 'notification-toast';
        notification.innerHTML = `
            <div style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 15px 25px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.3);">
                ${text}
            </div>
        `;
        notification.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 10005; opacity: 0; transition: opacity 0.3s ease;';
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.opacity = '1';
        }, 10);
        
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    /**
     * 도감 열기
     */
    async open() {
        console.log('도감 열기 시작');
        const library = document.getElementById('math-persona-library');
        
        if (!library) {
            console.log('도감이 없으므로 새로 생성합니다.');
            this.createLibraryInterface();
        }
        
        const libraryAfterCreate = document.getElementById('math-persona-library');
        if (libraryAfterCreate) {
            libraryAfterCreate.style.display = 'flex';
            setTimeout(() => libraryAfterCreate.classList.add('show'), 10);
            this.isOpen = true;
            
            console.log('데이터 로드 시작...');
            // 데이터 새로고침 및 렌더링
            await this.loadDataFromDB();
            
            console.log('진행 상황 업데이트...');
            // 진행 상황 업데이트
            this.updateProgressDisplay();
            
            console.log('도감 열기 완료');
        } else {
            console.error('도감 엘리먼트를 생성할 수 없습니다.');
        }
    }

    /**
     * 도감 닫기
     */
    close() {
        const library = document.getElementById('math-persona-library');
        if (library) {
            library.classList.remove('show');
            setTimeout(() => {
                library.style.display = 'none';
            }, 300);
            this.isOpen = false;
        }
    }

    /**
     * 이벤트 바인딩
     */
    bindEventListeners() {
        // ESC 키로 닫기
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                if (document.getElementById('practice-modal')) {
                    this.closePractice();
                } else if (document.getElementById('pattern-popup')) {
                    this.closePopup();
                } else if (this.isOpen) {
                    this.close();
                }
            }
        });
    }
}

// 전역 인스턴스 생성
document.addEventListener('DOMContentLoaded', () => {
    window.mathPersonaSystem = new MathPersonaSystem();
});