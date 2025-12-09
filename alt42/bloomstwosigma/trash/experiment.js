// 전역 변수
let state = {
    activeTab: 'design',
    selectedTeacher: '',
    selectedStudents: [],
    controlGroup: [],
    experimentGroup: [],
    experimentConfig: {
        name: '',
        description: '',
        duration: 8,
        startDate: '',
        selectedFeedbacks: {
            metacognitive: [],
            learning: [],
            combined: []
        },
        disabledForControl: []
    },
    trackingConfigs: [
        { id: 1, name: '학습 세션 정답률 추적', description: '개별 세션의 정답률 변화 모니터링', isActive: true },
        { id: 2, name: '메타인지 활동 빈도', description: '자기점검 및 모니터링 행동 횟수', isActive: false },
        { id: 3, name: '피드백 반응 시간', description: '피드백 제공 후 학습자 반응시간', isActive: true }
    ],
    experimentResults: [
        {
            id: 1,
            date: '2024-03-15',
            type: '정답률',
            summary: '실험그룹 정답률 12% 향상',
            details: '메타인지 피드백을 받은 학생들의 평균 정답률이 73%에서 85%로 상승. 특히 자기점검 질문에 대한 반응이 좋았음.',
            significance: '통계적으로 유의미한 차이 (p<0.05)',
            author: '김연구자'
        },
        {
            id: 2,
            date: '2024-03-20',
            type: '응답시간',
            summary: '평균 응답시간 25% 단축',
            details: '학습인지 피드백 그룹에서 문제 해결 시간이 현저히 줄어듦. 단계별 힌트 제공이 효과적이었음.',
            significance: '중간 정도의 효과크기 (Cohen\'s d = 0.6)',
            author: '박연구자'
        },
        {
            id: 3,
            date: '2024-03-22',
            type: '지속성',
            summary: '학습 지속시간 40% 증가',
            details: '결합형 피드백을 받은 학생들이 더 오래 집중하며 학습을 지속함. 중도 포기율이 현저히 감소.',
            significance: '높은 실용적 의미',
            author: '이연구자'
        }
    ],
    hypotheses: [
        {
            id: 1,
            text: '메타인지 피드백의 제공 타이밍을 문제 풀이 중간으로 조정하면 더 큰 효과를 얻을 것',
            author: '김연구자',
            date: '2024-03-25',
            category: '타이밍 최적화'
        },
        {
            id: 2,
            text: '학습자의 사전 메타인지 수준에 따라 피드백 효과가 달라질 것으로 예상',
            author: '박연구자',
            date: '2024-03-24',
            category: '개인차 변인'
        }
    ],
    currentModal: null
};

// 선생님별 학생 데이터
const teacherStudents = {
    "김선생님": [
        { id: 1, name: "김민준", grade: "고2", school: "서울고", avgScore: 85, patterns: ["아이디어 해방 자동발화형"] },
        { id: 2, name: "이서연", grade: "고2", school: "서울고", avgScore: 92, patterns: ["과신-시야 협착형"] },
        { id: 3, name: "박지호", grade: "고2", school: "서울고", avgScore: 78, patterns: ["작업기억 ⅔ 할당형"] },
        { id: 4, name: "최수아", grade: "고2", school: "서울고", avgScore: 88, patterns: ["모순 확신-답불가형"] },
        { id: 5, name: "정예준", grade: "고2", school: "서울고", avgScore: 73, patterns: ["속도 압박 억제형"] }
    ],
    "박선생님": [
        { id: 6, name: "김하은", grade: "고2", school: "경기고", avgScore: 95, patterns: ["과신-시야 협착형"] },
        { id: 7, name: "이준서", grade: "고2", school: "경기고", avgScore: 81, patterns: ["무의식 재현 루프형"] },
        { id: 8, name: "박민지", grade: "고2", school: "경기고", avgScore: 87, patterns: ["해설지-혼합 착각형"] },
        { id: 9, name: "최준혁", grade: "고2", school: "경기고", avgScore: 76, patterns: ["노이즈 필터 실패형"] },
        { id: 10, name: "정서윤", grade: "고2", school: "경기고", avgScore: 90, patterns: ["시각화 회피형"] }
    ],
    "이선생님": [
        { id: 11, name: "강도윤", grade: "고1", school: "대전고", avgScore: 82, patterns: ["피로-오답 포용형"] },
        { id: 12, name: "윤채원", grade: "고1", school: "대전고", avgScore: 88, patterns: ["메타인지 과부하형"] },
        { id: 13, name: "임현우", grade: "고1", school: "대전고", avgScore: 79, patterns: ["단계 건너뛰기형"] },
        { id: 14, name: "한소영", grade: "고1", school: "대전고", avgScore: 91, patterns: ["검증 회피형"] },
        { id: 15, name: "오준석", grade: "고1", school: "대전고", avgScore: 75, patterns: ["개념 연결 실패형"] }
    ]
};

// 피드백 목록
const feedbackList = {
    metacognitive: [
        { id: 'mc1', name: '자기점검 질문', desc: '"지금까지 풀이가 맞는지 확인해보세요"', file: 'metacognitive_selfcheck.php' },
        { id: 'mc2', name: '전략 확인', desc: '"어떤 방법으로 이 문제를 해결하고 있나요?"', file: 'metacognitive_strategy.php' },
        { id: 'mc3', name: '이해도 점검', desc: '"이 문제에서 무엇을 구하는지 명확한가요?"', file: 'metacognitive_understanding.php' },
        { id: 'mc4', name: '진행 상황 모니터링', desc: '"현재 풀이에서 어느 정도 진행되었나요?"', file: 'metacognitive_progress.php' },
        { id: 'mc5', name: '오류 탐지', desc: '"혹시 놓친 조건이나 실수가 있는지 점검해보세요"', file: 'metacognitive_error.php' }
    ],
    learning: [
        { id: 'lc1', name: '개념 연결', desc: '"이 문제는 [개념명]과 관련이 있습니다"', file: 'learning_concept.php' },
        { id: 'lc2', name: '유사 문제 제시', desc: '"비슷한 유형의 문제를 참고해보세요"', file: 'learning_similar.php' },
        { id: 'lc3', name: '단계별 힌트', desc: '"다음 단계는 이것입니다"', file: 'learning_step.php' },
        { id: 'lc4', name: '공식 안내', desc: '"이 상황에서 사용할 수 있는 공식입니다"', file: 'learning_formula.php' },
        { id: 'lc5', name: '풀이 전략 제안', desc: '"이런 방법으로 접근해보세요"', file: 'learning_approach.php' },
        { id: 'lc6', name: '예시 설명', desc: '"구체적인 예시로 설명하면..."', file: 'learning_example.php' }
    ]
};

// DOM 로드 완료 시 초기화
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

// 앱 초기화
function initializeApp() {
    setupEventListeners();
    loadInitialData();
    updateUI();
}

// 이벤트 리스너 설정
function setupEventListeners() {
    // 네비게이션 탭
    document.querySelectorAll('.nav-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            switchTab(this.dataset.tab);
        });
    });

    // 실험 설정 폼
    document.getElementById('experiment-config-form').addEventListener('submit', function(e) {
        e.preventDefault();
        saveExperimentConfig();
    });

    // 선생님 선택
    document.getElementById('teacher-select').addEventListener('change', function() {
        handleTeacherSelection(this.value);
    });

    // 피드백 방법 선택
    document.querySelectorAll('.feedback-method').forEach(btn => {
        btn.addEventListener('click', function() {
            showFeedbackModal(this.dataset.type);
        });
    });

    // 그룹 배정 버튼
    document.getElementById('add-to-control').addEventListener('click', function() {
        addSelectedStudentsToGroup('control');
    });

    document.getElementById('add-to-experiment').addEventListener('click', function() {
        addSelectedStudentsToGroup('experiment');
    });

    // 추적 설정 추가
    document.getElementById('add-tracking-config').addEventListener('click', function() {
        showTrackingModal();
    });

    // 가설 추가
    document.getElementById('add-hypothesis').addEventListener('click', function() {
        addHypothesis();
    });

    // 모달 버튼들
    document.getElementById('show-survey-modal').addEventListener('click', function() {
        showSurveyModal();
    });

    document.getElementById('show-analysis-modal').addEventListener('click', function() {
        showAnalysisModal();
    });

    // 모달 닫기
    document.querySelectorAll('.close-modal').forEach(btn => {
        btn.addEventListener('click', function() {
            closeModal();
        });
    });

    // 모달 외부 클릭 시 닫기
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    });

    // 추적 설정 폼 제출
    document.getElementById('tracking-form').addEventListener('submit', function(e) {
        e.preventDefault();
        addTrackingConfig();
    });

    // 실험 설정 필드 변경 감지
    document.getElementById('experiment-name').addEventListener('input', function() {
        updateExperimentTitle(this.value);
    });

    document.getElementById('duration').addEventListener('input', function() {
        updateFooterDuration(this.value);
    });
}

// 초기 데이터 로드
function loadInitialData() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('start-date').value = today;
    
    renderTrackingConfigs();
    renderExperimentResults();
    renderHypotheses();
    renderSurveyItems();
}

// 탭 전환
function switchTab(tabName) {
    // 네비게이션 탭 업데이트
    document.querySelectorAll('.nav-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');

    // 탭 컨텐츠 업데이트
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    document.getElementById(`${tabName}-tab`).classList.add('active');

    state.activeTab = tabName;
}

// 실험 설정 저장
function saveExperimentConfig() {
    state.experimentConfig = {
        ...state.experimentConfig,
        name: document.getElementById('experiment-name').value,
        description: document.getElementById('experiment-description').value,
        duration: parseInt(document.getElementById('duration').value),
        startDate: document.getElementById('start-date').value
    };
    
    updateExperimentTitle(state.experimentConfig.name);
    updateFooterDuration(state.experimentConfig.duration);
    
    alert('실험 설정이 저장되었습니다.');
}

// 실험 제목 업데이트
function updateExperimentTitle(title) {
    const titleElement = document.getElementById('experiment-title');
    titleElement.textContent = title || '실험명을 설정해주세요';
}

// 푸터 기간 업데이트
function updateFooterDuration(duration) {
    document.getElementById('footer-duration').textContent = duration;
}

// 선생님 선택 처리
function handleTeacherSelection(teacher) {
    state.selectedTeacher = teacher;
    
    if (teacher) {
        document.getElementById('group-assignment-container').style.display = 'grid';
        document.getElementById('teacher-students-title').textContent = `${teacher} 학생 목록`;
        renderAvailableStudents();
    } else {
        document.getElementById('group-assignment-container').style.display = 'none';
    }
}

// 사용 가능한 학생 목록 렌더링
function renderAvailableStudents() {
    const container = document.getElementById('available-students');
    const students = teacherStudents[state.selectedTeacher] || [];
    
    // 이미 배정된 학생들 제외
    const usedIds = [...state.controlGroup, ...state.experimentGroup].map(s => s.id);
    const availableStudents = students.filter(s => !usedIds.includes(s.id));
    
    container.innerHTML = availableStudents.map(student => `
        <div class="student-item" data-student-id="${student.id}">
            <div class="student-name">${student.name}</div>
            <div class="student-details">${student.grade} | ${student.school} | 평균: ${student.avgScore}점</div>
        </div>
    `).join('');
    
    // 학생 선택 이벤트 리스너 추가
    container.querySelectorAll('.student-item').forEach(item => {
        item.addEventListener('click', function() {
            toggleStudentSelection(parseInt(this.dataset.studentId));
        });
    });
}

// 학생 선택/해제
function toggleStudentSelection(studentId) {
    const student = teacherStudents[state.selectedTeacher].find(s => s.id === studentId);
    const studentElement = document.querySelector(`[data-student-id="${studentId}"]`);
    
    if (state.selectedStudents.find(s => s.id === studentId)) {
        state.selectedStudents = state.selectedStudents.filter(s => s.id !== studentId);
        studentElement.classList.remove('selected');
    } else {
        state.selectedStudents.push(student);
        studentElement.classList.add('selected');
    }
    
    updateGroupButtons();
}

// 그룹 버튼 상태 업데이트
function updateGroupButtons() {
    const hasSelected = state.selectedStudents.length > 0;
    document.getElementById('add-to-control').disabled = !hasSelected;
    document.getElementById('add-to-experiment').disabled = !hasSelected;
}

// 선택된 학생들을 그룹에 추가
function addSelectedStudentsToGroup(groupType) {
    if (groupType === 'control') {
        state.controlGroup.push(...state.selectedStudents);
    } else {
        state.experimentGroup.push(...state.selectedStudents);
    }
    
    state.selectedStudents = [];
    renderAvailableStudents();
    renderGroups();
    updateGroupButtons();
    updateParticipantCount();
}

// 그룹 렌더링
function renderGroups() {
    renderGroup('control-group', state.controlGroup);
    renderGroup('experiment-group', state.experimentGroup);
    
    document.getElementById('control-group-count').textContent = state.controlGroup.length;
    document.getElementById('experiment-group-count').textContent = state.experimentGroup.length;
}

// 개별 그룹 렌더링
function renderGroup(containerId, students) {
    const container = document.getElementById(containerId);
    
    container.innerHTML = students.map(student => `
        <div class="student-item">
            <div class="flex items-center justify-between">
                <div>
                    <div class="student-name">${student.name}</div>
                    <div class="student-details">${student.grade} | ${student.school}</div>
                </div>
                <button class="remove-student text-gray-400 hover:text-red-400" data-student-id="${student.id}" data-group="${containerId}">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `).join('');
    
    // 학생 제거 이벤트 리스너 추가
    container.querySelectorAll('.remove-student').forEach(btn => {
        btn.addEventListener('click', function() {
            removeStudentFromGroup(parseInt(this.dataset.studentId), this.dataset.group);
        });
    });
}

// 그룹에서 학생 제거
function removeStudentFromGroup(studentId, groupContainer) {
    if (groupContainer === 'control-group') {
        state.controlGroup = state.controlGroup.filter(s => s.id !== studentId);
    } else {
        state.experimentGroup = state.experimentGroup.filter(s => s.id !== studentId);
    }
    
    renderAvailableStudents();
    renderGroups();
    updateParticipantCount();
}

// 참가자 수 업데이트
function updateParticipantCount() {
    const totalCount = state.controlGroup.length + state.experimentGroup.length;
    document.getElementById('participant-count').textContent = `참가자: ${totalCount}명`;
}

// 피드백 모달 표시
function showFeedbackModal(type) {
    const modal = document.getElementById('feedback-modal');
    const title = document.getElementById('feedback-modal-title');
    const feedbackContainer = document.getElementById('feedback-list');
    
    state.currentModal = type;
    
    // 제목 설정
    const titles = {
        metacognitive: '메타인지 피드백 선택',
        learning: '학습인지 피드백 선택',
        combined: '결합형 피드백 선택'
    };
    title.textContent = titles[type];
    
    // 피드백 목록 렌더링
    const feedbacks = type === 'combined' 
        ? [...feedbackList.metacognitive, ...feedbackList.learning]
        : feedbackList[type] || [];
    
    const selectedFeedbacks = state.experimentConfig.selectedFeedbacks[type] || [];
    
    feedbackContainer.innerHTML = feedbacks.map(feedback => {
        const isSelected = selectedFeedbacks.includes(feedback.id);
        return `
            <div class="feedback-item p-4 rounded-lg border-2 cursor-pointer transition-all ${
                isSelected 
                    ? 'border-purple-400 bg-purple-500/20' 
                    : 'border-white/20 bg-white/5 hover:bg-white/10'
            }" data-feedback-id="${feedback.id}">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <h4 class="font-semibold">${feedback.name}</h4>
                        <p class="text-sm text-gray-300 mt-1">${feedback.desc}</p>
                        <p class="text-xs text-gray-500 mt-2">파일: ${feedback.file}</p>
                    </div>
                    ${isSelected ? '<i class="fas fa-check-circle text-purple-400"></i>' : ''}
                </div>
            </div>
        `;
    }).join('');
    
    // 피드백 선택 이벤트 리스너 추가
    feedbackContainer.querySelectorAll('.feedback-item').forEach(item => {
        item.addEventListener('click', function() {
            toggleFeedbackSelection(this.dataset.feedbackId);
        });
    });
    
    modal.classList.remove('hidden');
}

// 피드백 선택/해제
function toggleFeedbackSelection(feedbackId) {
    const type = state.currentModal;
    const selectedFeedbacks = state.experimentConfig.selectedFeedbacks[type] || [];
    
    if (selectedFeedbacks.includes(feedbackId)) {
        state.experimentConfig.selectedFeedbacks[type] = selectedFeedbacks.filter(id => id !== feedbackId);
    } else {
        state.experimentConfig.selectedFeedbacks[type] = [...selectedFeedbacks, feedbackId];
    }
    
    updateFeedbackCounts();
    showFeedbackModal(type); // 모달 다시 렌더링
}

// 피드백 개수 업데이트
function updateFeedbackCounts() {
    document.getElementById('metacognitive-count').textContent = 
        `선택됨: ${state.experimentConfig.selectedFeedbacks.metacognitive.length}개`;
    document.getElementById('learning-count').textContent = 
        `선택됨: ${state.experimentConfig.selectedFeedbacks.learning.length}개`;
    document.getElementById('combined-count').textContent = 
        `선택됨: ${state.experimentConfig.selectedFeedbacks.combined.length}개`;
    
    const totalFeedbacks = Object.values(state.experimentConfig.selectedFeedbacks)
        .reduce((sum, arr) => sum + arr.length, 0);
    document.getElementById('footer-feedback').textContent = totalFeedbacks;
}

// 추적 설정 렌더링
function renderTrackingConfigs() {
    const container = document.getElementById('tracking-configs');
    
    container.innerHTML = state.trackingConfigs.map(config => `
        <label class="tracking-config flex items-center space-x-3 p-3 bg-white/5 rounded-lg cursor-pointer hover:bg-white/10 ${config.isActive ? 'active' : ''}">
            <input
                type="checkbox"
                ${config.isActive ? 'checked' : ''}
                data-config-id="${config.id}"
                class="rounded border-gray-300"
            />
            <div class="flex-1">
                <p class="font-medium">${config.name}</p>
                <p class="text-sm text-gray-400">${config.description}</p>
            </div>
        </label>
    `).join('');
    
    // 체크박스 이벤트 리스너 추가
    container.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            toggleTrackingConfig(parseInt(this.dataset.configId));
        });
    });
    
    updateTrackingFooter();
}

// 추적 설정 토글
function toggleTrackingConfig(configId) {
    state.trackingConfigs = state.trackingConfigs.map(config => 
        config.id === configId 
            ? { ...config, isActive: !config.isActive }
            : config
    );
    
    renderTrackingConfigs();
    renderTrackingConfigList();
}

// 추적 설정 목록 렌더링 (데이터 추적 탭용)
function renderTrackingConfigList() {
    const container = document.getElementById('tracking-config-list');
    
    container.innerHTML = state.trackingConfigs.map(config => `
        <div class="tracking-config p-4 rounded-lg border-2 ${
            config.isActive 
                ? 'border-green-400/50 bg-green-500/10' 
                : 'border-white/20 bg-white/5'
        }">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <h4 class="font-semibold">${config.name}</h4>
                    <p class="text-sm text-gray-400 mt-1">${config.description}</p>
                </div>
                
                <div class="flex items-center space-x-2">
                    <button
                        class="px-3 py-1 rounded text-sm ${
                            config.isActive 
                                ? 'bg-green-500 hover:bg-green-600' 
                                : 'bg-gray-500 hover:bg-gray-600'
                        }"
                        onclick="toggleTrackingConfig(${config.id})"
                    >
                        ${config.isActive ? '활성' : '비활성'}
                    </button>
                    
                    <button
                        onclick="deleteTrackingConfig(${config.id})"
                        class="px-3 py-1 bg-red-500 hover:bg-red-600 rounded text-sm"
                    >
                        삭제
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

// 추적 설정 삭제
function deleteTrackingConfig(configId) {
    if (confirm('이 추적 설정을 삭제하시겠습니까?')) {
        state.trackingConfigs = state.trackingConfigs.filter(config => config.id !== configId);
        renderTrackingConfigs();
        renderTrackingConfigList();
    }
}

// 추적 푸터 업데이트
function updateTrackingFooter() {
    const activeCount = state.trackingConfigs.filter(c => c.isActive).length;
    document.getElementById('footer-tracking').textContent = activeCount;
}

// 추적 설정 모달 표시
function showTrackingModal() {
    document.getElementById('tracking-modal').classList.remove('hidden');
}

// 추적 설정 추가
function addTrackingConfig() {
    const name = document.getElementById('tracking-name').value.trim();
    const description = document.getElementById('tracking-description').value.trim();
    
    if (!name || !description) {
        alert('모든 필드를 입력해주세요.');
        return;
    }
    
    const newConfig = {
        id: Date.now(),
        name: name,
        description: description,
        isActive: false
    };
    
    state.trackingConfigs.push(newConfig);
    renderTrackingConfigs();
    renderTrackingConfigList();
    
    // 폼 초기화
    document.getElementById('tracking-form').reset();
    closeModal();
}

// 실험 결과 렌더링
function renderExperimentResults() {
    const container = document.getElementById('experiment-results');
    
    container.innerHTML = state.experimentResults.map(result => `
        <div class="experiment-result" onclick="showResultDetail(${result.id})">
            <div class="flex items-center justify-between mb-2">
                <span class="result-type">${result.type}</span>
                <span class="result-date">${result.date}</span>
            </div>
            <p class="result-summary">${result.summary}</p>
            <p class="result-author">작성자: ${result.author}</p>
        </div>
    `).join('');
}

// 결과 상세 보기
function showResultDetail(resultId) {
    const result = state.experimentResults.find(r => r.id === resultId);
    if (result) {
        alert(`${result.summary}\n\n${result.details}\n\n${result.significance}`);
    }
}

// 가설 렌더링
function renderHypotheses() {
    const container = document.getElementById('hypotheses-list');
    
    container.innerHTML = state.hypotheses.map(hypothesis => `
        <div class="hypothesis-item">
            <p class="hypothesis-text">${hypothesis.text}</p>
            <div class="hypothesis-meta">
                <span>${hypothesis.author}</span>
                <div class="flex items-center space-x-2">
                    <span class="hypothesis-category">${hypothesis.category}</span>
                    <span>${hypothesis.date}</span>
                </div>
            </div>
            
            <div class="hypothesis-actions">
                <button class="bg-blue-500 hover:bg-blue-600" onclick="startMonitoring(${hypothesis.id})">
                    <i class="fas fa-eye"></i>
                    <span>모니터링</span>
                </button>
                
                <button class="bg-green-500 hover:bg-green-600" onclick="startExperimentFromHypothesis(${hypothesis.id})">
                    <i class="fas fa-play"></i>
                    <span>실험</span>
                </button>
            </div>
        </div>
    `).join('');
}

// 가설 추가
function addHypothesis() {
    const text = document.getElementById('new-hypothesis').value.trim();
    
    if (!text) {
        alert('가설을 입력해주세요.');
        return;
    }
    
    const newHypothesis = {
        id: Date.now(),
        text: text,
        author: '현재 사용자',
        date: new Date().toISOString().split('T')[0],
        category: '신규 가설'
    };
    
    state.hypotheses.push(newHypothesis);
    renderHypotheses();
    
    document.getElementById('new-hypothesis').value = '';
}

// 가설에서 모니터링 시작
function startMonitoring(hypothesisId) {
    alert('모니터링 설정 기능은 개발 중입니다.');
}

// 가설에서 실험 시작
function startExperimentFromHypothesis(hypothesisId) {
    const hypothesis = state.hypotheses.find(h => h.id === hypothesisId);
    if (hypothesis) {
        switchTab('design');
        document.getElementById('experiment-name').value = `가설 검증: ${hypothesis.text.substring(0, 30)}...`;
        document.getElementById('experiment-description').value = `다음 가설을 검증하기 위한 실험입니다: ${hypothesis.text}`;
        updateExperimentTitle(document.getElementById('experiment-name').value);
    }
}

// 설문 항목 렌더링
function renderSurveyItems() {
    const container = document.getElementById('survey-items');
    
    const surveyItems = [
        { id: 1, text: '메타인지 피드백이 학습에 도움이 되었습니까?', type: 'Likert 척도', category: '피드백 효과성' },
        { id: 2, text: '어떤 종류의 피드백이 가장 유용했습니까?', type: '객관식', category: '피드백 효과성' },
        { id: 3, text: '실험 참여 후 가장 큰 변화는 무엇입니까?', type: '주관식', category: '학습 만족도' }
    ];
    
    container.innerHTML = surveyItems.map(item => `
        <div class="p-3 bg-white/5 rounded-lg">
            <p class="font-medium text-sm">${item.id}. ${item.text}</p>
            <p class="text-xs text-gray-400">유형: ${item.type} | 카테고리: ${item.category}</p>
        </div>
    `).join('');
}

// 설문 모달 표시
function showSurveyModal() {
    document.getElementById('survey-modal').classList.remove('hidden');
}

// 분석 모달 표시
function showAnalysisModal() {
    const reportContainer = document.getElementById('analysis-report');
    
    reportContainer.innerHTML = `
        <h4 class="text-lg font-semibold mb-4">메타인지·학습인지 피드백 효과 분석 보고서</h4>
        
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="bg-purple-100 p-4 rounded">
                <h5 class="font-semibold text-purple-800">실험군 성과</h5>
                <p class="text-2xl font-bold text-purple-600">+24.5%</p>
                <p class="text-sm text-purple-700">정답률 향상</p>
            </div>
            <div class="bg-blue-100 p-4 rounded">
                <h5 class="font-semibold text-blue-800">응답시간 단축</h5>
                <p class="text-2xl font-bold text-blue-600">-18.3%</p>
                <p class="text-sm text-blue-700">평균 응답시간</p>
            </div>
            <div class="bg-green-100 p-4 rounded">
                <h5 class="font-semibold text-green-800">참여도 증가</h5>
                <p class="text-2xl font-bold text-green-600">+31.2%</p>
                <p class="text-sm text-green-700">학습 지속시간</p>
            </div>
        </div>

        <h5 class="text-base font-medium mb-3">1. 연구 개요</h5>
        <p class="text-sm mb-4">
            본 연구는 ${state.experimentConfig.name || '메타인지 피드백 효과'}에 대한 실험으로, 
            ${state.experimentGroup.length}명의 실험군과 ${state.controlGroup.length}명의 통제군을 대상으로 
            ${state.experimentConfig.duration}주간 진행되었습니다.
        </p>

        <h5 class="text-base font-medium mb-3">2. 주요 발견사항</h5>
        <ul class="text-sm mb-4 list-disc list-inside">
            <li>메타인지 피드백을 받은 학생들의 자기점검 빈도가 평균 2.3배 증가</li>
            <li>학습인지 피드백이 문제 해결 속도 향상에 가장 효과적</li>
            <li>결합형 피드백 그룹에서 학습 지속성이 가장 높게 나타남</li>
            <li>개별 학생의 학습 패턴에 따른 피드백 효과 차이 확인</li>
        </ul>

        <h5 class="text-base font-medium mb-3">3. 통계 분석 결과</h5>
        <p class="text-sm mb-2">
            <strong>정답률:</strong> 실험군 평균 85.2% vs 통제군 평균 67.8% (p &lt; 0.001)
        </p>
        <p class="text-sm mb-2">
            <strong>응답시간:</strong> 실험군 평균 42.3초 vs 통제군 평균 51.8초 (p &lt; 0.01)
        </p>
        <p class="text-sm mb-4">
            <strong>효과크기:</strong> Cohen's d = 1.23 (큰 효과크기)
        </p>

        <h5 class="text-base font-medium mb-3">4. 질적 분석</h5>
        <p class="text-sm mb-4">
            학생 인터뷰 결과, 메타인지 피드백을 받은 학생들은 "문제를 풀 때 더 신중해졌다", 
            "실수를 줄이는 방법을 배웠다"는 반응을 보였습니다. 특히 자기점검 질문이 
            가장 도움이 되었다고 응답했습니다.
        </p>

        <h5 class="text-base font-medium mb-3">5. 결론 및 시사점</h5>
        <p class="text-sm">
            메타인지 및 학습인지 피드백은 학생들의 자기조절학습 능력과 학업성취도 향상에 
            유의미한 효과가 있음을 확인했습니다. 향후 개별 맞춤형 피드백 시스템 구축이 필요합니다.
        </p>
    `;
    
    document.getElementById('analysis-modal').classList.remove('hidden');
}

// 모달 닫기
function closeModal() {
    document.querySelectorAll('.modal').forEach(modal => {
        modal.classList.add('hidden');
    });
    state.currentModal = null;
}

// UI 업데이트
function updateUI() {
    updateParticipantCount();
    updateFeedbackCounts();
    updateTrackingFooter();
    renderGroups();
}

// DB 관련 상태
let dbTables = [];
let selectedTable = null;
let selectedTableFields = [];
let conditions = [];
let conditionCounter = 0;
let currentModalPage = 1;
let modalPagination = null;

// DB 연결 테스트
async function testDBConnection() {
    try {
        const response = await fetch('experiment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=test_connection'
        });
        
        const result = await response.json();
        console.log('DB 연결 테스트 결과:', result);
        
        // 결과를 알림창으로 표시
        let message = `DB 연결 테스트 결과:\n\n`;
        message += `연결 상태: ${result.connection_test.status}\n`;
        message += `메시지: ${result.connection_test.message}\n`;
        message += `사용 가능한 DB: ${result.available_databases.join(', ')}\n`;
        message += `mathking DB 존재: ${result.mathking_exists ? '예' : '아니오'}\n`;
        message += `현재 DB 테이블 수: ${result.current_db_table_count}\n`;
        
        alert(message);
        
    } catch (error) {
        console.error('DB 연결 테스트 실패:', error);
        alert('DB 연결 테스트 실패: ' + error.message);
    }
}

// DB 테이블 목록 새로고침
async function refreshDBTables() {
    const tablesList = document.getElementById('dbTablesList');
    if (tablesList) {
        tablesList.innerHTML = '<div class="text-gray-400 text-center py-4">테이블 목록을 로딩중...</div>';
    }
    
    try {
        const response = await fetch('experiment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_db_tables'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('DB 테이블 조회 결과:', result);
        
        if (result.error) {
            console.error('DB 오류:', result.error);
            if (tablesList) {
                tablesList.innerHTML = `<div class="text-red-400 text-center py-4">
                    DB 연결 오류: ${result.error}
                    <br><br>
                    <button onclick="testDBConnection()" class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">
                        연결 테스트
                    </button>
                </div>`;
            }
            return;
        }
        
        dbTables = result.tables || [];
        console.log('로딩된 테이블 수:', dbTables.length);
        renderDBTables();
        
    } catch (error) {
        console.error('DB 테이블 목록 조회 실패:', error);
        if (tablesList) {
            tablesList.innerHTML = `<div class="text-red-400 text-center py-4">
                오류: ${error.message}
                <br><br>
                <button onclick="testDBConnection()" class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">
                    연결 테스트
                </button>
            </div>`;
        }
    }
}

// DB 테이블 목록 렌더링
function renderDBTables() {
    const tablesList = document.getElementById('dbTablesList');
    if (!tablesList) return;
    
    if (dbTables.length === 0) {
        tablesList.innerHTML = '<div class="text-gray-400 text-center py-4">테이블이 없습니다.</div>';
        return;
    }
    
    tablesList.innerHTML = dbTables.map(table => `
        <div class="bg-white/5 border border-white/20 rounded-lg p-3 cursor-pointer hover:bg-white/10 transition-all"
             onclick="viewTableData('${table.name}', this)">
            <div class="flex justify-between items-start mb-2">
                <h4 class="font-medium text-white">${table.name}</h4>
                <span class="text-xs text-gray-400">${table.records}개</span>
            </div>
            <div class="text-xs text-gray-400 space-y-1">
                <div>엔진: ${table.engine}</div>
                <div>크기: ${table.size}</div>
                ${table.last_update ? `<div>수정: ${table.last_update}</div>` : ''}
            </div>
        </div>
    `).join('');
}

// 테이블 선택
function selectTable(tableName) {
    selectedTable = tableName;
    
    // 선택된 테이블 하이라이트
    document.querySelectorAll('#dbTablesList > div').forEach(div => {
        div.classList.remove('border-blue-400/50', 'bg-blue-500/20');
        div.classList.add('border-white/20');
    });
    
    event.target.closest('div').classList.remove('border-white/20');
    event.target.closest('div').classList.add('border-blue-400/50', 'bg-blue-500/20');
    
    // 미리보기 및 추가 버튼 활성화
    document.getElementById('previewTableBtn').disabled = false;
    document.getElementById('addTableBtn').disabled = false;
}

// 테이블 클릭 시 팝업으로 데이터 보기
function viewTableData(tableName, clickedElement) {
    selectedTable = tableName;
    
    // 다른 테이블 하이라이트 제거
    document.querySelectorAll('#dbTablesList > div').forEach(div => {
        div.classList.remove('border-blue-400/50', 'bg-blue-500/20');
        div.classList.add('border-white/20');
    });
    
    // 클릭된 테이블 하이라이트
    clickedElement.classList.remove('border-white/20');
    clickedElement.classList.add('border-blue-400/50', 'bg-blue-500/20');
    
    // 팝업으로 데이터 보기
    showTableDataModal(tableName);
}

// 테이블 미리보기
async function previewTable() {
    if (!selectedTable) {
        alert('테이블을 먼저 선택해주세요.');
        return;
    }
    
    try {
        const response = await fetch('experiment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=get_table_data&table_name=${selectedTable}&limit=10`
        });
        
        const result = await response.json();
        
        if (result.error) {
            alert('테이블 미리보기 오류: ' + result.error);
            return;
        }
        
        showTablePreviewModal(result.data || [], result.columns || []);
        
    } catch (error) {
        console.error('테이블 미리보기 실패:', error);
        alert('테이블 미리보기를 불러오는데 실패했습니다.');
    }
}

// 테이블 데이터 모달 표시 (페이지네이션 지원)
let currentTableData = null;

async function showTableDataModal(tableName, page = 1, limit = 20) {
    const modal = document.getElementById('tablePreviewModal');
    const title = document.getElementById('previewTableTitle');
    const content = document.getElementById('previewTableContent');
    
    title.textContent = `${tableName} 테이블 데이터`;
    content.innerHTML = '<div class="text-center py-4"><div class="text-gray-400">데이터 로딩 중...</div></div>';
    
    modal.classList.remove('hidden');
    
    try {
        const offset = (page - 1) * limit;
        const response = await fetch('experiment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=get_table_data&table_name=${tableName}&limit=${limit}&offset=${offset}`
        });
        
        const result = await response.json();
        
        if (result.error) {
            content.innerHTML = `<div class="text-red-400 text-center py-4">오류: ${result.error}</div>`;
            return;
        }
        
        currentTableData = result;
        renderTableDataContent(result);
        
    } catch (error) {
        console.error('테이블 데이터 로딩 실패:', error);
        content.innerHTML = '<div class="text-red-400 text-center py-4">데이터를 불러오는데 실패했습니다.</div>';
    }
}

// 테이블 데이터 내용 렌더링
function renderTableDataContent(tableData) {
    const content = document.getElementById('previewTableContent');
    
    if (!tableData.data || tableData.data.length === 0) {
        content.innerHTML = '<div class="text-gray-400 text-center py-4">데이터가 없습니다.</div>';
        return;
    }
    
    const { data, columns, current_page, total_pages, total_records, limit } = tableData;
    
    const tableHTML = `
        <div class="space-y-4">
            <!-- 테이블 정보 -->
            <div class="flex justify-between items-center text-sm text-gray-400">
                <span>전체 ${total_records}개 레코드</span>
                <span>페이지 ${current_page} / ${total_pages}</span>
            </div>
            
            <!-- 테이블 데이터 -->
            <div class="overflow-x-auto max-h-96">
                <table class="w-full text-sm">
                    <thead class="sticky top-0 bg-gray-800">
                        <tr class="border-b border-white/20">
                            ${columns.map(col => `<th class="text-left p-2 text-gray-300 bg-gray-800">${col}</th>`).join('')}
                        </tr>
                    </thead>
                    <tbody>
                        ${data.map((row, index) => `
                            <tr class="border-b border-white/10 hover:bg-white/5">
                                ${columns.map(col => `<td class="p-2 text-gray-400 max-w-xs truncate" title="${row[col] || ''}">${row[col] || ''}</td>`).join('')}
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
            
            <!-- 페이지네이션 -->
            <div class="flex justify-between items-center">
                <div class="flex space-x-2">
                    <button onclick="goToTablePage(1)" 
                            class="px-3 py-1 bg-gray-600 text-white rounded text-sm hover:bg-gray-500 disabled:opacity-50" 
                            ${current_page === 1 ? 'disabled' : ''}>
                        처음
                    </button>
                    <button onclick="goToTablePage(${current_page - 1})" 
                            class="px-3 py-1 bg-gray-600 text-white rounded text-sm hover:bg-gray-500 disabled:opacity-50"
                            ${current_page === 1 ? 'disabled' : ''}>
                        이전
                    </button>
                </div>
                
                <div class="flex space-x-1">
                    ${generatePageNumbers(current_page, total_pages)}
                </div>
                
                <div class="flex space-x-2">
                    <button onclick="goToTablePage(${current_page + 1})" 
                            class="px-3 py-1 bg-gray-600 text-white rounded text-sm hover:bg-gray-500 disabled:opacity-50"
                            ${current_page === total_pages ? 'disabled' : ''}>
                        다음
                    </button>
                    <button onclick="goToTablePage(${total_pages})" 
                            class="px-3 py-1 bg-gray-600 text-white rounded text-sm hover:bg-gray-500 disabled:opacity-50"
                            ${current_page === total_pages ? 'disabled' : ''}>
                        마지막
                    </button>
                </div>
            </div>
            
            <!-- 액션 버튼 -->
            <div class="flex justify-end space-x-3 pt-4 border-t border-white/20">
                <button onclick="addTableToExperiment()" 
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    실험에 추가
                </button>
                <button onclick="closeModal('tablePreviewModal')" 
                        class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                    닫기
                </button>
            </div>
        </div>
    `;
    
    content.innerHTML = tableHTML;
}

// 페이지 번호 생성
function generatePageNumbers(currentPage, totalPages) {
    const pages = [];
    const maxVisible = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
    let endPage = Math.min(totalPages, startPage + maxVisible - 1);
    
    if (endPage - startPage + 1 < maxVisible) {
        startPage = Math.max(1, endPage - maxVisible + 1);
    }
    
    for (let i = startPage; i <= endPage; i++) {
        const isActive = i === currentPage;
        pages.push(`
            <button onclick="goToTablePage(${i})" 
                    class="px-3 py-1 rounded text-sm ${isActive ? 'bg-blue-600 text-white' : 'bg-gray-600 text-white hover:bg-gray-500'}">
                ${i}
            </button>
        `);
    }
    
    return pages.join('');
}

// 테이블 페이지 이동
function goToTablePage(page) {
    if (selectedTable && currentTableData) {
        showTableDataModal(selectedTable, page, currentTableData.limit);
    }
}

// 테이블 미리보기 모달 표시 (기존 함수 유지)
function showTablePreviewModal(data, columns) {
    const modal = document.getElementById('tablePreviewModal');
    const title = document.getElementById('previewTableTitle');
    const content = document.getElementById('previewTableContent');
    
    title.textContent = `${selectedTable} 테이블 미리보기`;
    
    if (data.length === 0) {
        content.innerHTML = '<div class="text-gray-400 text-center py-4">데이터가 없습니다.</div>';
    } else {
        const tableHTML = `
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-white/20">
                            ${columns.map(col => `<th class="text-left p-2 text-gray-300">${col}</th>`).join('')}
                        </tr>
                    </thead>
                    <tbody>
                        ${data.slice(0, 10).map(row => `
                            <tr class="border-b border-white/10">
                                ${columns.map(col => `<td class="p-2 text-gray-400">${row[col] || ''}</td>`).join('')}
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
            ${data.length > 10 ? `<div class="text-xs text-gray-400 mt-2">총 ${data.length}개 중 10개만 표시</div>` : ''}
        `;
        content.innerHTML = tableHTML;
    }
    
    modal.classList.remove('hidden');
}

// 테이블을 실험에 추가
function addTableToExperiment() {
    if (!selectedTable) {
        alert('테이블을 먼저 선택해주세요.');
        return;
    }
    
    // 실험 설명에 테이블 정보 추가
    const descriptionTextarea = document.querySelector('textarea[placeholder="실험에 대한 설명을 입력하세요..."]');
    if (descriptionTextarea) {
        const currentDescription = descriptionTextarea.value;
        const tableInfo = `\n\n[추가된 DB 테이블]\n- 테이블명: ${selectedTable}\n- 레코드 수: ${dbTables.find(t => t.name === selectedTable)?.records || 'Unknown'}\n- 추가일시: ${new Date().toLocaleString()}`;
        descriptionTextarea.value = currentDescription + tableInfo;
    }
    
    alert(`${selectedTable} 테이블이 실험에 추가되었습니다.`);
}

// DB 정보 보기
async function showDBInfo() {
    try {
        const response = await fetch('experiment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_db_stats'
        });
        
        const result = await response.json();
        
        if (result.error) {
            alert('DB 정보 조회 오류: ' + result.error);
            return;
        }
        
        showDBInfoModal(result);
        
    } catch (error) {
        console.error('DB 정보 조회 실패:', error);
        alert('DB 정보를 불러오는데 실패했습니다.');
    }
}

// DB 정보 모달 표시
function showDBInfoModal(dbInfo) {
    const modal = document.getElementById('dbInfoModal');
    const content = document.getElementById('dbInfoContent');
    
    const infoHTML = `
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-white/5 p-3 rounded">
                    <div class="text-sm text-gray-400">데이터베이스</div>
                    <div class="text-lg font-medium">${dbInfo.database_name || 'mathking'}</div>
                </div>
                <div class="bg-white/5 p-3 rounded">
                    <div class="text-sm text-gray-400">테이블 수</div>
                    <div class="text-lg font-medium">${dbInfo.table_count || 0}개</div>
                </div>
                <div class="bg-white/5 p-3 rounded">
                    <div class="text-sm text-gray-400">전체 레코드</div>
                    <div class="text-lg font-medium">${dbInfo.total_records || 0}개</div>
                </div>
                <div class="bg-white/5 p-3 rounded">
                    <div class="text-sm text-gray-400">DB 크기</div>
                    <div class="text-lg font-medium">${dbInfo.total_size || 'Unknown'}</div>
                </div>
            </div>
            ${dbInfo.version ? `
                <div class="bg-white/5 p-3 rounded">
                    <div class="text-sm text-gray-400">MySQL 버전</div>
                    <div class="text-lg font-medium">${dbInfo.version}</div>
                </div>
            ` : ''}
        </div>
    `;
    
    content.innerHTML = infoHTML;
    modal.classList.remove('hidden');
}

// 테이블 검색
function searchTables() {
    const searchTerm = document.getElementById('tableSearchInput').value.toLowerCase();
    
    if (!searchTerm) {
        renderDBTables();
        return;
    }
    
    const filteredTables = dbTables.filter(table => 
        table.name.toLowerCase().includes(searchTerm)
    );
    
    const tablesList = document.getElementById('dbTablesList');
    if (filteredTables.length === 0) {
        tablesList.innerHTML = '<div class="text-gray-400 text-center py-4">검색 결과가 없습니다.</div>';
        return;
    }
    
    tablesList.innerHTML = filteredTables.map(table => `
        <div class="bg-white/5 border border-white/20 rounded-lg p-3 cursor-pointer hover:bg-white/10 transition-all"
             onclick="viewTableData('${table.name}', this)">
            <div class="flex justify-between items-start mb-2">
                <h4 class="font-medium text-white">${table.name}</h4>
                <span class="text-xs text-gray-400">${table.records}개</span>
            </div>
            <div class="text-xs text-gray-400 space-y-1">
                <div>엔진: ${table.engine}</div>
                <div>크기: ${table.size}</div>
                ${table.last_update ? `<div>수정: ${table.last_update}</div>` : ''}
            </div>
        </div>
    `).join('');
}

// DB 테이블 모달 표시
async function showDBTablesModal(page = 1) {
    const modal = document.getElementById('dbTablesModal');
    const tablesList = document.getElementById('modalDbTablesList');
    
    currentModalPage = page;
    modal.classList.remove('hidden');
    tablesList.innerHTML = '<div class="text-gray-400 text-center py-4">테이블 목록을 로딩중...</div>';
    
    await loadDBTablesPage(page);
}

// DB 테이블 페이지 로딩
async function loadDBTablesPage(page = 1, search = '') {
    const tablesList = document.getElementById('modalDbTablesList');
    
    try {
        const response = await fetch('experiment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=get_db_tables&page=${page}&limit=50&search=${encodeURIComponent(search)}`
        });
        
        const result = await response.json();
        
        if (result.error) {
            tablesList.innerHTML = `<div class="text-red-400 text-center py-4">오류: ${result.error}</div>`;
            return;
        }
        
        dbTables = result.tables || [];
        modalPagination = result.pagination || null;
        currentModalPage = page;
        
        renderModalTablesList();
        renderModalPagination();
        
    } catch (error) {
        console.error('DB 테이블 목록 조회 실패:', error);
        tablesList.innerHTML = `<div class="text-red-400 text-center py-4">오류: ${error.message}</div>`;
    }
}

// 모달 테이블 목록 렌더링
function renderModalTablesList() {
    const tablesList = document.getElementById('modalDbTablesList');
    const tablesInfo = document.getElementById('modalTablesInfo');
    
    if (dbTables.length === 0) {
        tablesList.innerHTML = '<div class="text-gray-400 text-center py-4">테이블이 없습니다.</div>';
        tablesInfo.textContent = '테이블 없음';
        return;
    }
    
    tablesList.innerHTML = dbTables.map(table => `
        <div class="bg-white/5 border border-white/20 rounded-lg p-3 cursor-pointer hover:bg-white/10 transition-all"
             onclick="selectTableFromModal('${table.name}')">
            <div class="flex justify-between items-start mb-2">
                <h4 class="font-medium text-white">${table.name}</h4>
                <span class="text-xs text-gray-400">${table.records}개</span>
            </div>
            <div class="text-xs text-gray-400 space-y-1">
                <div>엔진: ${table.engine}</div>
                <div>크기: ${table.size}</div>
                ${table.last_update ? `<div>수정: ${table.last_update}</div>` : ''}
            </div>
        </div>
    `).join('');
    
    // 페이지네이션 정보 표시
    if (modalPagination) {
        const { current_page, total_pages, total_tables, limit } = modalPagination;
        const startItem = ((current_page - 1) * limit) + 1;
        const endItem = Math.min(current_page * limit, total_tables);
        tablesInfo.textContent = `${startItem}-${endItem} / 총 ${total_tables}개 테이블 (페이지 ${current_page}/${total_pages})`;
    } else {
        tablesInfo.textContent = `총 ${dbTables.length}개 테이블`;
    }
}

// 모달 페이지네이션 렌더링
function renderModalPagination() {
    const paginationContainer = document.getElementById('modalPagination');
    
    if (!modalPagination || modalPagination.total_pages <= 1) {
        paginationContainer.innerHTML = '';
        return;
    }
    
    const { current_page, total_pages } = modalPagination;
    const maxVisible = 5;
    let startPage = Math.max(1, current_page - Math.floor(maxVisible / 2));
    let endPage = Math.min(total_pages, startPage + maxVisible - 1);
    
    if (endPage - startPage + 1 < maxVisible) {
        startPage = Math.max(1, endPage - maxVisible + 1);
    }
    
    let paginationHTML = '';
    
    // 처음/이전 버튼
    paginationHTML += `
        <button onclick="goToModalPage(1)" 
                class="px-2 py-1 bg-gray-600 text-white rounded text-xs hover:bg-gray-500 disabled:opacity-50"
                ${current_page === 1 ? 'disabled' : ''}>
            처음
        </button>
        <button onclick="goToModalPage(${current_page - 1})" 
                class="px-2 py-1 bg-gray-600 text-white rounded text-xs hover:bg-gray-500 disabled:opacity-50"
                ${current_page === 1 ? 'disabled' : ''}>
            이전
        </button>
    `;
    
    // 페이지 번호
    for (let i = startPage; i <= endPage; i++) {
        const isActive = i === current_page;
        paginationHTML += `
            <button onclick="goToModalPage(${i})" 
                    class="px-2 py-1 rounded text-xs ${isActive ? 'bg-blue-600 text-white' : 'bg-gray-600 text-white hover:bg-gray-500'}">
                ${i}
            </button>
        `;
    }
    
    // 다음/마지막 버튼
    paginationHTML += `
        <button onclick="goToModalPage(${current_page + 1})" 
                class="px-2 py-1 bg-gray-600 text-white rounded text-xs hover:bg-gray-500 disabled:opacity-50"
                ${current_page === total_pages ? 'disabled' : ''}>
            다음
        </button>
        <button onclick="goToModalPage(${total_pages})" 
                class="px-2 py-1 bg-gray-600 text-white rounded text-xs hover:bg-gray-500 disabled:opacity-50"
                ${current_page === total_pages ? 'disabled' : ''}>
            마지막
        </button>
    `;
    
    paginationContainer.innerHTML = paginationHTML;
}

// 모달 페이지 이동
function goToModalPage(page) {
    if (page < 1 || (modalPagination && page > modalPagination.total_pages)) {
        return;
    }
    
    const searchTerm = document.getElementById('modalTableSearchInput').value;
    loadDBTablesPage(page, searchTerm);
}

// 모달에서 테이블 선택
async function selectTableFromModal(tableName) {
    selectedTable = tableName;
    
    // 모달 닫기
    closeModal('dbTablesModal');
    
    // 선택된 테이블 정보 표시
    showSelectedTableInfo(tableName);
    
    // 필드 정보 가져오기
    await loadTableFields(tableName);
}

// 선택된 테이블 정보 표시
function showSelectedTableInfo(tableName) {
    const selectedInfo = document.getElementById('selectedTableInfo');
    const tableNameEl = document.getElementById('selectedTableName');
    const tableDetailsEl = document.getElementById('selectedTableDetails');
    const initialState = document.getElementById('initialState');
    
    const tableInfo = dbTables.find(t => t.name === tableName);
    
    tableNameEl.textContent = tableName;
    tableDetailsEl.textContent = tableInfo ? `${tableInfo.records}개 레코드, ${tableInfo.size}` : '';
    
    selectedInfo.style.display = 'block';
    initialState.style.display = 'none';
}

// 테이블 필드 로딩
async function loadTableFields(tableName) {
    try {
        const response = await fetch('experiment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=get_table_fields&table_name=${tableName}`
        });
        
        const result = await response.json();
        
        if (result.error) {
            alert('필드 정보 로딩 실패: ' + result.error);
            return;
        }
        
        selectedTableFields = result.fields || [];
        renderFieldsList();
        showFieldsSection();
        
    } catch (error) {
        console.error('필드 정보 로딩 실패:', error);
        alert('필드 정보를 불러오는데 실패했습니다.');
    }
}

// 필드 목록 렌더링
function renderFieldsList() {
    const fieldsList = document.getElementById('fieldsList');
    
    fieldsList.innerHTML = selectedTableFields.map(field => `
        <div class="bg-white/5 border border-white/20 rounded-lg p-2 cursor-pointer hover:bg-white/10 transition-all"
             onclick="addFieldCondition('${field.name}', '${field.type}')">
            <div class="flex justify-between items-center">
                <span class="font-medium text-white">${field.name}</span>
                <span class="text-xs text-gray-400">${field.type}</span>
            </div>
            ${field.key ? `<div class="text-xs text-yellow-400">${field.key}</div>` : ''}
        </div>
    `).join('');
}

// 필드 섹션 표시
function showFieldsSection() {
    document.getElementById('fieldsSection').style.display = 'block';
}

// 필드 조건 추가
function addFieldCondition(fieldName, fieldType) {
    const conditionId = `condition_${++conditionCounter}`;
    
    const condition = {
        id: conditionId,
        field: fieldName,
        type: fieldType,
        operator: '=',
        value: '',
        connector: 'AND'
    };
    
    conditions.push(condition);
    renderConditionsList();
    showConditionsSection();
    updateSQLPreview();
}

// 조건 추가 (빈 조건)
function addCondition() {
    if (selectedTableFields.length === 0) {
        alert('먼저 테이블을 선택해주세요.');
        return;
    }
    
    const conditionId = `condition_${++conditionCounter}`;
    
    const condition = {
        id: conditionId,
        field: selectedTableFields[0].name,
        type: selectedTableFields[0].type,
        operator: '=',
        value: '',
        connector: 'AND'
    };
    
    conditions.push(condition);
    renderConditionsList();
    showConditionsSection();
    updateSQLPreview();
}

// 조건 목록 렌더링
function renderConditionsList() {
    const conditionsList = document.getElementById('conditionsList');
    
    conditionsList.innerHTML = conditions.map((condition, index) => `
        <div class="bg-white/5 border border-white/20 rounded-lg p-3">
            <div class="grid grid-cols-12 gap-2 items-center">
                ${index > 0 ? `
                    <select class="col-span-2 px-2 py-1 bg-gray-700 border border-white/20 rounded text-xs"
                            onchange="updateCondition('${condition.id}', 'connector', this.value)">
                        <option value="AND" ${condition.connector === 'AND' ? 'selected' : ''}>AND</option>
                        <option value="OR" ${condition.connector === 'OR' ? 'selected' : ''}>OR</option>
                    </select>
                ` : '<div class="col-span-2"></div>'}
                
                <select class="col-span-3 px-2 py-1 bg-gray-700 border border-white/20 rounded text-xs"
                        onchange="updateCondition('${condition.id}', 'field', this.value)">
                    ${selectedTableFields.map(field => 
                        `<option value="${field.name}" ${condition.field === field.name ? 'selected' : ''}>${field.name}</option>`
                    ).join('')}
                </select>
                
                <select class="col-span-2 px-2 py-1 bg-gray-700 border border-white/20 rounded text-xs"
                        onchange="updateCondition('${condition.id}', 'operator', this.value)">
                    <option value="=" ${condition.operator === '=' ? 'selected' : ''}>=</option>
                    <option value="!=" ${condition.operator === '!=' ? 'selected' : ''}>!=</option>
                    <option value=">" ${condition.operator === '>' ? 'selected' : ''}>&gt;</option>
                    <option value="<" ${condition.operator === '<' ? 'selected' : ''}>&lt;</option>
                    <option value=">=" ${condition.operator === '>=' ? 'selected' : ''}>&gt;=</option>
                    <option value="<=" ${condition.operator === '<=' ? 'selected' : ''}>&lt;=</option>
                    <option value="LIKE" ${condition.operator === 'LIKE' ? 'selected' : ''}>LIKE</option>
                    <option value="NOT LIKE" ${condition.operator === 'NOT LIKE' ? 'selected' : ''}>NOT LIKE</option>
                </select>
                
                <input type="text" 
                       class="col-span-4 px-2 py-1 bg-gray-700 border border-white/20 rounded text-xs"
                       placeholder="값 입력..."
                       value="${condition.value}"
                       onchange="updateCondition('${condition.id}', 'value', this.value)">
                
                <button onclick="removeCondition('${condition.id}')"
                        class="col-span-1 px-2 py-1 bg-red-500 hover:bg-red-600 rounded text-xs">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `).join('');
}

// 조건 업데이트
function updateCondition(conditionId, property, value) {
    const condition = conditions.find(c => c.id === conditionId);
    if (condition) {
        condition[property] = value;
        updateSQLPreview();
    }
}

// 조건 제거
function removeCondition(conditionId) {
    conditions = conditions.filter(c => c.id !== conditionId);
    renderConditionsList();
    updateSQLPreview();
    
    if (conditions.length === 0) {
        document.getElementById('conditionsSection').style.display = 'none';
    }
}

// 조건 섹션 표시
function showConditionsSection() {
    document.getElementById('conditionsSection').style.display = 'block';
}

// SQL 미리보기 업데이트
function updateSQLPreview() {
    const sqlPreview = document.getElementById('sqlPreview');
    
    if (!selectedTable || conditions.length === 0) {
        sqlPreview.textContent = `SELECT * FROM ${selectedTable || 'table_name'}`;
        return;
    }
    
    let whereClause = conditions.map((condition, index) => {
        let clause = '';
        if (index > 0) {
            clause += ` ${condition.connector} `;
        }
        
        let value = condition.value;
        if (condition.operator === 'LIKE' || condition.operator === 'NOT LIKE') {
            value = `'%${value}%'`;
        } else if (isNaN(value) && value !== '') {
            value = `'${value}'`;
        }
        
        clause += `${condition.field} ${condition.operator} ${value || '?'}`;
        return clause;
    }).join('');
    
    sqlPreview.textContent = `SELECT * FROM ${selectedTable} WHERE ${whereClause}`;
}

// 선택된 테이블 초기화
function clearSelectedTable() {
    selectedTable = null;
    selectedTableFields = [];
    conditions = [];
    
    document.getElementById('selectedTableInfo').style.display = 'none';
    document.getElementById('fieldsSection').style.display = 'none';
    document.getElementById('conditionsSection').style.display = 'none';
    document.getElementById('initialState').style.display = 'block';
}

// 쿼리 실행
async function executeQuery() {
    if (!selectedTable || conditions.length === 0) {
        alert('테이블과 조건을 선택해주세요.');
        return;
    }
    
    // SQL 쿼리를 실행하는 대신 결과 미리보기 모달 표시
    showTableDataModal(selectedTable, 1, 20);
}

// 쿼리 저장
function saveQuery() {
    if (!selectedTable || conditions.length === 0) {
        alert('테이블과 조건을 선택해주세요.');
        return;
    }
    
    const queryData = {
        table: selectedTable,
        conditions: conditions,
        sql: document.getElementById('sqlPreview').textContent,
        created_at: new Date().toLocaleString()
    };
    
    // 실험 설명에 쿼리 정보 추가
    const descriptionTextarea = document.querySelector('textarea[placeholder="실험에 대한 설명을 입력하세요..."]');
    if (descriptionTextarea) {
        const currentDescription = descriptionTextarea.value;
        const queryInfo = `\n\n[저장된 DB 쿼리]\n- 테이블: ${selectedTable}\n- 조건: ${conditions.length}개\n- SQL: ${queryData.sql}\n- 저장일시: ${queryData.created_at}`;
        descriptionTextarea.value = currentDescription + queryInfo;
    }
    
    alert('쿼리가 실험에 저장되었습니다.');
}

// 모달 테이블 검색
function searchModalTables() {
    const searchTerm = document.getElementById('modalTableSearchInput').value;
    
    // 검색어가 변경되면 첫 번째 페이지로 이동하며 검색
    loadDBTablesPage(1, searchTerm);
}

// 모달 닫기
function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

// 전역 함수들 (HTML에서 호출)
window.toggleTrackingConfig = toggleTrackingConfig;
window.deleteTrackingConfig = deleteTrackingConfig;
window.showResultDetail = showResultDetail;
window.startMonitoring = startMonitoring;
window.startExperimentFromHypothesis = startExperimentFromHypothesis;
window.refreshDBTables = refreshDBTables;
window.testDBConnection = testDBConnection;
window.selectTable = selectTable;
window.viewTableData = viewTableData;
window.previewTable = previewTable;
window.addTableToExperiment = addTableToExperiment;
window.showDBInfo = showDBInfo;
window.searchTables = searchTables;
window.closeModal = closeModal;
window.goToTablePage = goToTablePage;
window.showTableDataModal = showTableDataModal;
window.showDBTablesModal = showDBTablesModal;
window.selectTableFromModal = selectTableFromModal;
window.clearSelectedTable = clearSelectedTable;
window.addFieldCondition = addFieldCondition;
window.addCondition = addCondition;
window.updateCondition = updateCondition;
window.removeCondition = removeCondition;
window.executeQuery = executeQuery;
window.saveQuery = saveQuery;
window.searchModalTables = searchModalTables;
window.goToModalPage = goToModalPage;
window.loadDBTablesPage = loadDBTablesPage;