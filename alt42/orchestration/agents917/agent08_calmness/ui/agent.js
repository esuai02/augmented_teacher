/**
 * Agent 08 - Calmness Analysis UI
 * File: agents/agent08_calmness/ui/agent.js
 */

// Agent 08 모달 표시
function showAgent08Modal() {
    const overlay = document.getElementById('modal-overlay');
    const wrapper = document.getElementById('modal-content-wrapper');

    wrapper.innerHTML = `
        <div class="modal-content" style="width: 50vw; max-width: 50vw;">
            <div class="modal-header">
                <h2>😌 Step 8: 침착도 분석</h2>
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
                            <span style="color: #1f2937; margin-left: 8px;" id="calmness-student-id">
                                ${window.phpData ? window.phpData.studentId : '-'}
                            </span>
                        </div>
                        <div>
                            <span style="color: #6b7280; font-weight: 500;">학생 이름:</span>
                            <span style="color: #1f2937; margin-left: 8px;" id="calmness-student-name">
                                ${window.phpData ? window.phpData.studentName : '-'}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- 현재 침착도 섹션 -->
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 24px; border-radius: 12px; margin-bottom: 20px; color: white;">
                    <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 16px; text-align: center;">
                        현재 침착도
                    </h3>
                    <div style="text-align: center;">
                        <div style="font-size: 48px; font-weight: 700; margin-bottom: 8px;" id="current-score-display">
                            -
                        </div>
                        <div style="font-size: 18px; font-weight: 500; opacity: 0.9;" id="current-level-display">
                            데이터 로딩 중...
                        </div>
                    </div>
                </div>

                <!-- 추이 분석 섹션 -->
                <div style="background: #f9fafb; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
                    <h3 style="font-size: 16px; font-weight: 600; color: #1f2937; margin-bottom: 12px;">
                        📊 추이 분석
                    </h3>
                    <div style="display: grid; gap: 12px;">
                        <div style="display: flex; justify-content: space-between; font-size: 14px;">
                            <span style="color: #6b7280;">주간 기준:</span>
                            <span style="color: #1f2937; font-weight: 600;" id="baseline-score">-</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 14px;">
                            <span style="color: #6b7280;">변화 추이:</span>
                            <span style="color: #1f2937; font-weight: 600;" id="trend-display">-</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 14px;">
                            <span style="color: #6b7280;">변화량:</span>
                            <span style="color: #1f2937; font-weight: 600;" id="delta-display">-</span>
                        </div>
                    </div>
                </div>

                <!-- 통계 섹션 -->
                <div style="background: #f9fafb; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
                    <h3 style="font-size: 16px; font-weight: 600; color: #1f2937; margin-bottom: 12px;">
                        📈 최근 7일 통계
                    </h3>
                    <div style="display: grid; gap: 12px;">
                        <div style="display: flex; justify-content: space-between; font-size: 14px;">
                            <span style="color: #6b7280;">데이터 건수:</span>
                            <span style="color: #1f2937; font-weight: 600;" id="stats-count">-</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 14px;">
                            <span style="color: #6b7280;">평균 점수:</span>
                            <span style="color: #1f2937; font-weight: 600;" id="stats-average">-</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 14px;">
                            <span style="color: #6b7280;">최저 ~ 최고:</span>
                            <span style="color: #1f2937; font-weight: 600;" id="stats-range">-</span>
                        </div>
                    </div>
                </div>

                <!-- 인사이트 섹션 -->
                <div style="background: #eff6ff; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
                    <h3 style="font-size: 16px; font-weight: 600; color: #1f2937; margin-bottom: 12px;">
                        💡 분석 인사이트
                    </h3>
                    <div id="insights-display" style="font-size: 14px; color: #374151; line-height: 1.6;">
                        데이터를 분석 중입니다...
                    </div>
                </div>

                <!-- 추천사항 섹션 -->
                <div style="background: #f0fdf4; padding: 16px; border-radius: 8px;">
                    <h3 style="font-size: 16px; font-weight: 600; color: #1f2937; margin-bottom: 12px;">
                        🎯 추천사항
                    </h3>
                    <div id="recommendations-display" style="font-size: 14px; color: #374151; line-height: 1.6;">
                        추천사항을 생성 중입니다...
                    </div>
                </div>
            </div>

            <div class="modal-footer" style="padding: 20px; border-top: 1px solid #e5e7eb; display: flex; gap: 10px; justify-content: flex-end;">
                <button class="btn btn-secondary" onclick="closeModal()">닫기</button>
                <button class="btn btn-primary" onclick="completeAgent08()">확인 및 다음 단계</button>
            </div>
        </div>
    `;

    overlay.classList.add('active');

    // 데이터 로드
    loadAgent08Data();
}

// Agent 08 데이터 로드
async function loadAgent08Data() {
    try {
        const studentId = window.phpData ? window.phpData.studentId : null;
        if (!studentId) {
            console.error('Student ID not found - File: agent.js, Line: 151');
            return;
        }

        const response = await fetch(`agents/agent08_calmness/agent.php?userid=${studentId}`);
        const result = await response.json();

        if (result.success) {
            const data = result.data;

            // 현재 점수 및 레벨 표시
            document.getElementById('current-score-display').textContent =
                data.current_score !== null ? data.current_score + '점' : '데이터 없음';
            document.getElementById('current-level-display').textContent = data.current_level;

            // 추이 분석 표시
            document.getElementById('baseline-score').textContent =
                data.baseline_score !== null ? data.baseline_score + '점' : '데이터 없음';
            document.getElementById('trend-display').textContent =
                data.trend_emoji + ' ' + data.trend;
            document.getElementById('delta-display').textContent =
                data.delta > 0 ? '+' + data.delta + '점' : data.delta + '점';

            // 통계 표시
            document.getElementById('stats-count').textContent = data.statistics.count + '건';
            document.getElementById('stats-average').textContent = data.statistics.average + '점';
            document.getElementById('stats-range').textContent =
                data.statistics.min + '점 ~ ' + data.statistics.max + '점';

            // 인사이트 표시
            const insightsHtml = data.insights.map(insight =>
                `<div style="margin-bottom: 8px;">• ${insight}</div>`
            ).join('');
            document.getElementById('insights-display').innerHTML = insightsHtml || '분석 가능한 데이터가 없습니다.';

            // 추천사항 표시
            const recommendationsHtml = data.recommendations.map(rec =>
                `<div style="margin-bottom: 8px;">${rec}</div>`
            ).join('');
            document.getElementById('recommendations-display').innerHTML = recommendationsHtml || '추천사항이 없습니다.';

            console.log('✅ Agent 08 data loaded:', result);
        } else {
            console.error('❌ Failed to load agent data:', result.error);
            document.getElementById('current-score-display').textContent = '오류';
            document.getElementById('current-level-display').textContent = '데이터 로드 실패';
        }
    } catch (error) {
        console.error('❌ Error loading agent data - File: agent.js, Line: 195:', error);
        document.getElementById('current-score-display').textContent = '오류';
        document.getElementById('current-level-display').textContent = '데이터 로드 실패';
    }
}

// Agent 08 완료 처리
function completeAgent08() {
    // 상태 업데이트
    if (window.state) {
        if (!window.state.stepData) window.state.stepData = {};
        if (!window.state.stepData[8]) window.state.stepData[8] = { inputs: {}, outputs: {} };

        window.state.stepData[8].outputs['침착도 분석'] = '완료';
        window.state.stepData[8].outputs['상태'] = document.getElementById('current-level-display').textContent;

        // Step 8 완료 처리
        window.state.completedSteps.add(8);
        window.state.currentStep = 9;
    }

    // 모달 닫기
    closeModal();

    // 워크플로우 재렌더링
    if (window.renderWorkflow) {
        window.renderWorkflow();
    }

    console.log('✅ Agent 08 completed successfully');
}

console.log('✅ Agent 08 UI loaded');
