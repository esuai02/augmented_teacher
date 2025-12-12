/**
 * Agent 12 - Rest Routine Analysis UI
 * File: agents/agent12_rest_routine/ui/agent.js
 */

// Agent 12 모달 표시
function showAgent12Modal() {
    const overlay = document.getElementById('modal-overlay');
    const wrapper = document.getElementById('modal-content-wrapper');
    const studentId = window.phpData ? window.phpData.studentId : null;

    if (!studentId) {
        alert('학생 ID를 찾을 수 없습니다.');
        return;
    }

    wrapper.innerHTML = `
        <div class="modal-content" style="width: 50vw; max-width: 50vw;">
            <div class="modal-header">
                <h2>⏱️ Step 12: 휴식 루틴 분석</h2>
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
                            <span style="color: #1f2937; margin-left: 8px;" id="rest-student-id">
                                ${studentId}
                            </span>
                        </div>
                        <div>
                            <span style="color: #6b7280; font-weight: 500;">학생 이름:</span>
                            <span style="color: #1f2937; margin-left: 8px;" id="rest-student-name">
                                ${window.phpData ? window.phpData.studentName : '-'}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- 현재 휴식 패턴 섹션 -->
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 24px; border-radius: 12px; margin-bottom: 20px; color: white;">
                    <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 16px; text-align: center;">
                        현재 휴식 패턴
                    </h3>
                    <div style="text-align: center;">
                        <div style="font-size: 36px; font-weight: 700; margin-bottom: 8px;" id="pattern-type-display">
                            -
                        </div>
                        <div style="font-size: 14px; font-weight: 500; opacity: 0.9;" id="pattern-description">
                            데이터 로딩 중...
                        </div>
                    </div>
                </div>

                <!-- 통계 섹션 -->
                <div style="background: #f9fafb; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
                    <h3 style="font-size: 16px; font-weight: 600; color: #1f2937; margin-bottom: 12px;">
                        📊 휴식 통계 (최근 30일)
                    </h3>
                    <div style="display: grid; gap: 12px;">
                        <div style="display: flex; justify-content: space-between; font-size: 14px;">
                            <span style="color: #6b7280;">휴식 횟수:</span>
                            <span style="color: #1f2937; font-weight: 600;" id="rest-count">-</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 14px;">
                            <span style="color: #6b7280;">평균 간격:</span>
                            <span style="color: #1f2937; font-weight: 600;" id="avg-interval">-</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 14px;">
                            <span style="color: #6b7280;">최소 ~ 최대:</span>
                            <span style="color: #1f2937; font-weight: 600;" id="interval-range">-</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 14px;">
                            <span style="color: #6b7280;">일관성 점수:</span>
                            <span style="color: #1f2937; font-weight: 600;" id="consistency-score">-</span>
                        </div>
                    </div>
                </div>

                <!-- 인사이트 섹션 -->
                <div style="background: #eff6ff; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
                    <h3 style="font-size: 16px; font-weight: 600; color: #1f2937; margin-bottom: 12px;">
                        💡 분석 인사이트
                    </h3>
                    <ul id="insights-list" style="margin: 0; padding-left: 20px; color: #374151; font-size: 14px; line-height: 1.8;">
                        <li>데이터 로딩 중...</li>
                    </ul>
                </div>

                <!-- 추천사항 섹션 -->
                <div style="background: #fef3c7; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
                    <h3 style="font-size: 16px; font-weight: 600; color: #1f2937; margin-bottom: 12px;">
                        🎯 추천사항
                    </h3>
                    <ul id="recommendations-list" style="margin: 0; padding-left: 20px; color: #374151; font-size: 14px; line-height: 1.8;">
                        <li>데이터 로딩 중...</li>
                    </ul>
                </div>
            </div>

            <div class="modal-footer" style="display: flex; justify-content: space-between; padding: 16px; border-top: 1px solid #e5e7eb;">
                <button onclick="closeModal()" style="padding: 10px 20px; background: #6b7280; color: white; border: none; border-radius: 6px; cursor: pointer;">
                    닫기
                </button>
                <button onclick="exportAgent12Data()" style="padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 6px; cursor: pointer;">
                    다른 에이전트로 전달
                </button>
            </div>
        </div>
    `;

    overlay.classList.add('active');

    // AJAX 호출 - 데이터 로드
    fetch(`agents/agent12_rest_routine/agent.php?userid=${studentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const d = data.data;

                // 패턴 유형 표시
                document.getElementById('pattern-type-display').textContent = d.pattern_type;

                // 통계 표시
                document.getElementById('rest-count').textContent = d.rest_count + '회';
                document.getElementById('avg-interval').textContent = d.avg_interval_minutes + '분';
                document.getElementById('interval-range').textContent =
                    d.min_interval + '분 ~ ' + d.max_interval + '분';
                document.getElementById('consistency-score').textContent =
                    d.consistency_score + '점';

                // 인사이트 표시
                const insightsList = document.getElementById('insights-list');
                if (d.insights && d.insights.length > 0) {
                    insightsList.innerHTML = d.insights.map(insight =>
                        `<li>${insight}</li>`
                    ).join('');
                } else {
                    insightsList.innerHTML = '<li>인사이트가 없습니다.</li>';
                }

                // 추천사항 표시
                const recList = document.getElementById('recommendations-list');
                if (d.recommendations && d.recommendations.length > 0) {
                    recList.innerHTML = d.recommendations.map(rec =>
                        `<li>${rec}</li>`
                    ).join('');
                } else {
                    recList.innerHTML = '<li>추천사항이 없습니다.</li>';
                }

                // 에이전트 간 데이터 저장
                window.agentData = window.agentData || {};
                window.agentData.agent12 = {
                    timestamp: new Date(),
                    studentId: d.student_id,
                    patternType: d.pattern_type,
                    avgInterval: d.avg_interval_minutes,
                    restCount: d.rest_count,
                    consistencyScore: d.consistency_score,
                    insights: d.insights,
                    recommendations: d.recommendations
                };

                console.log('Agent 12 데이터가 저장되었습니다:', window.agentData.agent12);

            } else {
                alert('에러: ' + (data.error || '알 수 없는 오류가 발생했습니다.'));
                console.error('Agent 12 에러:', data.error);
            }
        })
        .catch(error => {
            alert('Error in agent.js: ' + error.message);
            console.error('Agent 12 AJAX 에러:', error);
        });
}

// 에이전트 간 데이터 전달 함수
function exportAgent12Data() {
    if (window.agentData && window.agentData.agent12) {
        console.log('=== Agent 12 분석 결과 ===');
        console.log('학생 ID:', window.agentData.agent12.studentId);
        console.log('휴식 패턴 유형:', window.agentData.agent12.patternType);
        console.log('평균 휴식 간격:', window.agentData.agent12.avgInterval, '분');
        console.log('휴식 횟수:', window.agentData.agent12.restCount, '회');
        console.log('일관성 점수:', window.agentData.agent12.consistencyScore, '점');
        console.log('전체 데이터:', window.agentData.agent12);
        console.log('========================');

        alert(
            'Agent 12 분석 결과가 window.agentData.agent12에 저장되었습니다.\n\n' +
            '패턴 유형: ' + window.agentData.agent12.patternType + '\n' +
            '평균 간격: ' + window.agentData.agent12.avgInterval + '분\n' +
            '휴식 횟수: ' + window.agentData.agent12.restCount + '회\n\n' +
            '자세한 내용은 브라우저 콘솔을 확인하세요.'
        );
    } else {
        alert('저장된 Agent 12 데이터가 없습니다.');
    }
}
