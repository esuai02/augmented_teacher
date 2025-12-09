/**
 * Main Application JavaScript
 * 메인 애플리케이션 기능들
 */

// 전역 변수들
let currentExperiment = {};
let selectedStudents = [];
let trackingConfigs = [];
let hypotheses = [];
let experimentResults = [];
let feedbackMethods = {
    metacognitive: [],
    learning: [],
    combined: []
};

// 대시보드 데이터 업데이트
function updateDashboard() {
    const activeExperiments = experimentResults.filter(exp => exp.status === 'active').length;
    const completedExperiments = experimentResults.filter(exp => exp.status === 'completed').length;
    const totalParticipants = selectedStudents.length;
    const dataCollected = experimentResults.length;
    
    document.getElementById('dashboard-active-experiments').textContent = activeExperiments;
    document.getElementById('dashboard-completed-experiments').textContent = completedExperiments;
    document.getElementById('dashboard-total-participants').textContent = totalParticipants;
    document.getElementById('dashboard-data-collected').textContent = dataCollected;
    
    // 최근 활동 업데이트
    updateRecentActivities();
    
    // 알림 업데이트
    updateNotifications();
}

// 최근 활동 업데이트
function updateRecentActivities() {
    const recentActivities = document.getElementById('recent-activities');
    const activities = [
        { time: '10분 전', action: '실험 설정이 저장되었습니다.' },
        { time: '1시간 전', action: '새로운 가설이 추가되었습니다.' },
        { time: '2시간 전', action: '학생 그룹이 배정되었습니다.' }
    ];
    
    if (activities.length === 0) {
        recentActivities.innerHTML = '<div class="text-sm text-gray-300">활동 내역이 없습니다.</div>';
        return;
    }
    
    recentActivities.innerHTML = activities.map(activity => `
        <div class="text-xs p-2 bg-white/5 rounded border border-white/10">
            <div class="text-gray-400">${activity.time}</div>
            <div class="text-gray-300">${activity.action}</div>
        </div>
    `).join('');
}

// 알림 업데이트
function updateNotifications() {
    const notifications = document.getElementById('notifications');
    const alerts = [
        { type: 'warning', message: '실험 종료일이 3일 남았습니다.' },
        { type: 'info', message: '새로운 데이터가 수집되었습니다.' }
    ];
    
    if (alerts.length === 0) {
        notifications.innerHTML = '<div class="text-sm text-gray-300">새로운 알림이 없습니다.</div>';
        return;
    }
    
    notifications.innerHTML = alerts.map(alert => `
        <div class="text-xs p-2 bg-white/5 rounded border border-white/10">
            <div class="text-${alert.type === 'warning' ? 'orange' : 'blue'}-400">${alert.message}</div>
        </div>
    `).join('');
}

// 실험 설정 저장
async function saveExperimentConfig() {
    const form = document.getElementById('experiment-config-form');
    const formData = new FormData(form);
    
    currentExperiment = {
        name: formData.get('experiment-name'),
        description: formData.get('experiment-description'),
        startDate: formData.get('start-date'),
        duration: formData.get('duration')
    };
    
    // 실험 관리자에 실험 생성
    if (window.experimentManager && currentExperiment.name) {
        try {
            const experimentData = {
                name: currentExperiment.name,
                description: currentExperiment.description,
                startDate: currentExperiment.startDate,
                durationWeeks: parseInt(currentExperiment.duration) || 8,
                status: 'planned',
                createdBy: window.USER_ID || 1  // 현재 사용자 ID
            };
            
            console.log('실험 생성 시도:', experimentData);
            
            const result = await window.experimentManager.createExperiment(experimentData);
            console.log('실험 생성 성공:', result);
            
            // 실험 ID 저장
            currentExperiment.id = result.experiment_id;
            
            // 대시보드 업데이트
            updateDashboard();
            
            alert('실험이 성공적으로 생성되었습니다! (ID: ' + result.experiment_id + ')');
            
        } catch (error) {
            console.error('실험 생성 실패:', error);
            alert('실험 생성에 실패했습니다: ' + error.message);
        }
    } else {
        console.warn('실험 관리자가 없거나 실험명이 비어있습니다.', {
            hasExperimentManager: !!window.experimentManager,
            experimentName: currentExperiment.name
        });
    }
    
    // 헤더 업데이트
    const titleElement = document.getElementById('experiment-title');
    if (titleElement) {
        titleElement.textContent = currentExperiment.name || '실험명을 설정해주세요';
    }
    
    // 푸터 업데이트
    const footerDuration = document.getElementById('footer-duration');
    if (footerDuration) {
        footerDuration.textContent = currentExperiment.duration || '8';
    }
    
    alert('실험 설정이 저장되었습니다.');
    updateDashboard();
}

// 피드백 방법 선택
function selectFeedbackMethod(type) {
    const modal = document.getElementById('feedback-modal');
    const modalTitle = document.getElementById('feedback-modal-title');
    const feedbackList = document.getElementById('feedback-list');
    
    modalTitle.textContent = `${type === 'metacognitive' ? '메타인지' : type === 'learning' ? '학습인지' : '결합형'} 피드백 선택`;
    
    const feedbackOptions = [
        { id: 1, name: '자기점검 질문', description: '학습 진행 상황을 스스로 점검하도록 유도' },
        { id: 2, name: '조건 확인', description: '문제 해결 조건을 다시 확인하도록 안내' },
        { id: 3, name: '전략 평가', description: '사용한 학습 전략의 효과성을 평가' },
        { id: 4, name: '목표 재설정', description: '학습 목표를 재검토하고 조정' },
        { id: 5, name: '진행 모니터링', description: '학습 진행 과정을 실시간으로 모니터링' }
    ];
    
    feedbackList.innerHTML = feedbackOptions.map(option => `
        <div class="flex items-center p-3 bg-white/5 border border-white/20 rounded-lg cursor-pointer hover:bg-white/10"
             onclick="toggleFeedbackOption('${type}', ${option.id})">
            <input type="checkbox" id="feedback-${option.id}" class="mr-3">
            <div class="flex-1">
                <div class="font-medium">${option.name}</div>
                <div class="text-sm text-gray-400">${option.description}</div>
            </div>
        </div>
    `).join('');
    
    modal.classList.remove('hidden');
}

// 피드백 옵션 토글
function toggleFeedbackOption(type, optionId) {
    const checkbox = document.getElementById(`feedback-${optionId}`);
    checkbox.checked = !checkbox.checked;
    
    if (checkbox.checked) {
        if (!feedbackMethods[type].includes(optionId)) {
            feedbackMethods[type].push(optionId);
        }
    } else {
        feedbackMethods[type] = feedbackMethods[type].filter(id => id !== optionId);
    }
    
    updateFeedbackCounts();
}

// 피드백 카운트 업데이트
function updateFeedbackCounts() {
    document.getElementById('metacognitive-count').textContent = `선택됨: ${feedbackMethods.metacognitive.length}개`;
    document.getElementById('learning-count').textContent = `선택됨: ${feedbackMethods.learning.length}개`;
    document.getElementById('combined-count').textContent = `선택됨: ${feedbackMethods.combined.length}개`;
    
    const totalFeedback = feedbackMethods.metacognitive.length + feedbackMethods.learning.length + feedbackMethods.combined.length;
    const footerFeedback = document.getElementById('footer-feedback');
    if (footerFeedback) {
        footerFeedback.textContent = totalFeedback;
    }
}

// 선생님 학생 로딩
function loadTeacherStudents() {
    const teacherSelect = document.getElementById('teacher-select');
    const selectedTeacher = teacherSelect.value;
    
    if (!selectedTeacher) {
        document.getElementById('group-assignment-container').style.display = 'none';
        return;
    }
    
    // 가짜 학생 데이터 생성
    const students = Array.from({ length: 30 }, (_, i) => ({
        id: i + 1,
        name: `학생${i + 1}`,
        grade: Math.floor(Math.random() * 3) + 1,
        class: Math.floor(Math.random() * 5) + 1,
        performance: ['상', '중', '하'][Math.floor(Math.random() * 3)]
    }));
    
    renderStudentsList(students);
    document.getElementById('group-assignment-container').style.display = 'grid';
    document.getElementById('teacher-students-title').textContent = `${selectedTeacher} 학생 목록`;
}

// 학생 목록 렌더링
function renderStudentsList(students) {
    const availableStudents = document.getElementById('available-students');
    
    availableStudents.innerHTML = students.map(student => `
        <div class="student-item p-3 border border-white/20 rounded-lg cursor-pointer hover:bg-white/10 ${selectedStudents.includes(student.id) ? 'bg-purple-500/20 border-purple-400' : 'bg-white/5'}"
             onclick="toggleStudentSelection(${student.id})">
            <div class="student-name font-medium">${student.name}</div>
            <div class="student-details text-xs text-gray-400">${student.grade}학년 ${student.class}반 - 성취도: ${student.performance}</div>
        </div>
    `).join('');
}

// 학생 선택 토글
function toggleStudentSelection(studentId) {
    if (selectedStudents.includes(studentId)) {
        selectedStudents = selectedStudents.filter(id => id !== studentId);
    } else {
        selectedStudents.push(studentId);
    }
    
    // 버튼 상태 업데이트
    const addToControlBtn = document.getElementById('add-to-control');
    const addToExperimentBtn = document.getElementById('add-to-experiment');
    
    if (selectedStudents.length > 0) {
        addToControlBtn.disabled = false;
        addToExperimentBtn.disabled = false;
    } else {
        addToControlBtn.disabled = true;
        addToExperimentBtn.disabled = true;
    }
    
    // 학생 목록 다시 렌더링
    loadTeacherStudents();
}

// 통제 그룹에 추가
function addToControlGroup() {
    const controlGroup = document.getElementById('control-group');
    const controlCount = document.getElementById('control-group-count');
    
    selectedStudents.forEach(studentId => {
        const studentDiv = document.createElement('div');
        studentDiv.className = 'p-2 bg-gray-500/20 border border-gray-400 rounded text-sm';
        studentDiv.innerHTML = `
            <div class="flex items-center justify-between">
                <span>학생${studentId}</span>
                <button onclick="removeFromGroup(${studentId}, 'control')" class="text-red-400 hover:text-red-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        controlGroup.appendChild(studentDiv);
    });
    
    controlCount.textContent = controlGroup.children.length;
    selectedStudents = [];
    updateDashboard();
}

// 실험 그룹에 추가
function addToExperimentGroup() {
    const experimentGroup = document.getElementById('experiment-group');
    const experimentCount = document.getElementById('experiment-group-count');
    
    selectedStudents.forEach(studentId => {
        const studentDiv = document.createElement('div');
        studentDiv.className = 'p-2 bg-green-500/20 border border-green-400 rounded text-sm';
        studentDiv.innerHTML = `
            <div class="flex items-center justify-between">
                <span>학생${studentId}</span>
                <button onclick="removeFromGroup(${studentId}, 'experiment')" class="text-red-400 hover:text-red-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        experimentGroup.appendChild(studentDiv);
    });
    
    experimentCount.textContent = experimentGroup.children.length;
    selectedStudents = [];
    updateDashboard();
}

// 그룹에서 제거
function removeFromGroup(studentId, groupType) {
    const group = document.getElementById(groupType + '-group');
    const count = document.getElementById(groupType + '-group-count');
    
    const studentElements = group.querySelectorAll('div');
    studentElements.forEach(element => {
        if (element.textContent.includes(`학생${studentId}`)) {
            element.remove();
        }
    });
    
    count.textContent = group.children.length;
    updateDashboard();
}

// 추적 설정 모달 표시
function showTrackingModal() {
    const modal = document.getElementById('tracking-modal');
    modal.classList.remove('hidden');
}

// 추적 설정 추가
function addTrackingConfig() {
    const name = document.getElementById('tracking-name').value;
    const description = document.getElementById('tracking-description').value;
    
    if (!name || !description) {
        alert('모든 필드를 입력해주세요.');
        return;
    }
    
    const config = {
        id: Date.now(),
        name: name,
        description: description,
        active: true,
        created: new Date().toLocaleString()
    };
    
    trackingConfigs.push(config);
    renderTrackingConfigs();
    
    // 폼 초기화
    document.getElementById('tracking-name').value = '';
    document.getElementById('tracking-description').value = '';
    
    // 모달 닫기
    closeModal('tracking-modal');
    
    updateDashboard();
}

// 추적 설정 렌더링
function renderTrackingConfigs() {
    const list = document.getElementById('tracking-config-list');
    
    if (trackingConfigs.length === 0) {
        list.innerHTML = '<div class="text-gray-400 text-center py-4">추적 설정이 없습니다.</div>';
        return;
    }
    
    list.innerHTML = trackingConfigs.map(config => `
        <div class="tracking-config p-4 border border-white/20 rounded-lg ${config.active ? 'bg-green-500/10 border-green-400/30' : 'bg-white/5'}">
            <div class="flex items-center justify-between mb-2">
                <h4 class="font-medium">${config.name}</h4>
                <div class="flex items-center space-x-2">
                    <button onclick="connectDBToTracking(${config.id})" 
                            class="text-xs px-2 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded" 
                            title="DB 연결">
                        <i class="fas fa-database"></i>
                    </button>
                    <button onclick="toggleTrackingConfig(${config.id})" 
                            class="text-xs px-2 py-1 rounded ${config.active ? 'bg-green-500 text-white' : 'bg-gray-500 text-white'}">
                        ${config.active ? '활성' : '비활성'}
                    </button>
                    <button onclick="removeTrackingConfig(${config.id})" class="text-red-400 hover:text-red-300">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <p class="text-sm text-gray-300 mb-2">${config.description}</p>
            <div class="text-xs text-gray-400 mb-2">생성: ${config.created}</div>
            
            <!-- DB 연결 상태 표시 -->
            <div class="db-connection-status" id="db-status-${config.id}">
                ${config.connectedDB ? `
                    <div class="text-xs bg-green-500/20 text-green-300 px-2 py-1 rounded">
                        <i class="fas fa-check-circle mr-1"></i>
                        DB 연결됨: ${config.connectedDB.table}
                    </div>
                ` : `
                    <div class="text-xs bg-gray-500/20 text-gray-400 px-2 py-1 rounded">
                        <i class="fas fa-exclamation-circle mr-1"></i>
                        DB 연결 필요
                    </div>
                `}
            </div>
        </div>
    `).join('');
    
    // 푸터 업데이트
    const activeCount = trackingConfigs.filter(c => c.active).length;
    const footerTracking = document.getElementById('footer-tracking');
    if (footerTracking) {
        footerTracking.textContent = activeCount;
    }
}

// 추적 설정 토글
function toggleTrackingConfig(configId) {
    const config = trackingConfigs.find(c => c.id === configId);
    if (config) {
        config.active = !config.active;
        renderTrackingConfigs();
    }
}

// 추적 설정 제거
function removeTrackingConfig(configId) {
    if (confirm('이 추적 설정을 삭제하시겠습니까?')) {
        trackingConfigs = trackingConfigs.filter(c => c.id !== configId);
        renderTrackingConfigs();
    }
}

// 설문 모달 표시
function showSurveyModal() {
    const modal = document.getElementById('survey-modal');
    modal.classList.remove('hidden');
}

// 분석 모달 표시
function showAnalysisModal() {
    const modal = document.getElementById('analysis-modal');
    modal.classList.remove('hidden');
}

// 가설 추가
async function addHypothesis() {
    const textarea = document.getElementById('new-hypothesis');
    const hypothesis = textarea.value.trim();
    
    if (!hypothesis) {
        alert('가설을 입력해주세요.');
        return;
    }
    
    const newHypothesis = {
        id: Date.now(),
        text: hypothesis,
        category: '예측',
        created: new Date().toLocaleString()
    };
    
    // 실험 관리자에 가설 저장
    if (window.experimentManager && window.experimentManager.experimentId) {
        try {
            console.log('가설 저장 시도:', {
                experimentId: window.experimentManager.experimentId,
                hypothesis: hypothesis,
                authorId: window.USER_ID || 1
            });
            
            const result = await window.saveHypothesis(
                window.experimentManager.experimentId,
                hypothesis,
                { type: 'primary', authorId: window.USER_ID || 1 }
            );
            
            console.log('가설 저장 성공:', result);
            newHypothesis.id = result.hypothesis_id;
            
        } catch (error) {
            console.error('가설 저장 실패:', error);
            alert('가설 저장에 실패했습니다: ' + error.message);
            return;
        }
    } else {
        console.warn('실험 관리자가 없거나 실험 ID가 없습니다.', {
            hasExperimentManager: !!window.experimentManager,
            experimentId: window.experimentManager?.experimentId
        });
        
        // 실험이 없으면 기본 실험 자동 생성
        if (!window.experimentManager || !window.experimentManager.experimentId) {
            try {
                console.log('기본 실험 자동 생성 시도...');
                
                const defaultExperiment = {
                    name: '기본 실험 - ' + new Date().toLocaleDateString(),
                    description: '가설 추가를 위해 자동 생성된 기본 실험입니다.',
                    startDate: new Date().toISOString().split('T')[0],
                    durationWeeks: 8,
                    status: 'planned',
                    createdBy: window.USER_ID || 1
                };
                
                const experimentResult = await window.experimentManager.createExperiment(defaultExperiment);
                console.log('기본 실험 생성 성공:', experimentResult);
                
                // 이제 가설 저장 시도
                const result = await window.saveHypothesis(
                    window.experimentManager.experimentId,
                    hypothesis,
                    { type: 'primary', authorId: window.USER_ID || 1 }
                );
                
                console.log('가설 저장 성공:', result);
                newHypothesis.id = result.hypothesis_id;
                
                alert('기본 실험이 자동으로 생성되어 가설이 저장되었습니다.');
                
            } catch (error) {
                console.error('기본 실험 생성 또는 가설 저장 실패:', error);
                alert('실험 생성 또는 가설 저장에 실패했습니다: ' + error.message);
                return;
            }
        }
    }
    
    hypotheses.push(newHypothesis);
    renderHypotheses();
    
    textarea.value = '';
    updateDashboard();
}

// 가설 렌더링
function renderHypotheses() {
    const list = document.getElementById('hypotheses-list');
    
    if (hypotheses.length === 0) {
        list.innerHTML = '<div class="text-gray-400 text-center py-4">가설이 없습니다.</div>';
        return;
    }
    
    list.innerHTML = hypotheses.map(hypothesis => `
        <div class="hypothesis-item p-3 bg-white/5 border border-white/20 rounded-lg">
            <div class="hypothesis-text text-sm mb-2">${hypothesis.text}</div>
            <div class="hypothesis-meta flex items-center justify-between">
                <span class="hypothesis-category bg-purple-500/20 text-purple-300 px-2 py-1 rounded text-xs">${hypothesis.category}</span>
                <span class="text-xs text-gray-400">${hypothesis.created}</span>
            </div>
            <div class="hypothesis-actions flex space-x-2 mt-2">
                <button onclick="editHypothesis(${hypothesis.id})" class="text-xs px-2 py-1 bg-blue-500 text-white rounded">
                    <i class="fas fa-edit"></i> 수정
                </button>
                <button onclick="removeHypothesis(${hypothesis.id})" class="text-xs px-2 py-1 bg-red-500 text-white rounded">
                    <i class="fas fa-trash"></i> 삭제
                </button>
            </div>
        </div>
    `).join('');
}

// 가설 제거
function removeHypothesis(hypothesisId) {
    if (confirm('이 가설을 삭제하시겠습니까?')) {
        hypotheses = hypotheses.filter(h => h.id !== hypothesisId);
        renderHypotheses();
    }
}

// 가설 수정
function editHypothesis(hypothesisId) {
    const hypothesis = hypotheses.find(h => h.id === hypothesisId);
    if (hypothesis) {
        const newText = prompt('가설을 수정하세요:', hypothesis.text);
        if (newText && newText.trim()) {
            hypothesis.text = newText.trim();
            renderHypotheses();
        }
    }
}

// 실험 결과 렌더링
function renderExperimentResults() {
    const results = document.getElementById('experiment-results');
    
    if (experimentResults.length === 0) {
        results.innerHTML = '<div class="text-gray-400 text-center py-4">실험 결과가 없습니다.</div>';
        return;
    }
    
    results.innerHTML = experimentResults.map(result => `
        <div class="experiment-result p-3 bg-white/5 border border-white/20 rounded-lg cursor-pointer hover:bg-white/10">
            <div class="flex items-center justify-between mb-2">
                <div class="result-type text-purple-300 text-xs">${result.type}</div>
                <div class="result-date text-gray-400 text-xs">${result.date}</div>
            </div>
            <div class="result-summary text-sm">${result.summary}</div>
            <div class="result-author text-gray-400 text-xs">작성자: ${result.author}</div>
        </div>
    `).join('');
}

// 가설을 바탕으로 실험 시작
function startExperimentFromHypotheses() {
    // 가설 목록 수집
    const hypothesesText = hypotheses.map(h => `• ${h.text}`).join('\n');
    
    if (hypotheses.length === 0) {
        alert('먼저 가설을 추가해주세요.');
        return;
    }
    
    // 실험 설계 탭으로 이동
    switchTab('design-tab');
    
    // 실험 설명란에 가설 내용 복사
    const descriptionTextarea = document.getElementById('experiment-description');
    if (descriptionTextarea) {
        const currentDescription = descriptionTextarea.value;
        const hypothesesSection = `\n\n[실험 가설]\n${hypothesesText}\n\n[실험 목적]\n위 가설들을 검증하기 위한 실험을 진행합니다.`;
        
        if (currentDescription.trim()) {
            descriptionTextarea.value = currentDescription + hypothesesSection;
        } else {
            descriptionTextarea.value = hypothesesSection.trim();
        }
    }
    
    // 실험명이 비어있으면 기본값 설정
    const experimentNameInput = document.getElementById('experiment-name');
    if (experimentNameInput && !experimentNameInput.value.trim()) {
        experimentNameInput.value = `가설 검증 실험 - ${new Date().toLocaleDateString()}`;
    }
    
    // 시작일이 비어있으면 오늘 날짜 설정
    const startDateInput = document.getElementById('start-date');
    if (startDateInput && !startDateInput.value) {
        startDateInput.value = new Date().toISOString().split('T')[0];
    }
    
    alert('가설 내용이 실험 설계 탭으로 복사되었습니다. 실험 설정을 완료해주세요.');
}

// 추적 설정에 DB 연결
let currentTrackingConfigId = null;

function connectDBToTracking(trackingConfigId) {
    currentTrackingConfigId = trackingConfigId;
    const config = trackingConfigs.find(c => c.id === trackingConfigId);
    
    if (!config) {
        alert('추적 설정을 찾을 수 없습니다.');
        return;
    }
    
    // DB 테이블 선택 모달 표시 (기존 모달 재사용)
    showDBTablesModal();
    
    // 모달 제목 변경
    const modalTitle = document.querySelector('#dbTablesModal h3');
    if (modalTitle) {
        modalTitle.innerHTML = `<i class="fas fa-database mr-2"></i>추적 설정 "${config.name}"에 연결할 DB 테이블 선택`;
    }
}

// 기존 selectTableFromModal 함수 수정
const originalSelectTableFromModal = window.selectTableFromModal;
window.selectTableFromModal = async function(tableName) {
    if (currentTrackingConfigId) {
        // 추적 설정에 DB 연결
        const config = trackingConfigs.find(c => c.id === currentTrackingConfigId);
        if (config) {
            config.connectedDB = {
                table: tableName,
                connectedAt: new Date().toLocaleString()
            };
            
            // 실험 관리자에 DB 연결 정보 저장
            if (window.experimentManager && window.experimentManager.experimentId) {
                try {
                    await window.saveDatabaseConnection(
                        window.experimentManager.experimentId,
                        tableName,
                        {
                            databaseName: 'mathking',
                            purpose: `추적 설정 "${config.name}"에 연결된 데이터 소스`,
                            conditions: { tracking_config_id: currentTrackingConfigId }
                        }
                    );
                    console.log('추적 설정 DB 연결 저장 완료');
                } catch (error) {
                    console.error('추적 설정 DB 연결 저장 실패:', error);
                }
            }
            
            renderTrackingConfigs();
            alert(`"${config.name}" 추적 설정에 "${tableName}" 테이블이 연결되었습니다.`);
        }
        
        currentTrackingConfigId = null;
        
        // 모달 제목 원복
        const modalTitle = document.querySelector('#dbTablesModal h3');
        if (modalTitle) {
            modalTitle.innerHTML = '<i class="fas fa-database mr-2"></i>DB 테이블 선택';
        }
        
        closeModal('dbTablesModal');
    } else {
        // 기존 기능 (일반 DB 연결)
        originalSelectTableFromModal(tableName);
    }
};

// 실험 결과 종합 분석
function generateComprehensiveAnalysis() {
    const connectedTrackings = trackingConfigs.filter(c => c.active && c.connectedDB);
    
    if (connectedTrackings.length === 0) {
        alert('활성화된 추적 설정 중 DB가 연결된 항목이 없습니다.');
        return;
    }
    
    const analysisContent = `
[종합 실험 결과 분석]

실험 기간: ${currentExperiment.startDate || '설정되지 않음'} (${currentExperiment.duration || 8}주)
분석 생성일: ${new Date().toLocaleString()}

[분석 대상 추적 설정]
${connectedTrackings.map(config => `
• ${config.name}
  - 설명: ${config.description}
  - 연결된 DB: ${config.connectedDB.table}
  - 연결일시: ${config.connectedDB.connectedAt}
`).join('')}

[검증된 가설]
${hypotheses.map(h => `• ${h.text}`).join('\n')}

[분석 결과 요약]
- 총 ${connectedTrackings.length}개의 데이터 소스에서 수집된 데이터를 종합 분석
- 각 추적 설정별 결과를 통합하여 가설 검증 수행
- 다각도 분석을 통한 신뢰성 있는 실험 결과 도출

[권장 사항]
1. 모든 연결된 데이터 소스의 데이터 품질 확인
2. 추적 설정별 결과 간 상관관계 분석
3. 가설별 검증 결과 문서화
4. 후속 실험 계획 수립
    `.trim();
    
    // 실험 결과에 저장
    if (window.experimentManager && window.experimentManager.experimentId) {
        saveExperimentResultData({
            type: 'analysis',
            title: '종합 실험 결과 분석',
            content: analysisContent,
            data: {
                tracking_configs: connectedTrackings,
                hypotheses: hypotheses,
                analysis_date: new Date().toISOString()
            }
        }).then(() => {
            alert('종합 분석 결과가 저장되었습니다.');
        }).catch(error => {
            console.error('종합 분석 저장 실패:', error);
            alert('종합 분석 저장에 실패했습니다.');
        });
    } else {
        // 실험이 없는 경우 임시로 표시
        const modal = document.createElement('div');
        modal.innerHTML = `
            <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                <div class="bg-slate-800 p-6 rounded-xl max-w-4xl max-h-[80vh] overflow-y-auto">
                    <h3 class="text-xl font-semibold mb-4">종합 실험 결과 분석</h3>
                    <pre class="bg-gray-900 p-4 rounded text-sm whitespace-pre-wrap">${analysisContent}</pre>
                    <button onclick="this.closest('div').remove()" class="mt-4 px-4 py-2 bg-blue-500 rounded">닫기</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }
}

// 실험 결과 저장
async function saveExperimentResultData(resultData) {
    if (!window.experimentManager || !window.experimentManager.experimentId) {
        throw new Error('실험을 먼저 생성해주세요.');
    }
    
    try {
        const result = await window.experimentManager.recordExperimentResult(resultData);
        
        // 로컬 배열에도 추가
        const newResult = {
            id: result.result_id,
            type: resultData.type,
            title: resultData.title,
            summary: resultData.content,
            author: 'Current User',
            date: new Date().toLocaleString(),
            data: resultData.data
        };
        
        experimentResults.push(newResult);
        renderExperimentResults();
        updateDashboard();
        
        return result;
        
    } catch (error) {
        console.error('실험 결과 저장 실패:', error);
        throw error;
    }
}

// 분석 결과 저장
async function saveAnalysisResult(analysisContent) {
    const resultData = {
        type: 'analysis',
        title: '분석 보고서',
        content: analysisContent,
        data: {
            analysis_type: 'manual',
            created_at: new Date().toISOString()
        },
        authorId: window.USER_ID || 1
    };
    
    try {
        await saveExperimentResultData(resultData);
        alert('분석 결과가 저장되었습니다.');
    } catch (error) {
        alert('분석 결과 저장에 실패했습니다: ' + error.message);
    }
}

// 설문 결과 저장
async function saveSurveyResult(surveyData) {
    const resultData = {
        type: 'survey',
        title: '설문 조사 결과',
        content: '설문 조사가 완료되었습니다.',
        data: surveyData,
        authorId: window.USER_ID || 1
    };
    
    try {
        await saveExperimentResultData(resultData);
        alert('설문 결과가 저장되었습니다.');
    } catch (error) {
        alert('설문 결과 저장에 실패했습니다: ' + error.message);
    }
}

// 디버깅 함수
function debugExperimentSystem() {
    console.log('=== 실험 시스템 디버깅 정보 ===');
    console.log('experimentManager:', window.experimentManager);
    console.log('experimentManager.experimentId:', window.experimentManager?.experimentId);
    console.log('currentExperiment:', currentExperiment);
    console.log('USER_ID:', window.USER_ID);
    console.log('USER_ROLE:', window.USER_ROLE);
    console.log('saveHypothesis 함수:', typeof window.saveHypothesis);
    console.log('saveExperiment 함수:', typeof window.saveExperiment);
    console.log('========================');
}

// 페이지 로드 시 초기화
document.addEventListener('DOMContentLoaded', function() {
    // DB 연결 탭의 설명이 있는 테이블 목록 로드
    if (typeof window.loadInitialDescribedTables === 'function') {
        window.loadInitialDescribedTables();
    }
    
    // 실험 설정 폼 이벤트
    const experimentForm = document.getElementById('experiment-config-form');
    if (experimentForm) {
        experimentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            saveExperimentConfig();
        });
    }
    
    // 추적 설정 폼 이벤트
    const trackingForm = document.getElementById('tracking-form');
    if (trackingForm) {
        trackingForm.addEventListener('submit', function(e) {
            e.preventDefault();
            addTrackingConfig();
        });
    }
    
    // 모달 닫기 이벤트
    document.querySelectorAll('.close-modal').forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                modal.classList.add('hidden');
            }
        });
    });
    
    // 초기 렌더링
    renderTrackingConfigs();
    renderHypotheses();
    renderExperimentResults();
    updateDashboard();
});

// 전역 함수 등록
window.updateDashboard = updateDashboard;
window.saveExperimentConfig = saveExperimentConfig;
window.selectFeedbackMethod = selectFeedbackMethod;
window.toggleFeedbackOption = toggleFeedbackOption;
window.loadTeacherStudents = loadTeacherStudents;
window.toggleStudentSelection = toggleStudentSelection;
window.addToControlGroup = addToControlGroup;
window.addToExperimentGroup = addToExperimentGroup;
window.removeFromGroup = removeFromGroup;
window.showTrackingModal = showTrackingModal;
window.addTrackingConfig = addTrackingConfig;
window.toggleTrackingConfig = toggleTrackingConfig;
window.removeTrackingConfig = removeTrackingConfig;
window.showSurveyModal = showSurveyModal;
window.showAnalysisModal = showAnalysisModal;
window.addHypothesis = addHypothesis;
window.removeHypothesis = removeHypothesis;
window.editHypothesis = editHypothesis;
window.saveExperimentResultData = saveExperimentResultData;
window.saveAnalysisResult = saveAnalysisResult;
window.saveSurveyResult = saveSurveyResult;
window.debugExperimentSystem = debugExperimentSystem;
window.startExperimentFromHypotheses = startExperimentFromHypotheses;
window.connectDBToTracking = connectDBToTracking;
window.generateComprehensiveAnalysis = generateComprehensiveAnalysis;