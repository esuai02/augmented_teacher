/**
 * Agent 13 - Learning Dropout Analysis UI
 * File: agents/agent13_learning_dropout/ui/agent.js
 */

// Agent 13 모달 표시
function showAgent13Modal() {
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
                <h2>🚨 Step 13: 학습 이탈 분석</h2>
                <button class="modal-close" onclick="closeModal()">×</button>
            </div>

            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <div style="background: #f9fafb; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
                    <h3 style="font-size: 16px; font-weight: 600; color: #1f2937; margin-bottom: 12px;">📋 학생 정보</h3>
                    <div style="display: grid; gap: 8px; font-size: 14px;">
                        <div><span style="color: #6b7280; font-weight: 500;">학생 ID:</span><span style="color: #1f2937; margin-left: 8px;" id="dropout-student-id">${studentId}</span></div>
                        <div><span style="color: #6b7280; font-weight: 500;">학생 이름:</span><span style="color: #1f2937; margin-left: 8px;" id="dropout-student-name">${window.phpData ? window.phpData.studentName : '-'}</span></div>
                    </div>
                </div>

                <div style="background: linear-gradient(135deg, #ff6a88 0%, #ff99ac 100%); padding: 24px; border-radius: 12px; margin-bottom: 20px; color: white;">
                    <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 16px; text-align: center;">현재 위험 등급</h3>
                    <div style="text-align: center;">
                        <div style="font-size: 36px; font-weight: 700; margin-bottom: 8px;" id="risk-tier-display">-</div>
                        <div style="font-size: 14px; font-weight: 500; opacity: 0.9;" id="risk-legend">데이터 로딩 중...</div>
                    </div>
                </div>

                <div style="background: #f9fafb; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
                    <h3 style="font-size: 16px; font-weight: 600; color: #1f2937; margin-bottom: 12px;">📊 핵심 지표 (최근 24시간)</h3>
                    <div style="display: grid; gap: 12px;">
                        <div style="display: flex; justify-content: space-between; font-size: 14px;"><span style="color: #6b7280;">이탈 경고(ninactive):</span><span style="color: #1f2937; font-weight: 600;" id="ninactive">-</span></div>
                        <div style="display: flex; justify-content: space-between; font-size: 14px;"><span style="color: #6b7280;">루틴 지연 블록(nlazy/20):</span><span style="color: #1f2937; font-weight: 600;" id="nlazy-blocks">-</span></div>
                        <div style="display: flex; justify-content: space-between; font-size: 14px;"><span style="color: #6b7280;">지연 시청(👀):</span><span style="color: #1f2937; font-weight: 600;" id="eye-flag">-</span></div>
                        <div style="display: flex; justify-content: space-between; font-size: 14px;"><span style="color: #6b7280;">지연 시청 시간(분):</span><span style="color: #1f2937; font-weight: 600;" id="eye-min">-</span></div>
                        <div style="display: flex; justify-content: space-between; font-size: 14px;"><span style="color: #6b7280;">무입력 지속(분):</span><span style="color: #1f2937; font-weight: 600;" id="tlaststroke-min">-</span></div>
                        <div style="display: flex; justify-content: space-between; font-size: 14px;"><span style="color: #6b7280;">npomodoro / kpomodoro / pm:</span><span style="color: #1f2937; font-weight: 600;" id="pomodoro">-</span></div>
                    </div>
                </div>

                <div style="background: #eff6ff; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
                    <h3 style="font-size: 16px; font-weight: 600; color: #1f2937; margin-bottom: 12px;">💡 분석 인사이트</h3>
                    <ul id="insights-list" style="margin: 0; padding-left: 20px; color: #374151; font-size: 14px; line-height: 1.8;">
                        <li>데이터 로딩 중...</li>
                    </ul>
                </div>

                <div style="background: #fef3c7; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
                    <h3 style="font-size: 16px; font-weight: 600; color: #1f2937; margin-bottom: 12px;">🎯 추천 액션</h3>
                    <ul id="recommendations-list" style="margin: 0; padding-left: 20px; color: #374151; font-size: 14px; line-height: 1.8;">
                        <li>데이터 로딩 중...</li>
                    </ul>
                </div>
            </div>

            <div class="modal-footer" style="display: flex; justify-content: space-between; padding: 16px; border-top: 1px solid #e5e7eb;">
                <button onclick="closeModal()" style="padding: 10px 20px; background: #6b7280; color: white; border: none; border-radius: 6px; cursor: pointer;">닫기</button>
                <button onclick="exportAgent13Data()" style="padding: 10px 20px; background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer;">다른 에이전트로 전달</button>
            </div>
        </div>
    `;

    overlay.classList.add('active');

    fetch(`agents/agent13_learning_dropout/agent.php?userid=${studentId}`)
        .then(r => r.json())
        .then(res => {
            if (!res.success) {
                alert('에러: ' + (res.error || '알 수 없는 오류'));
                return;
            }

            const d = res.data;
            document.getElementById('risk-tier-display').textContent = d.risk_tier.toUpperCase();
            document.getElementById('risk-legend').textContent = `윈도우: ${new Date(d.window.from * 1000).toLocaleString()} ~ ${new Date(d.window.to * 1000).toLocaleString()}`;

            document.getElementById('ninactive').textContent = d.metrics.ninactive + '회';
            document.getElementById('nlazy-blocks').textContent = d.metrics.nlazy_blocks + ' 블록';
            document.getElementById('eye-flag').textContent = d.metrics.eye_flag ? '예' : '아니오';
            document.getElementById('eye-min').textContent = (d.metrics.eye_timespent_min ?? '-') + '';
            document.getElementById('tlaststroke-min').textContent = (d.metrics.tlaststroke_min ?? '-') + '';
            document.getElementById('pomodoro').textContent = `${d.metrics.npomodoro} / ${d.metrics.kpomodoro} / ${d.metrics.pmresult}`;

            const insightsList = document.getElementById('insights-list');
            insightsList.innerHTML = (d.insights || []).map(x => `<li>${x}</li>`).join('') || '<li>인사이트 없음</li>';

            const recList = document.getElementById('recommendations-list');
            recList.innerHTML = (d.recommendations || []).map(x => `<li>${x}</li>`).join('') || '<li>추천 없음</li>';

            window.agentData = window.agentData || {};
            window.agentData.agent13 = {
                timestamp: new Date(),
                studentId: d.student_id,
                riskTier: d.risk_tier,
                metrics: d.metrics,
                insights: d.insights,
                recommendations: d.recommendations
            };
            console.log('Agent 13 데이터 저장됨:', window.agentData.agent13);
        })
        .catch(err => {
            alert('Error in Agent13 UI: ' + err.message);
            console.error('Agent 13 AJAX 에러:', err);
        });
}

function exportAgent13Data() {
    if (window.agentData && window.agentData.agent13) {
        console.log('=== Agent 13 분석 결과 ===');
        console.log(JSON.stringify(window.agentData.agent13, null, 2));
        alert('Agent 13 분석 결과가 window.agentData.agent13에 저장되었습니다. 콘솔을 확인하세요.');
    } else {
        alert('저장된 Agent 13 데이터가 없습니다.');
    }
}


