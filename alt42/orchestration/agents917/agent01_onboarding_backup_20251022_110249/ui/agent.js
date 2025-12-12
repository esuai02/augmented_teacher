/**
 * Agent 01 - Onboarding UI
 * File: agents/agent01_onboarding/ui/agent.js
 */

// MBTI 타입 목록
const mbtiTypes = [
    'INTJ', 'INTP', 'ENTJ', 'ENTP',
    'INFJ', 'INFP', 'ENFJ', 'ENFP',
    'ISTJ', 'ISFJ', 'ESTJ', 'ESFJ',
    'ISTP', 'ISFP', 'ESTP', 'ESFP'
];

// Agent 01 모달 표시
function showAgent01Modal() {
    const overlay = document.getElementById('modal-overlay');
    const wrapper = document.getElementById('modal-content-wrapper');

    // 현재 선택된 MBTI (있다면)
    const currentMBTI = window.phpData && window.phpData.mbti ? window.phpData.mbti : 'INTJ';

    wrapper.innerHTML = `
        <div class="modal-content" style="width: 50vw; max-width: 50vw;">
            <div class="modal-header">
                <h2>👤 Step 1: 온보딩</h2>
                <button class="modal-close" onclick="closeModal()">×</button>
            </div>

            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <!-- 학생 정보 섹션 -->
                <div style="background: #f9fafb; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
                    <h3 style="font-size: 16px; font-weight: 600; color: #1f2937; margin-bottom: 12px;">
                        📋 학생 정보
                    </h3>
                    <div style="display: grid; gap: 8px; font-size: 14px;">
                        <div>
                            <span style="color: #6b7280; font-weight: 500;">학생 ID:</span>
                            <span style="color: #1f2937; margin-left: 8px;" id="student-id-display">
                                ${window.phpData ? window.phpData.studentId : '-'}
                            </span>
                        </div>
                        <div>
                            <span style="color: #6b7280; font-weight: 500;">학생 이름:</span>
                            <span style="color: #1f2937; margin-left: 8px;" id="student-name-display">
                                ${window.phpData ? window.phpData.studentName : '-'}
                            </span>
                        </div>
                        <div>
                            <span style="color: #6b7280; font-weight: 500;">역할:</span>
                            <span style="color: #1f2937; margin-left: 8px;">
                                ${window.phpData ? window.phpData.userRole : '-'}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- MBTI 선택 섹션 -->
                <div style="margin-bottom: 20px;">
                    <h3 style="font-size: 16px; font-weight: 600; color: #1f2937; margin-bottom: 12px;">
                        🎯 성격 유형 (MBTI) 선택
                    </h3>
                    <p style="font-size: 14px; color: #6b7280; margin-bottom: 16px;">
                        학습 스타일에 맞는 맞춤형 학습 경로를 제공하기 위해 MBTI 유형을 선택해주세요.
                    </p>

                    <div id="mbti-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px;">
                        ${mbtiTypes.map(type => `
                            <button
                                class="mbti-option ${type === currentMBTI ? 'selected' : ''}"
                                onclick="selectMBTI('${type}')"
                                data-mbti="${type}"
                                style="
                                    padding: 12px;
                                    border: 2px solid ${type === currentMBTI ? '#3b82f6' : '#e5e7eb'};
                                    background: ${type === currentMBTI ? '#eff6ff' : 'white'};
                                    border-radius: 8px;
                                    font-size: 14px;
                                    font-weight: 600;
                                    color: ${type === currentMBTI ? '#3b82f6' : '#6b7280'};
                                    cursor: pointer;
                                    transition: all 0.2s;
                                ">
                                ${type}
                            </button>
                        `).join('')}
                    </div>

                    <div id="mbti-selected" style="margin-top: 16px; padding: 12px; background: #eff6ff; border-radius: 8px; display: ${currentMBTI ? 'block' : 'none'};">
                        <span style="font-size: 14px; color: #3b82f6; font-weight: 500;">
                            ✓ 선택된 MBTI: <span id="selected-mbti-display">${currentMBTI}</span>
                        </span>
                    </div>
                </div>

                <!-- 학습 이력 섹션 -->
                <div style="background: #f9fafb; padding: 16px; border-radius: 8px;">
                    <h3 style="font-size: 16px; font-weight: 600; color: #1f2937; margin-bottom: 12px;">
                        📊 학습 이력
                    </h3>
                    <div style="display: grid; gap: 12px;">
                        <div style="display: flex; justify-content: space-between; font-size: 14px;">
                            <span style="color: #6b7280;">총 학습 세션:</span>
                            <span style="color: #1f2937; font-weight: 600;" id="total-sessions">-</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 14px;">
                            <span style="color: #6b7280;">완료한 활동:</span>
                            <span style="color: #1f2937; font-weight: 600;" id="completed-activities">-</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 14px;">
                            <span style="color: #6b7280;">평균 점수:</span>
                            <span style="color: #1f2937; font-weight: 600;" id="average-score">-</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer" style="padding: 20px; border-top: 1px solid #e5e7eb; display: flex; gap: 10px; justify-content: flex-end;">
                <button class="btn btn-secondary" onclick="closeModal()">취소</button>
                <button class="btn btn-primary" onclick="saveAgent01Data()">확인 및 다음 단계</button>
            </div>
        </div>
    `;

    overlay.classList.add('active');

    // 데이터 로드
    loadAgent01Data();
}

// MBTI 선택
function selectMBTI(type) {
    // 모든 버튼 초기화
    document.querySelectorAll('.mbti-option').forEach(btn => {
        btn.style.border = '2px solid #e5e7eb';
        btn.style.background = 'white';
        btn.style.color = '#6b7280';
        btn.classList.remove('selected');
    });

    // 선택된 버튼 스타일
    const selectedBtn = document.querySelector(`[data-mbti="${type}"]`);
    if (selectedBtn) {
        selectedBtn.style.border = '2px solid #3b82f6';
        selectedBtn.style.background = '#eff6ff';
        selectedBtn.style.color = '#3b82f6';
        selectedBtn.classList.add('selected');
    }

    // 선택 표시
    const displayDiv = document.getElementById('mbti-selected');
    const displaySpan = document.getElementById('selected-mbti-display');
    if (displayDiv && displaySpan) {
        displayDiv.style.display = 'block';
        displaySpan.textContent = type;
    }

    // 전역 상태 업데이트
    if (window.state) {
        window.state.selectedMBTI = type;
    }

    console.log('MBTI selected:', type);
}

// Agent 01 데이터 로드
async function loadAgent01Data() {
    try {
        const studentId = window.phpData ? window.phpData.studentId : null;
        if (!studentId) {
            console.error('❌ Student ID not found | File: agents/agent01_onboarding/ui/agent.js | Line: 168');
            showAgent01ErrorUI('학생 ID를 찾을 수 없습니다. (File: agents/agent01_onboarding/ui/agent.js, Line: 168)');
            return;
        }

        const url = `agents/agent01_onboarding/agent.php?userid=${encodeURIComponent(studentId)}`;
        const response = await fetch(url, { credentials: 'same-origin' });

        const contentType = response.headers.get('content-type') || '';
        if (!contentType.includes('application/json')) {
            const text = await response.text();
            const snippet = text.slice(0, 300).replace(/\s+/g, ' ').trim();
            console.error('❌ Non-JSON response | File: agents/agent01_onboarding/ui/agent.js | Line: 176', {
                status: response.status,
                url,
                contentType,
                snippet
            });
            const humanHint = /login|로그인|<form[^>]*action=\"\/?login/i.test(text) ? '로그인이 필요합니다. 먼저 Moodle에 로그인하세요.' : '서버 응답 형식이 올바르지 않습니다.';
            showAgent01ErrorUI(`${humanHint} (File: agents/agent01_onboarding/ui/agent.js, Line: 176)`);
            return;
        }

        if (!response.ok) {
            console.error('❌ HTTP error while loading agent data | File: agents/agent01_onboarding/ui/agent.js | Line: 185', {
                status: response.status,
                url
            });
            showAgent01ErrorUI(`데이터 로드 실패 (HTTP ${response.status}). (File: agents/agent01_onboarding/ui/agent.js, Line: 185)`);
            return;
        }

        const data = await response.json();

        if (data && data.success) {
            // 학습 이력 업데이트
            document.getElementById('total-sessions').textContent = (data.data && data.data.learning_history && data.data.learning_history.total_sessions) || 0;
            document.getElementById('completed-activities').textContent = (data.data && data.data.learning_history && data.data.learning_history.completed_activities) || 0;
            document.getElementById('average-score').textContent = (data.data && data.data.learning_history && data.data.learning_history.average_score) || 0;

            console.log('✅ Agent 01 data loaded:', data);
        } else {
            const errMsg = data && (data.error || data.message) ? (data.error || data.message) : '알 수 없는 오류';
            console.error('❌ Failed to load agent data | File: agents/agent01_onboarding/ui/agent.js | Line: 186:', errMsg);
            showAgent01ErrorUI(`데이터 로드 실패: ${errMsg} (File: agents/agent01_onboarding/ui/agent.js, Line: 186)`);
        }
    } catch (error) {
        console.error('❌ Exception while loading agent data | File: agents/agent01_onboarding/ui/agent.js | Line: 189:', error);
        showAgent01ErrorUI(`예외 발생: ${error && (error.message || error)} (File: agents/agent01_onboarding/ui/agent.js, Line: 189)`);
    }
}

// Agent 01 데이터 저장
async function saveAgent01Data() {
    const selectedMBTI = document.querySelector('.mbti-option.selected');

    if (!selectedMBTI) {
        alert('MBTI 유형을 선택해주세요.');
        return;
    }

    const mbtiType = selectedMBTI.getAttribute('data-mbti');

    try {
        // 여기서 실제 저장 API를 호출할 수 있습니다
        console.log('Saving MBTI:', mbtiType);

        // 상태 업데이트
        if (window.state) {
            if (!window.state.stepData) window.state.stepData = {};
            if (!window.state.stepData[1]) window.state.stepData[1] = { inputs: {}, outputs: {} };

            window.state.stepData[1].inputs['MBTI'] = mbtiType;
            window.state.stepData[1].outputs['성격 유형'] = mbtiType;
            window.state.stepData[1].outputs['프로필 정보'] = '완료';

            // Step 1 완료 처리
            window.state.completedSteps.add(1);
            window.state.currentStep = 2;
        }

        // 모달 닫기
        closeModal();

        // 워크플로우 재렌더링
        if (window.renderWorkflow) {
            window.renderWorkflow();
        }

        console.log('✅ Agent 01 data saved successfully');

    } catch (error) {
        console.error('❌ Error saving agent data | File: agents/agent01_onboarding/ui/agent.js | Line: 223:', error);
        alert('데이터 저장 중 오류가 발생했습니다.');
    }
}

console.log('✅ Agent 01 UI loaded');

// 간단한 오류 표시 유틸리티 (온보딩 모달 내 표시)
function showAgent01ErrorUI(message) {
    try {
        const wrapper = document.getElementById('modal-content-wrapper');
        if (!wrapper) return;
        const box = document.createElement('div');
        box.setAttribute('role', 'alert');
        box.style.background = '#fee2e2';
        box.style.border = '1px solid #ef4444';
        box.style.color = '#991b1b';
        box.style.padding = '12px';
        box.style.borderRadius = '8px';
        box.style.margin = '12px 0';
        box.style.fontSize = '14px';
        box.textContent = message;
        wrapper.appendChild(box);
    } catch (e) {
        // 최후 수단: 콘솔 출력만 수행
        console.error('❌ Failed to display error UI | File: agents/agent01_onboarding/ui/agent.js | Line: 245:', e, message);
    }
}
