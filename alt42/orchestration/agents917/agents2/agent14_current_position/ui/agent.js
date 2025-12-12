/**
 * Agent 14 - Current Position Analysis UI
 * File: agents/agent14_current_position/ui/agent.js
 * 현재 위치 평가 패널 렌더링
 */

// Agent 14 패널 렌더링 함수
function renderAgent14Panel(panelElement) {
    const studentId = window.phpData ? window.phpData.studentId : null;

    if (!studentId) {
        panelElement.innerHTML = `
            <div style="padding: 20px; color: #dc2626; background: #fee2e2; border-radius: 8px;">
                <i class="fas fa-exclamation-triangle"></i> 학생 ID를 찾을 수 없습니다.
            </div>
        `;
        return;
    }

    // 로딩 상태 표시
    panelElement.innerHTML = `
        <div style="text-align: center; padding: 50px; color: #6b7280;">
            <div style="font-size: 48px; margin-bottom: 20px;">
                <i class="fas fa-spinner" style="animation: spin 1s linear infinite;"></i>
            </div>
            <p>데이터를 분석하는 중입니다...</p>
        </div>
        <style>
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        </style>
    `;

    // API 호출 및 데이터 렌더링
    fetch(`agents/agent14_current_position/agent.php?userid=${studentId}`)
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                showError(data.error || '데이터를 불러오는데 실패했습니다.', panelElement);
                return;
            }

            const analysisData = data.data;

            // 데이터 없음 처리
            if (analysisData.status === 'no_data') {
                showError(analysisData.message, panelElement);
                return;
            }

            // 대시보드 렌더링
            renderDashboard(analysisData, panelElement);

            // 전역 데이터 저장
            window.agentData = window.agentData || {};
            window.agentData.agent14 = {
                timestamp: new Date(),
                studentId: studentId,
                overallStatus: analysisData.overall_status,
                completionRate: analysisData.completion_rate,
                emotionalState: analysisData.emotional_state,
                statistics: analysisData.statistics,
                insights: analysisData.insights,
                recommendations: analysisData.recommendations,
                agentSummary: analysisData.agent_summary
            };
            console.log('Agent 14 데이터 저장됨:', window.agentData.agent14);
        })
        .catch(error => {
            showError('API 호출 오류: ' + error.message, panelElement);
            console.error('Agent 14 AJAX 에러:', error);
        });
}

// 에러 표시 함수
function showError(message, panelElement) {
    panelElement.innerHTML = `
        <div style="background: #ffebee; border: 2px solid #f44336; border-radius: 10px; padding: 20px; color: #c62828;">
            <i class="fas fa-exclamation-triangle" style="font-size: 24px; margin-right: 10px;"></i>
            <span>${message}</span>
        </div>
    `;
}

// 대시보드 렌더링 함수
function renderDashboard(data, panelElement) {
    panelElement.innerHTML = `
        <style>
            .agent14-summary-box {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 20px;
                border-radius: 12px;
                margin-bottom: 20px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            }
            .agent14-summary-box h3 {
                font-size: 16px;
                margin-bottom: 12px;
                opacity: 0.9;
            }
            .agent14-summary-text {
                font-size: 14px;
                line-height: 1.6;
                background: rgba(255,255,255,0.1);
                padding: 12px;
                border-radius: 8px;
            }
            .agent14-card {
                background: #f9fafb;
                padding: 16px;
                border-radius: 10px;
                margin-bottom: 16px;
            }
            .agent14-card h3 {
                font-size: 15px;
                font-weight: 600;
                color: #1f2937;
                margin-bottom: 12px;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .agent14-status-badge {
                display: inline-block;
                padding: 6px 14px;
                border-radius: 16px;
                font-size: 13px;
                font-weight: bold;
                margin-top: 8px;
            }
            .agent14-status-delayed { background: #FF6B6B; color: white; }
            .agent14-status-ontime { background: #4CAF50; color: white; }
            .agent14-status-early { background: #2196F3; color: white; }
            .agent14-emotion-very-positive { background: #2196F3; color: white; }
            .agent14-emotion-positive { background: #4CAF50; color: white; }
            .agent14-emotion-negative { background: #FF9800; color: white; }
            .agent14-emotion-neutral { background: #9E9E9E; color: white; }
            .agent14-progress-bar {
                width: 100%;
                height: 24px;
                background: #e0e0e0;
                border-radius: 12px;
                overflow: hidden;
                margin-top: 8px;
            }
            .agent14-progress-fill {
                height: 100%;
                background: linear-gradient(90deg, #667eea, #764ba2);
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-weight: bold;
                font-size: 12px;
                transition: width 0.5s ease;
            }
            .agent14-stat-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
                margin-top: 12px;
            }
            .agent14-stat-item {
                background: white;
                padding: 12px;
                border-radius: 8px;
                text-align: center;
            }
            .agent14-stat-value {
                font-size: 24px;
                font-weight: bold;
                color: #667eea;
            }
            .agent14-stat-label {
                font-size: 11px;
                color: #666;
                margin-top: 4px;
            }
            .agent14-insights-list, .agent14-recommendations-list {
                list-style: none;
                margin-top: 12px;
                padding: 0;
            }
            .agent14-insights-list li {
                padding: 10px;
                background: #E3F2FD;
                border-left: 3px solid #2196F3;
                margin-bottom: 8px;
                border-radius: 4px;
                font-size: 13px;
            }
            .agent14-recommendations-list li {
                padding: 10px;
                background: #FFF3E0;
                border-left: 3px solid #FF9800;
                margin-bottom: 8px;
                border-radius: 4px;
                font-size: 13px;
            }
            .agent14-table {
                width: 100%;
                margin-top: 12px;
                border-collapse: collapse;
                font-size: 12px;
            }
            .agent14-table th {
                background: #667eea;
                color: white;
                padding: 10px 8px;
                text-align: left;
                font-size: 12px;
            }
            .agent14-table td {
                padding: 10px 8px;
                border-bottom: 1px solid #e0e0e0;
            }
            .agent14-table tr:hover {
                background: #f5f5f5;
            }
            .agent14-export-btn {
                margin-top: 16px;
                padding: 10px 20px;
                background: #667eea;
                color: white;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                font-size: 14px;
                font-weight: 500;
                transition: background 0.3s;
            }
            .agent14-export-btn:hover {
                background: #764ba2;
            }
        </style>

        <div style="max-height: calc(100vh - 200px); overflow-y: auto; padding-right: 8px;">
            <!-- Agent 요약 -->
            <div class="agent14-summary-box">
                <h3><i class="fas fa-robot"></i> Agent 요약 (다른 에이전트 전달용)</h3>
                <div class="agent14-summary-text">${data.agent_summary}</div>
            </div>

            <!-- 전체 진행 상태 -->
            <div class="agent14-card">
                <h3><i class="fas fa-chart-line"></i> 전체 진행 상태</h3>
                <div>
                    <p style="font-size: 14px; margin: 8px 0;">진행 상태</p>
                    <span class="agent14-status-badge ${getStatusClass(data.overall_status)}">${data.overall_status}</span>
                    <div class="agent14-progress-bar">
                        <div class="agent14-progress-fill" style="width: ${data.completion_rate || 0}%">
                            ${(data.completion_rate || 0).toFixed(1)}%
                        </div>
                    </div>
                </div>
            </div>

            <!-- 감정 상태 -->
            <div class="agent14-card">
                <h3><i class="fas fa-heart"></i> 감정 상태</h3>
                <div>
                    <p style="font-size: 14px; margin: 8px 0;">감정 상태</p>
                    <span class="agent14-status-badge ${getEmotionClass(data.emotional_state)}">${data.emotional_state}</span>
                    <div style="margin-top: 12px; font-size: 13px; color: #666;">
                        <div>매우만족: ${data.statistics.satisfaction.매우만족}개</div>
                        <div>만족: ${data.statistics.satisfaction.만족}개</div>
                        <div>불만족: ${data.statistics.satisfaction.불만족}개</div>
                    </div>
                </div>
            </div>

            <!-- 통계 -->
            <div class="agent14-card">
                <h3><i class="fas fa-chart-bar"></i> 통계</h3>
                <div class="agent14-stat-grid">
                    <div class="agent14-stat-item">
                        <div class="agent14-stat-value">${data.statistics.total_entries}</div>
                        <div class="agent14-stat-label">전체 항목</div>
                    </div>
                    <div class="agent14-stat-item">
                        <div class="agent14-stat-value">${data.statistics.completed}</div>
                        <div class="agent14-stat-label">완료</div>
                    </div>
                    <div class="agent14-stat-item">
                        <div class="agent14-stat-value">${data.statistics.delayed}</div>
                        <div class="agent14-stat-label">지연</div>
                    </div>
                    <div class="agent14-stat-item">
                        <div class="agent14-stat-value">${data.statistics.on_time}</div>
                        <div class="agent14-stat-label">적절</div>
                    </div>
                    <div class="agent14-stat-item">
                        <div class="agent14-stat-value">${data.statistics.early}</div>
                        <div class="agent14-stat-label">원활</div>
                    </div>
                    <div class="agent14-stat-item">
                        <div class="agent14-stat-value">${data.statistics.total_planned_minutes}</div>
                        <div class="agent14-stat-label">계획 시간 (분)</div>
                    </div>
                </div>
            </div>

            <!-- 분석 인사이트 -->
            <div class="agent14-card">
                <h3><i class="fas fa-lightbulb"></i> 분석 인사이트</h3>
                <ul class="agent14-insights-list">
                    ${data.insights.map(insight => `<li><i class="fas fa-info-circle"></i> ${insight}</li>`).join('')}
                </ul>
            </div>

            <!-- 추천 사항 -->
            <div class="agent14-card">
                <h3><i class="fas fa-clipboard-check"></i> 추천 사항</h3>
                <ul class="agent14-recommendations-list">
                    ${data.recommendations.map(rec => `<li><i class="fas fa-check-circle"></i> ${rec}</li>`).join('')}
                </ul>
            </div>

            <!-- 세부 항목 분석 -->
            <div class="agent14-card">
                <h3><i class="fas fa-list"></i> 세부 항목 분석</h3>
                <div style="overflow-x: auto;">
                    <table class="agent14-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>계획</th>
                                <th>예상시간</th>
                                <th>실제완료</th>
                                <th>지연</th>
                                <th>상태</th>
                                <th>만족도</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.entries.map(entry => {
                                const expectedTime = formatTime(entry.expected_start) + '~' + formatTime(entry.expected_end);
                                const actualTime = entry.actual_completion ? formatTime(entry.actual_completion) : '-';
                                const delay = entry.delay_minutes !== null ? entry.delay_minutes + '분' : '-';
                                const statusBadge = getStatusBadge(entry.progress_status);
                                const satisfactionBadge = entry.status || '-';

                                return `
                                    <tr>
                                        <td><strong>${entry.index}</strong></td>
                                        <td>${entry.plan}</td>
                                        <td>${expectedTime}</td>
                                        <td>${actualTime}</td>
                                        <td>${delay}</td>
                                        <td>${statusBadge}</td>
                                        <td>${satisfactionBadge}</td>
                                    </tr>
                                `;
                            }).join('')}
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 전달 버튼 -->
            <button class="agent14-export-btn" onclick="exportAgent14Data()">
                <i class="fas fa-share-alt"></i> 다른 에이전트로 전달
            </button>
        </div>
    `;
}

// 상태 클래스 반환
function getStatusClass(status) {
    if (status === '지연') return 'agent14-status-delayed';
    if (status === '적절') return 'agent14-status-ontime';
    if (status === '원활') return 'agent14-status-early';
    return '';
}

// 감정 클래스 반환
function getEmotionClass(emotion) {
    if (emotion === '매우 긍정') return 'agent14-emotion-very-positive';
    if (emotion === '긍정') return 'agent14-emotion-positive';
    if (emotion === '부정') return 'agent14-emotion-negative';
    return 'agent14-emotion-neutral';
}

// 상태 배지 반환
function getStatusBadge(status) {
    const classes = {
        '지연': 'agent14-status-delayed',
        '적절': 'agent14-status-ontime',
        '원활': 'agent14-status-early',
        '미완료': 'agent14-emotion-neutral'
    };
    const className = classes[status] || 'agent14-emotion-neutral';
    return `<span class="agent14-status-badge ${className}" style="padding: 4px 10px; font-size: 11px;">${status}</span>`;
}

// 시간 포맷팅
function formatTime(unixtime) {
    const date = new Date(unixtime * 1000);
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return hours + ':' + minutes;
}

// 데이터 전달 함수
function exportAgent14Data() {
    if (window.agentData && window.agentData.agent14) {
        console.log('=== Agent 14 분석 결과 ===');
        console.log(JSON.stringify(window.agentData.agent14, null, 2));
        alert('Agent 14 분석 결과가 window.agentData.agent14에 저장되었습니다. 콘솔을 확인하세요.');
    } else {
        alert('저장된 Agent 14 데이터가 없습니다.');
    }
}
