/**
 * Agent 09 - Learning Management Analysis UI
 * File: agents/agent09_learning_management/ui/agent.js
 *
 * Displays comprehensive learning management analysis including:
 * 1. Attendance Analysis
 * 2. Goal Input Analysis
 * 3. Pomodoro Analysis
 * 4. Wrong Answer Note Patterns
 * 5. Test Taking Patterns
 */

// Global variables
let agent09Data = null;
let currentArtifactId = null;
let selectedTargetAgent = null;

// Agent registry (1-21)
const AGENT_REGISTRY = [
    {id: 1, name: 'Agent 01', title: '온보딩'},
    {id: 2, name: 'Agent 02', title: '문제발견'},
    {id: 3, name: 'Agent 03', title: '주제추천'},
    {id: 4, name: 'Agent 04', title: '컨텐츠전달'},
    {id: 5, name: 'Agent 05', title: '하이라이트복습'},
    {id: 6, name: 'Agent 06', title: '목표설정'},
    {id: 7, name: 'Agent 07', title: '튜토리얼'},
    {id: 8, name: 'Agent 08', title: '평온도분석'},
    {id: 9, name: 'Agent 09', title: '학습관리분석'},
    {id: 10, name: 'Agent 10', title: '개념노트분석'},
    {id: 11, name: 'Agent 11', title: '문제노트분석'},
    {id: 12, name: 'Agent 12', title: '검증기법'},
    {id: 13, name: 'Agent 13', title: '피드백'},
    {id: 14, name: 'Agent 14', title: '오답노트'},
    {id: 15, name: 'Agent 15', title: '문제재정의'},
    {id: 16, name: 'Agent 16', title: '정서표현'},
    {id: 17, name: 'Agent 17', title: '전략재조정'},
    {id: 18, name: 'Agent 18', title: '추적'},
    {id: 19, name: 'Agent 19', title: '상호작용컨텐츠'},
    {id: 20, name: 'Agent 20', title: '개입준비'},
    {id: 21, name: 'Agent 21', title: '개입실행'}
];

// Agent 09 모달 표시
function showAgent09Modal() {
    const overlay = document.getElementById('modal-overlay');
    const wrapper = document.getElementById('modal-content-wrapper');

    wrapper.innerHTML = `
        <div class="modal-content" style="width: 70vw; max-width: 1200px;">
            <div class="modal-header">
                <h2>📈 Step 9: 학습관리 분석</h2>
                <button class="modal-close" onclick="closeModal()">×</button>
            </div>

            <div class="modal-body" style="max-height: 75vh; overflow-y: auto;">
                <!-- Loading State -->
                <div id="loading-state" style="text-align: center; padding: 40px;">
                    <div style="font-size: 48px; margin-bottom: 16px;">⏳</div>
                    <div style="font-size: 18px; color: #6b7280;">데이터를 분석 중입니다...</div>
                </div>

                <!-- Main Content (hidden initially) -->
                <div id="main-content" style="display: none;">
                    <!-- 학생 정보 섹션 -->
                    <div style="background: #f9fafb; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
                        <h3 style="font-size: 16px; font-weight: 600; color: #1f2937; margin-bottom: 12px;">
                            👤 학생 정보
                        </h3>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; font-size: 14px;">
                            <div>
                                <span style="color: #6b7280; font-weight: 500;">학생:</span>
                                <span style="color: #1f2937; margin-left: 8px;" id="student-name">-</span>
                            </div>
                            <div>
                                <span style="color: #6b7280; font-weight: 500;">분석 일시:</span>
                                <span style="color: #1f2937; margin-left: 8px;" id="analysis-date">-</span>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Navigation -->
                    <div style="display: flex; gap: 8px; margin-bottom: 20px; border-bottom: 2px solid #e5e7eb; padding-bottom: 8px;">
                        <button class="tab-btn active" onclick="showTab('attendance')" data-tab="attendance">📊 출결분석</button>
                        <button class="tab-btn" onclick="showTab('goals')" data-tab="goals">🎯 목표분석</button>
                        <button class="tab-btn" onclick="showTab('pomodoro')" data-tab="pomodoro">⏰ 포모도르</button>
                        <button class="tab-btn" onclick="showTab('wrong-notes')" data-tab="wrong-notes">📝 오답노트</button>
                        <button class="tab-btn" onclick="showTab('test-patterns')" data-tab="test-patterns">✅ 시험패턴</button>
                        <button class="tab-btn" onclick="showTab('send-to-agent')" data-tab="send-to-agent">🔗 에이전트 연결</button>
                        <button class="tab-btn" onclick="showTab('inbox')" data-tab="inbox">📥 수신함</button>
                    </div>

                    <!-- Tab Contents -->
                    <div id="tab-content-container">
                        <!-- Content will be dynamically loaded here -->
                    </div>
                </div>
            </div>

            <div class="modal-footer" style="padding: 20px; border-top: 1px solid #e5e7eb; display: flex; gap: 10px; justify-content: flex-end;">
                <button class="btn btn-secondary" onclick="closeModal()">닫기</button>
                <button class="btn btn-primary" onclick="completeAgent09()">확인 및 다음 단계</button>
            </div>
        </div>
    `;

    // Add tab button styles
    const style = document.createElement('style');
    style.textContent = `
        .tab-btn {
            padding: 10px 16px;
            border: none;
            background: #f3f4f6;
            color: #6b7280;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
        }
        .tab-btn:hover {
            background: #e5e7eb;
            color: #374151;
        }
        .tab-btn.active {
            background: #3b82f6;
            color: white;
        }
        .stat-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            transition: all 0.2s;
        }
        .stat-card:hover {
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .progress-bar {
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 8px;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
            transition: width 0.3s;
        }
    `;
    document.head.appendChild(style);

    overlay.classList.add('active');

    // 데이터 로드
    loadAgent09Data();
}

// Agent 09 데이터 로드
async function loadAgent09Data() {
    try {
        const studentId = window.phpData ? window.phpData.studentId : null;
        if (!studentId) {
            throw new Error('Student ID not found - File: agent.js, Line: ' + 144);
        }

        const response = await fetch(`agents/agent09_learning_management/agent.php?userid=${studentId}`);
        const result = await response.json();

        if (result.success) {
            agent09Data = result;

            // Hide loading, show content
            document.getElementById('loading-state').style.display = 'none';
            document.getElementById('main-content').style.display = 'block';

            // Update student info
            document.getElementById('student-name').textContent = result.student_name;
            document.getElementById('analysis-date').textContent = new Date(result.analysis_date).toLocaleString('ko-KR');

            // Show default tab
            showTab('attendance');

            console.log('✅ Agent 09 data loaded:', result);
        } else {
            throw new Error(result.error || 'Failed to load data');
        }
    } catch (error) {
        console.error('❌ Error loading agent data - File: agent.js, Line: ' + 167 + ':', error);
        document.getElementById('loading-state').innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <div style="font-size: 48px; margin-bottom: 16px;">❌</div>
                <div style="font-size: 18px; color: #ef4444;">데이터 로드 실패</div>
                <div style="font-size: 14px; color: #6b7280; margin-top: 8px;">${error.message}</div>
            </div>
        `;
    }
}

// Tab 전환
function showTab(tabName) {
    // Update tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');

    // Render tab content
    const container = document.getElementById('tab-content-container');

    switch(tabName) {
        case 'attendance':
            container.innerHTML = renderAttendanceTab();
            break;
        case 'goals':
            container.innerHTML = renderGoalsTab();
            break;
        case 'pomodoro':
            container.innerHTML = renderPomodoroTab();
            break;
        case 'wrong-notes':
            container.innerHTML = renderWrongNotesTab();
            break;
        case 'test-patterns':
            container.innerHTML = renderTestPatternsTab();
            break;
        case 'send-to-agent':
            container.innerHTML = renderSendToAgentTab();
            break;
        case 'inbox':
            container.innerHTML = renderInboxTab();
            loadInbox();
            break;
    }
}

// ============================================================
// Render Functions for Each Tab
// ============================================================

function renderAttendanceTab() {
    const data = agent09Data.data.attendance;
    return `
        <div style="display: grid; gap: 20px;">
            <!-- Summary Cards -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                <div class="stat-card">
                    <div style="color: #6b7280; font-size: 14px; margin-bottom: 8px;">출석률</div>
                    <div style="font-size: 32px; font-weight: 700; color: #3b82f6;">${data.attendance_rate}%</div>
                    <div class="progress-bar"><div class="progress-fill" style="width: ${data.attendance_rate}%"></div></div>
                </div>
                <div class="stat-card">
                    <div style="color: #6b7280; font-size: 14px; margin-bottom: 8px;">출석 일수</div>
                    <div style="font-size: 32px; font-weight: 700; color: #10b981;">${data.attended_days}일</div>
                    <div style="font-size: 14px; color: #6b7280; margin-top: 4px;">/ ${data.total_days}일</div>
                </div>
                <div class="stat-card">
                    <div style="color: #6b7280; font-size: 14px; margin-bottom: 8px;">결석 일수</div>
                    <div style="font-size: 32px; font-weight: 700; color: #ef4444;">${data.absent_days}일</div>
                    <div style="font-size: 14px; color: #6b7280; margin-top: 4px;">지각: ${data.late_days}일</div>
                </div>
                <div class="stat-card">
                    <div style="color: #6b7280; font-size: 14px; margin-bottom: 8px;">추세</div>
                    <div style="font-size: 24px; font-weight: 700; color: #8b5cf6;">
                        ${data.trend === 'improving' ? '📈 개선 중' : data.trend === 'declining' ? '📉 하락' : '➡️ 유지'}
                    </div>
                </div>
            </div>

            <!-- Weekly Pattern -->
            <div class="stat-card">
                <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 16px;">📅 요일별 출석률</h4>
                ${Object.entries(data.weekly_pattern).map(([day, rate]) => `
                    <div style="margin-bottom: 12px;">
                        <div style="display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 4px;">
                            <span>${day}</span>
                            <span style="font-weight: 600;">${rate}%</span>
                        </div>
                        <div class="progress-bar"><div class="progress-fill" style="width: ${rate}%"></div></div>
                    </div>
                `).join('')}
            </div>

            <!-- Time Pattern -->
            <div class="stat-card">
                <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 16px;">🕐 시간대별 출석률</h4>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
                    ${Object.entries(data.time_pattern).map(([time, rate]) => `
                        <div style="text-align: center;">
                            <div style="font-size: 28px; font-weight: 700; color: #3b82f6;">${rate}%</div>
                            <div style="font-size: 14px; color: #6b7280; margin-top: 4px;">
                                ${time === 'morning' ? '오전' : time === 'afternoon' ? '오후' : '저녁'}
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>

            <!-- Insights -->
            <div class="stat-card" style="background: #eff6ff;">
                <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 12px;">💡 인사이트</h4>
                ${data.insights.map(insight => `<div style="margin-bottom: 8px; font-size: 14px;">• ${insight}</div>`).join('')}
            </div>

            <!-- Recommendations -->
            <div class="stat-card" style="background: #f0fdf4;">
                <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 12px;">🎯 추천사항</h4>
                ${data.recommendations.map(rec => `<div style="margin-bottom: 8px; font-size: 14px;">• ${rec}</div>`).join('')}
            </div>
        </div>
    `;
}

function renderGoalsTab() {
    const data = agent09Data.data.goals;
    return `
        <div style="display: grid; gap: 20px;">
            <!-- Summary Cards -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px;">
                <div class="stat-card">
                    <div style="color: #6b7280; font-size: 14px; margin-bottom: 8px;">전체 목표</div>
                    <div style="font-size: 32px; font-weight: 700; color: #3b82f6;">${data.total_goals}</div>
                </div>
                <div class="stat-card">
                    <div style="color: #6b7280; font-size: 14px; margin-bottom: 8px;">완료한 목표</div>
                    <div style="font-size: 32px; font-weight: 700; color: #10b981;">${data.completed_goals}</div>
                </div>
                <div class="stat-card">
                    <div style="color: #6b7280; font-size: 14px; margin-bottom: 8px;">진행 중</div>
                    <div style="font-size: 32px; font-weight: 700; color: #f59e0b;">${data.in_progress_goals}</div>
                </div>
                <div class="stat-card">
                    <div style="color: #6b7280; font-size: 14px; margin-bottom: 8px;">달성률</div>
                    <div style="font-size: 32px; font-weight: 700; color: #8b5cf6;">${data.completion_rate}%</div>
                </div>
                <div class="stat-card">
                    <div style="color: #6b7280; font-size: 14px; margin-bottom: 8px;">평균 소요기간</div>
                    <div style="font-size: 28px; font-weight: 700; color: #06b6d4;">${data.average_goal_duration}일</div>
                </div>
            </div>

            <!-- Recent Goals -->
            <div class="stat-card">
                <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 16px;">🎯 최근 목표</h4>
                ${data.recent_goals.map(goal => `
                    <div style="padding: 12px; background: #f9fafb; border-radius: 8px; margin-bottom: 12px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <span style="font-weight: 600; font-size: 15px;">${goal.title}</span>
                            <span style="padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 500;
                                ${goal.status === 'completed' ? 'background: #d1fae5; color: #065f46;' : 'background: #fef3c7; color: #92400e;'}">
                                ${goal.status === 'completed' ? '완료' : '진행중'}
                            </span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 13px; color: #6b7280; margin-bottom: 8px;">
                            <span>목표 기한: ${goal.deadline}</span>
                            ${goal.actual_completion ? `<span>실제 완료: ${goal.actual_completion}</span>` : ''}
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${goal.progress}%; ${goal.status === 'completed' ? 'background: #10b981;' : ''}"></div>
                        </div>
                        <div style="text-align: right; font-size: 12px; color: #6b7280; margin-top: 4px;">${goal.progress}%</div>
                    </div>
                `).join('')}
            </div>

            <!-- Insights & Recommendations -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="stat-card" style="background: #eff6ff;">
                    <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 12px;">💡 인사이트</h4>
                    ${data.insights.map(insight => `<div style="margin-bottom: 8px; font-size: 14px;">• ${insight}</div>`).join('')}
                </div>
                <div class="stat-card" style="background: #f0fdf4;">
                    <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 12px;">🎯 추천사항</h4>
                    ${data.recommendations.map(rec => `<div style="margin-bottom: 8px; font-size: 14px;">${rec}</div>`).join('')}
                </div>
            </div>
        </div>
    `;
}

function renderPomodoroTab() {
    const data = agent09Data.data.pomodoro;
    return `
        <div style="display: grid; gap: 20px;">
            <!-- Summary Cards -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px;">
                <div class="stat-card">
                    <div style="color: #6b7280; font-size: 14px; margin-bottom: 8px;">총 세션</div>
                    <div style="font-size: 32px; font-weight: 700; color: #3b82f6;">${data.total_sessions}</div>
                </div>
                <div class="stat-card">
                    <div style="color: #6b7280; font-size: 14px; margin-bottom: 8px;">완료율</div>
                    <div style="font-size: 32px; font-weight: 700; color: #10b981;">${data.completion_rate}%</div>
                </div>
                <div class="stat-card">
                    <div style="color: #6b7280; font-size: 14px; margin-bottom: 8px;">총 학습시간</div>
                    <div style="font-size: 28px; font-weight: 700; color: #8b5cf6;">${Math.floor(data.total_study_time / 60)}h ${data.total_study_time % 60}m</div>
                </div>
                <div class="stat-card">
                    <div style="color: #6b7280; font-size: 14px; margin-bottom: 8px;">최고 집중시간</div>
                    <div style="font-size: 20px; font-weight: 700; color: #f59e0b;">${data.peak_performance_time}</div>
                </div>
            </div>

            <!-- Weekly Stats -->
            <div class="stat-card">
                <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 16px;">📊 요일별 세션</h4>
                ${Object.entries(data.weekly_stats).map(([day, count]) => `
                    <div style="margin-bottom: 12px;">
                        <div style="display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 4px;">
                            <span>${day}</span>
                            <span style="font-weight: 600;">${count} 세션</span>
                        </div>
                        <div class="progress-bar"><div class="progress-fill" style="width: ${(count/30)*100}%"></div></div>
                    </div>
                `).join('')}
            </div>

            <!-- Focus Quality -->
            <div class="stat-card">
                <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 16px;">🎯 집중도 분석</h4>
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px;">
                    ${Object.entries(data.focus_quality).map(([level, percent]) => `
                        <div style="text-align: center;">
                            <div style="font-size: 28px; font-weight: 700; color: ${
                                level === 'excellent' ? '#10b981' : level === 'good' ? '#3b82f6' : level === 'fair' ? '#f59e0b' : '#ef4444'
                            };">${percent}%</div>
                            <div style="font-size: 13px; color: #6b7280; margin-top: 4px;">
                                ${level === 'excellent' ? '우수' : level === 'good' ? '좋음' : level === 'fair' ? '보통' : '저조'}
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>

            <!-- Insights & Recommendations -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="stat-card" style="background: #eff6ff;">
                    <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 12px;">💡 인사이트</h4>
                    ${data.insights.map(insight => `<div style="margin-bottom: 8px; font-size: 14px;">• ${insight}</div>`).join('')}
                </div>
                <div class="stat-card" style="background: #f0fdf4;">
                    <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 12px;">🎯 추천사항</h4>
                    ${data.recommendations.map(rec => `<div style="margin-bottom: 8px; font-size: 14px;">${rec}</div>`).join('')}
                </div>
            </div>
        </div>
    `;
}

function renderWrongNotesTab() {
    const data = agent09Data.data.wrong_notes;
    return `
        <div style="display: grid; gap: 20px;">
            <!-- Summary Cards -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px;">
                <div class="stat-card">
                    <div style="color: #6b7280; font-size: 14px; margin-bottom: 8px;">총 노트</div>
                    <div style="font-size: 32px; font-weight: 700; color: #3b82f6;">${data.total_notes}</div>
                </div>
                <div class="stat-card">
                    <div style="color: #6b7280; font-size: 14px; margin-bottom: 8px;">마스터 완료</div>
                    <div style="font-size: 32px; font-weight: 700; color: #10b981;">${data.notes_mastered}</div>
                </div>
                <div class="stat-card">
                    <div style="color: #6b7280; font-size: 14px; margin-bottom: 8px;">평균 복습</div>
                    <div style="font-size: 32px; font-weight: 700; color: #8b5cf6;">${data.average_review_count}회</div>
                </div>
                <div class="stat-card">
                    <div style="color: #6b7280; font-size: 14px; margin-bottom: 8px;">품질 점수</div>
                    <div style="font-size: 32px; font-weight: 700; color: #f59e0b;">${data.note_quality_score}</div>
                </div>
            </div>

            <!-- Error Type Analysis -->
            <div class="stat-card">
                <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 16px;">📊 오류 유형 분석</h4>
                ${Object.entries(data.error_type_analysis).map(([type, percent]) => `
                    <div style="margin-bottom: 12px;">
                        <div style="display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 4px;">
                            <span>${
                                type === 'concept_misunderstanding' ? '개념 오해' :
                                type === 'careless_mistake' ? '실수' :
                                type === 'calculation_error' ? '계산 오류' :
                                type === 'insufficient_practice' ? '연습 부족' : '기타'
                            }</span>
                            <span style="font-weight: 600;">${percent}%</span>
                        </div>
                        <div class="progress-bar"><div class="progress-fill" style="width: ${percent}%"></div></div>
                    </div>
                `).join('')}
            </div>

            <!-- Recent Notes -->
            <div class="stat-card">
                <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 16px;">📝 최근 오답노트</h4>
                ${data.recent_notes.map(note => `
                    <div style="padding: 12px; background: #f9fafb; border-radius: 8px; margin-bottom: 12px; border-left: 4px solid ${note.mastered ? '#10b981' : '#f59e0b'};">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <span style="font-weight: 600; font-size: 15px;">${note.subject} - ${note.topic}</span>
                            <span style="padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 500;
                                ${note.mastered ? 'background: #d1fae5; color: #065f46;' : 'background: #fef3c7; color: #92400e;'}">
                                ${note.mastered ? '마스터' : '학습중'}
                            </span>
                        </div>
                        <div style="font-size: 13px; color: #6b7280; margin-bottom: 4px;">오류 유형: ${note.error_type}</div>
                        <div style="display: flex; justify-content: space-between; font-size: 13px; color: #6b7280;">
                            <span>작성일: ${note.created}</span>
                            <span>복습: ${note.review_count}회</span>
                        </div>
                    </div>
                `).join('')}
            </div>

            <!-- Insights & Recommendations -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="stat-card" style="background: #eff6ff;">
                    <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 12px;">💡 인사이트</h4>
                    ${data.insights.map(insight => `<div style="margin-bottom: 8px; font-size: 14px;">• ${insight}</div>`).join('')}
                </div>
                <div class="stat-card" style="background: #f0fdf4;">
                    <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 12px;">🎯 추천사항</h4>
                    ${data.recommendations.map(rec => `<div style="margin-bottom: 8px; font-size: 14px;">${rec}</div>`).join('')}
                </div>
            </div>
        </div>
    `;
}

function renderTestPatternsTab() {
    const data = agent09Data.data.test_patterns;
    return `
        <div style="display: grid; gap: 20px;">
            <!-- Summary Cards -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px;">
                <div class="stat-card">
                    <div style="color: #6b7280; font-size: 14px; margin-bottom: 8px;">응시 시험</div>
                    <div style="font-size: 32px; font-weight: 700; color: #3b82f6;">${data.total_tests}</div>
                </div>
                <div class="stat-card">
                    <div style="color: #6b7280; font-size: 14px; margin-bottom: 8px;">평균 점수</div>
                    <div style="font-size: 32px; font-weight: 700; color: #10b981;">${data.average_score}</div>
                </div>
                <div class="stat-card">
                    <div style="color: #6b7280; font-size: 14px; margin-bottom: 8px;">최고 점수</div>
                    <div style="font-size: 32px; font-weight: 700; color: #8b5cf6;">${data.highest_score}</div>
                </div>
                <div class="stat-card">
                    <div style="color: #6b7280; font-size: 14px; margin-bottom: 8px;">시간관리</div>
                    <div style="font-size: 32px; font-weight: 700; color: #f59e0b;">${data.time_management_score}</div>
                </div>
            </div>

            <!-- Subject Performance -->
            <div class="stat-card">
                <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 16px;">📚 과목별 성적</h4>
                ${Object.entries(data.subject_performance).map(([subject, stats]) => `
                    <div style="margin-bottom: 12px;">
                        <div style="display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 4px;">
                            <span>${subject}</span>
                            <span style="font-weight: 600;">${stats.score}점 (${stats.tests}회)</span>
                        </div>
                        <div class="progress-bar"><div class="progress-fill" style="width: ${stats.score}%"></div></div>
                    </div>
                `).join('')}
            </div>

            <!-- Difficulty Performance -->
            <div class="stat-card">
                <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 16px;">🎯 난이도별 성적</h4>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
                    ${Object.entries(data.difficulty_performance).map(([level, score]) => `
                        <div style="text-align: center;">
                            <div style="font-size: 32px; font-weight: 700; color: ${
                                level === 'easy' ? '#10b981' : level === 'medium' ? '#3b82f6' : '#ef4444'
                            };">${score}</div>
                            <div style="font-size: 14px; color: #6b7280; margin-top: 4px;">
                                ${level === 'easy' ? '쉬움' : level === 'medium' ? '보통' : '어려움'}
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>

            <!-- Recent Tests -->
            <div class="stat-card">
                <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 16px;">📋 최근 시험</h4>
                ${data.recent_tests.map(test => `
                    <div style="padding: 12px; background: #f9fafb; border-radius: 8px; margin-bottom: 12px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <span style="font-weight: 600; font-size: 15px;">${test.subject} - ${test.title}</span>
                            <span style="padding: 4px 12px; border-radius: 12px; font-size: 14px; font-weight: 600;
                                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                                ${test.score}점
                            </span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 13px; color: #6b7280;">
                            <span>응시일: ${test.date}</span>
                            <span>소요시간: ${test.time_taken}분</span>
                            <span>난이도: ${test.difficulty === 'easy' ? '쉬움' : test.difficulty === 'medium' ? '보통' : '어려움'}</span>
                        </div>
                    </div>
                `).join('')}
            </div>

            <!-- Insights & Recommendations -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="stat-card" style="background: #eff6ff;">
                    <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 12px;">💡 인사이트</h4>
                    ${data.insights.map(insight => `<div style="margin-bottom: 8px; font-size: 14px;">• ${insight}</div>`).join('')}
                </div>
                <div class="stat-card" style="background: #f0fdf4;">
                    <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 12px;">🎯 추천사항</h4>
                    ${data.recommendations.map(rec => `<div style="margin-bottom: 8px; font-size: 14px;">${rec}</div>`).join('')}
                </div>
            </div>
        </div>
    `;
}

function renderAgentPromptsTab() {
    const prompts = agent09Data.agent_prompts;
    return `
        <div style="display: grid; gap: 20px;">
            <div class="stat-card" style="background: #fffbeb;">
                <h4 style="font-size: 18px; font-weight: 600; margin-bottom: 16px; color: #92400e;">
                    🤖 다른 에이전트를 위한 분석 프롬프트
                </h4>
                <p style="color: #78350f; font-size: 14px; margin-bottom: 20px;">
                    이 섹션은 현재 학습 관리 분석 결과를 바탕으로 다른 에이전트들이 참고할 수 있도록 생성된 프롬프트입니다.
                </p>
            </div>

            ${Object.entries(prompts).map(([key, promptData]) => `
                <div class="stat-card">
                    <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 12px; color: #1f2937;">
                        ${promptData.title}
                    </h4>
                    <div style="background: #f9fafb; padding: 16px; border-radius: 8px; border-left: 4px solid #3b82f6;">
                        <pre style="font-family: 'Courier New', monospace; font-size: 13px; line-height: 1.6; margin: 0; white-space: pre-wrap; word-wrap: break-word; color: #374151;">${promptData.prompt}</pre>
                    </div>
                    <button class="btn btn-secondary" onclick="copyToClipboard('${key}')" style="margin-top: 12px; font-size: 13px;">
                        📋 프롬프트 복사
                    </button>
                </div>
            `).join('')}

            <!-- Overall Summary -->
            <div class="stat-card" style="background: #f0f9ff; border: 2px solid #3b82f6;">
                <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 12px; color: #1e40af;">
                    📊 종합 인사이트
                </h4>
                ${agent09Data.overall_insights.map(insight =>
                    `<div style="margin-bottom: 8px; font-size: 14px; color: #1e40af;">• ${insight}</div>`
                ).join('')}
            </div>

            <!-- Priority Actions -->
            <div class="stat-card" style="background: #f0fdf4; border: 2px solid #10b981;">
                <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 12px; color: #065f46;">
                    ⚡ 우선 실행 사항
                </h4>
                ${agent09Data.priority_actions.map(action =>
                    `<div style="margin-bottom: 8px; font-size: 14px; color: #065f46;">${action}</div>`
                ).join('')}
            </div>
        </div>
    `;
}

// Copy prompt to clipboard
function copyToClipboard(promptKey) {
    const prompt = agent09Data.agent_prompts[promptKey].prompt;
    navigator.clipboard.writeText(prompt).then(() => {
        alert('프롬프트가 클립보드에 복사되었습니다!');
    }).catch(err => {
        console.error('Failed to copy - File: agent.js, Line: ' + 743 + ':', err);
        alert('복사에 실패했습니다.');
    });
}

// ============================================================
// New Tab: Send To Agent
// ============================================================
function renderSendToAgentTab() {
    const prompts = agent09Data.agent_prompts;

    return `
        <div style="display: grid; gap: 20px;">
            <!-- Analysis Summary -->
            <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 16px;">📊 분석 결과 요약</h3>
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 16px;">
                    <div style="background: rgba(255,255,255,0.2); padding: 12px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 24px; font-weight: 700;">${agent09Data.data.attendance.attendance_rate}%</div>
                        <div style="font-size: 13px; margin-top: 4px;">출석률</div>
                    </div>
                    <div style="background: rgba(255,255,255,0.2); padding: 12px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 24px; font-weight: 700;">${agent09Data.data.goals.completion_rate}%</div>
                        <div style="font-size: 13px; margin-top: 4px;">목표달성률</div>
                    </div>
                    <div style="background: rgba(255,255,255,0.2); padding: 12px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 24px; font-weight: 700;">${agent09Data.data.pomodoro.completion_rate}%</div>
                        <div style="font-size: 13px; margin-top: 4px;">포모도로</div>
                    </div>
                    <div style="background: rgba(255,255,255,0.2); padding: 12px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 24px; font-weight: 700;">${agent09Data.data.test_patterns.average_score}</div>
                        <div style="font-size: 13px; margin-top: 4px;">평균점수</div>
                    </div>
                </div>
                <button class="btn btn-secondary" onclick="createArtifact()" style="width: 100%; background: rgba(255,255,255,0.9); color: #764ba2;">
                    💾 분석 결과 저장 (Artifact 생성)
                </button>
                <div id="artifact-status" style="margin-top: 12px; font-size: 14px;"></div>
            </div>

            <!-- Target Agent Selection -->
            <div class="stat-card">
                <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 16px;">🎯 타겟 에이전트 선택</h3>
                <div style="display: grid; gap: 12px;">
                    <div>
                        <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 8px;">
                            전달할 에이전트 선택 (1-21)
                        </label>
                        <select id="target-agent-select" onchange="onTargetAgentChange()"
                                style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px;">
                            <option value="">-- 에이전트 선택 --</option>
                            ${AGENT_REGISTRY.filter(a => a.id !== 9).map(agent =>
                                `<option value="${agent.id}">${agent.name} - ${agent.title}</option>`
                            ).join('')}
                        </select>
                    </div>

                    <!-- Preset Templates -->
                    <div id="preset-templates" style="display: none;">
                        <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 8px;">
                            프롬프트 프리셋
                        </label>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px;">
                            <button class="btn btn-secondary" onclick="loadPresetPrompt('default')" style="font-size: 13px;">
                                📝 기본 요약
                            </button>
                            <button class="btn btn-secondary" onclick="loadPresetPrompt('plan')" style="font-size: 13px;">
                                📋 행동 계획
                            </button>
                            <button class="btn btn-secondary" onclick="loadPresetPrompt('dataset')" style="font-size: 13px;">
                                📊 데이터 패키지
                            </button>
                            <button class="btn btn-secondary" onclick="loadPresetPrompt('command')" style="font-size: 13px;">
                                ⚡ 명령형
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Preparation Prompt Editor -->
            <div id="prompt-editor" class="stat-card" style="display: none;">
                <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 16px;">✍️ 준비 프롬프트 편집</h3>
                <textarea id="prep-prompt-text"
                          style="width: 100%; min-height: 300px; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-family: 'Courier New', monospace; font-size: 13px; line-height: 1.6;"
                          placeholder="타겟 에이전트를 위한 프롬프트를 입력하세요..."></textarea>

                <div style="display: flex; gap: 8px; margin-top: 12px;">
                    <button class="btn btn-primary" onclick="createLink()" style="flex: 1;">
                        🔗 링크 생성 및 전송
                    </button>
                    <button class="btn btn-secondary" onclick="saveDraft()">
                        💾 초안 저장
                    </button>
                </div>
                <div id="link-status" style="margin-top: 12px; font-size: 14px;"></div>
            </div>

            <!-- Link Preview -->
            <div id="link-preview" class="stat-card" style="display: none; background: #f0fdf4;">
                <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 16px; color: #065f46;">
                    ✅ 링크 생성 완료
                </h3>
                <div id="link-details"></div>
            </div>
        </div>
    `;
}

// ============================================================
// New Tab: Inbox
// ============================================================
function renderInboxTab() {
    return `
        <div style="display: grid; gap: 20px;">
            <!-- Inbox Header -->
            <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 8px;">📥 Agent 09 수신함</h3>
                <p style="font-size: 14px; opacity: 0.9;">다른 에이전트로부터 받은 분석 결과와 프롬프트</p>
                <div id="inbox-stats" style="margin-top: 12px; font-size: 14px;"></div>
            </div>

            <!-- Loading State -->
            <div id="inbox-loading" style="text-align: center; padding: 40px;">
                <div style="font-size: 48px; margin-bottom: 16px;">⏳</div>
                <div style="font-size: 18px; color: #6b7280;">수신함을 불러오는 중...</div>
            </div>

            <!-- Inbox Items Container -->
            <div id="inbox-items-container" style="display: none;"></div>

            <!-- Empty State -->
            <div id="inbox-empty" style="display: none; text-align: center; padding: 60px 20px;">
                <div style="font-size: 64px; margin-bottom: 16px; opacity: 0.5;">📭</div>
                <div style="font-size: 18px; color: #6b7280; font-weight: 500;">수신함이 비어있습니다</div>
                <div style="font-size: 14px; color: #9ca3af; margin-top: 8px;">다른 에이전트로부터 전달받은 내용이 여기에 표시됩니다</div>
            </div>
        </div>
    `;
}

// ============================================================
// Helper Functions
// ============================================================

// Create Artifact
async function createArtifact() {
    const statusDiv = document.getElementById('artifact-status');
    statusDiv.innerHTML = '<div style="color: #f59e0b;">⏳ Artifact를 생성하는 중...</div>';

    try {
        const studentId = window.phpData ? window.phpData.studentId : null;
        const response = await fetch(`agents/agent09_learning_management/agent.php?action=create_artifact&userid=${studentId}`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'}
        });

        const result = await response.json();

        if (result.success && result.artifact && result.artifact.created) {
            currentArtifactId = result.artifact.artifact_id;
            statusDiv.innerHTML = `
                <div style="color: #10b981;">
                    ✅ Artifact 생성 완료!<br>
                    <span style="font-size: 12px; opacity: 0.8;">ID: ${currentArtifactId}</span>
                </div>
            `;
            console.log('✅ Artifact created:', currentArtifactId);
        } else {
            throw new Error(result.artifact?.error || 'Artifact 생성 실패');
        }
    } catch (error) {
        console.error('❌ Error creating artifact - File: agent.js, Line: ' + new Error().lineNumber + ':', error);
        statusDiv.innerHTML = `<div style="color: #ef4444;">❌ ${error.message}</div>`;
    }
}

// Target Agent Selection Change
function onTargetAgentChange() {
    const select = document.getElementById('target-agent-select');
    selectedTargetAgent = parseInt(select.value);

    const presetDiv = document.getElementById('preset-templates');
    const editorDiv = document.getElementById('prompt-editor');

    if (selectedTargetAgent) {
        presetDiv.style.display = 'block';
        editorDiv.style.display = 'block';

        // Load default prompt for selected agent
        loadPresetPrompt('default');
    } else {
        presetDiv.style.display = 'none';
        editorDiv.style.display = 'none';
    }
}

// Load Preset Prompt
function loadPresetPrompt(presetType) {
    if (!selectedTargetAgent) return;

    const textarea = document.getElementById('prep-prompt-text');
    const prompts = agent09Data.agent_prompts;

    // Map preset types to agent prompts
    const promptMap = {
        '10': prompts.for_agent_10,
        '11': prompts.for_agent_11,
        '15': prompts.for_agent_15,
        '19': prompts.for_agent_19
    };

    let promptText = '';

    if (promptMap[selectedTargetAgent]) {
        promptText = promptMap[selectedTargetAgent].prompt;
    } else {
        // Generate default prompt
        promptText = `Agent ${selectedTargetAgent}을(를) 위한 학습관리 분석 결과\n\n`;
        promptText += `학생: ${agent09Data.student_name}\n`;
        promptText += `분석 일시: ${agent09Data.analysis_date}\n\n`;
        promptText += `주요 지표:\n`;
        promptText += `- 출석률: ${agent09Data.data.attendance.attendance_rate}%\n`;
        promptText += `- 목표 달성률: ${agent09Data.data.goals.completion_rate}%\n`;
        promptText += `- 포모도로 완성률: ${agent09Data.data.pomodoro.completion_rate}%\n`;
        promptText += `- 평균 시험 점수: ${agent09Data.data.test_patterns.average_score}점\n\n`;
        promptText += `종합 인사이트:\n`;
        agent09Data.overall_insights.forEach(insight => {
            promptText += `- ${insight}\n`;
        });
    }

    // Apply preset type modifications
    if (presetType === 'plan') {
        promptText += `\n\n다음 행동 계획:\n`;
        agent09Data.priority_actions.forEach(action => {
            promptText += `- ${action}\n`;
        });
    } else if (presetType === 'dataset') {
        promptText += `\n\n데이터 상세:\n`;
        promptText += JSON.stringify(agent09Data.data, null, 2);
    } else if (presetType === 'command') {
        promptText = `[명령] Agent ${selectedTargetAgent}은(는) 다음 분석 결과를 바탕으로 후속 작업을 수행하세요.\n\n` + promptText;
    }

    textarea.value = promptText;
}

// Create Link
async function createLink() {
    if (!currentArtifactId) {
        alert('먼저 Artifact를 생성해주세요.');
        return;
    }

    if (!selectedTargetAgent) {
        alert('타겟 에이전트를 선택해주세요.');
        return;
    }

    const promptText = document.getElementById('prep-prompt-text').value;
    if (!promptText.trim()) {
        alert('프롬프트를 입력해주세요.');
        return;
    }

    const statusDiv = document.getElementById('link-status');
    statusDiv.innerHTML = '<div style="color: #f59e0b;">⏳ 링크를 생성하는 중...</div>';

    try {
        const link_id = 'lnk_agent09_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);

        const linkData = {
            link_id: link_id,
            source_agent_id: 9,
            target_agent_id: selectedTargetAgent,
            artifact_id: currentArtifactId,
            prompt_text: promptText,
            render_hint: 'text',
            status: 'published'
        };

        const response = await fetch('api/links.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(linkData)
        });

        const result = await response.json();

        if (result.success) {
            statusDiv.innerHTML = '<div style="color: #10b981;">✅ 링크 생성 및 전송 완료!</div>';

            // Show link preview
            const previewDiv = document.getElementById('link-preview');
            const targetAgent = AGENT_REGISTRY.find(a => a.id === selectedTargetAgent);

            document.getElementById('link-details').innerHTML = `
                <div style="padding: 16px; background: white; border-radius: 8px; border: 2px solid #10b981;">
                    <div style="font-size: 16px; font-weight: 600; color: #065f46; margin-bottom: 12px;">
                        Agent 09 → Agent ${selectedTargetAgent} (${targetAgent.title})
                    </div>
                    <div style="font-size: 14px; color: #6b7280; margin-bottom: 8px;">
                        링크 ID: ${link_id}
                    </div>
                    <div style="font-size: 14px; color: #6b7280;">
                        상태: <span style="color: #10b981; font-weight: 600;">전송됨 (Published)</span>
                    </div>
                </div>
            `;
            previewDiv.style.display = 'block';

            console.log('✅ Link created:', link_id);
        } else {
            throw new Error(result.error || '링크 생성 실패');
        }
    } catch (error) {
        console.error('❌ Error creating link - File: agent.js, Line: ' + new Error().lineNumber + ':', error);
        statusDiv.innerHTML = `<div style="color: #ef4444;">❌ ${error.message}</div>`;
    }
}

// Save Draft
function saveDraft() {
    const promptText = document.getElementById('prep-prompt-text').value;
    localStorage.setItem('agent09_draft_prompt', promptText);
    localStorage.setItem('agent09_draft_target', selectedTargetAgent);

    alert('💾 초안이 저장되었습니다.');
    console.log('💾 Draft saved');
}

// Load Inbox
async function loadInbox() {
    const loadingDiv = document.getElementById('inbox-loading');
    const itemsContainer = document.getElementById('inbox-items-container');
    const emptyDiv = document.getElementById('inbox-empty');
    const statsDiv = document.getElementById('inbox-stats');

    try {
        const response = await fetch('api/inbox.php?target_agent_id=9');
        const result = await response.json();

        loadingDiv.style.display = 'none';

        if (result.success) {
            statsDiv.innerHTML = `
                전체: ${result.inbox_count}건 |
                미읽음: <span style="background: #fef3c7; color: #92400e; padding: 2px 8px; border-radius: 12px; font-weight: 600;">
                    ${result.unread_count}
                </span>
            `;

            if (result.items && result.items.length > 0) {
                itemsContainer.innerHTML = result.items.map(item => `
                    <div class="stat-card" style="border-left: 4px solid #3b82f6;">
                        <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 12px;">
                            <div>
                                <div style="font-size: 16px; font-weight: 600; color: #1f2937;">
                                    ${item.source_agent.name} - ${item.source_agent.title}
                                </div>
                                <div style="font-size: 13px; color: #6b7280; margin-top: 4px;">
                                    ${item.created_at_formatted}
                                </div>
                            </div>
                            <span style="padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 500;
                                ${item.status === 'published' ? 'background: #fef3c7; color: #92400e;' : 'background: #d1fae5; color: #065f46;'}">
                                ${item.status === 'published' ? '미읽음' : '읽음'}
                            </span>
                        </div>

                        <div style="background: #f9fafb; padding: 12px; border-radius: 8px; margin-bottom: 12px;">
                            <div style="font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 8px;">
                                📄 분석 요약
                            </div>
                            <div style="font-size: 13px; color: #6b7280; line-height: 1.6;">
                                ${item.artifact.summary}
                            </div>
                        </div>

                        ${item.prompt_text ? `
                            <div style="background: #eff6ff; padding: 12px; border-radius: 8px;">
                                <div style="font-size: 14px; font-weight: 500; color: #1e40af; margin-bottom: 8px;">
                                    ✍️ 준비된 프롬프트
                                </div>
                                <pre style="font-family: 'Courier New', monospace; font-size: 12px; line-height: 1.6; margin: 0; white-space: pre-wrap; word-wrap: break-word; color: #374151;">${item.prompt_text}</pre>
                            </div>
                        ` : ''}

                        <div style="display: flex; gap: 8px; margin-top: 12px;">
                            <button class="btn btn-primary" onclick="markAsRead('${item.link_id}')" style="font-size: 13px;">
                                ✓ 읽음 표시
                            </button>
                            <button class="btn btn-secondary" onclick="viewFullData('${item.artifact.artifact_id}')" style="font-size: 13px;">
                                📊 전체 데이터 보기
                            </button>
                        </div>
                    </div>
                `).join('');

                itemsContainer.style.display = 'block';
            } else {
                emptyDiv.style.display = 'block';
            }
        } else {
            throw new Error(result.error || 'Inbox 로드 실패');
        }
    } catch (error) {
        console.error('❌ Error loading inbox - File: agent.js, Line: ' + new Error().lineNumber + ':', error);
        loadingDiv.innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <div style="font-size: 48px; margin-bottom: 16px;">❌</div>
                <div style="font-size: 18px; color: #ef4444;">Inbox 로드 실패</div>
                <div style="font-size: 14px; color: #6b7280; margin-top: 8px;">${error.message}</div>
            </div>
        `;
    }
}

// Mark link as read
async function markAsRead(linkId) {
    try {
        const response = await fetch('api/links.php', {
            method: 'PUT',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                link_id: linkId,
                status: 'read'
            })
        });

        const result = await response.json();

        if (result.success) {
            console.log('✅ Link marked as read:', linkId);
            loadInbox(); // Reload inbox
        } else {
            throw new Error(result.error);
        }
    } catch (error) {
        console.error('❌ Error marking as read - File: agent.js, Line: ' + new Error().lineNumber + ':', error);
        alert('읽음 표시 실패: ' + error.message);
    }
}

// View full artifact data
function viewFullData(artifactId) {
    alert('전체 데이터 보기 기능은 추후 구현 예정입니다.\nArtifact ID: ' + artifactId);
}

// Agent 09 완료 처리
function completeAgent09() {
    // 상태 업데이트
    if (window.state) {
        if (!window.state.stepData) window.state.stepData = {};
        if (!window.state.stepData[9]) window.state.stepData[9] = { inputs: {}, outputs: {} };

        window.state.stepData[9].outputs['학습관리 분석'] = '완료';
        window.state.stepData[9].outputs['분석 데이터'] = {
            attendance_rate: agent09Data.data.attendance.attendance_rate,
            goal_completion_rate: agent09Data.data.goals.completion_rate,
            pomodoro_completion: agent09Data.data.pomodoro.completion_rate,
            avg_test_score: agent09Data.data.test_patterns.average_score
        };

        // Step 9 완료 처리
        window.state.completedSteps.add(9);
        window.state.currentStep = 10;
    }

    // 모달 닫기
    closeModal();

    // 워크플로우 재렌더링
    if (window.renderWorkflow) {
        window.renderWorkflow();
    }

    console.log('✅ Agent 09 completed successfully');
}

console.log('✅ Agent 09 UI loaded');
