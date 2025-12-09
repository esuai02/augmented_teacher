/**
 * Agent 10 - Concept Notes Analysis UI
 * File: agents/agent10_concept_notes/ui/agent.js
 */

function showAgent10Modal() {
    const overlay = document.getElementById('modal-overlay');
    const wrapper = document.getElementById('modal-content-wrapper');

    wrapper.innerHTML = `
        <div class="modal-content" style="width: 70vw; max-width: 70vw;">
            <div class="modal-header">
                <h2>📝 Step 10: 개념노트 분석</h2>
                <button class="modal-close" onclick="closeModal()">×</button>
            </div>

            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <!-- 기간 및 페이지네이션 컨트롤 -->
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
                    <button class="btn btn-secondary" id="agent10-prev-week">◀ 이전 1주</button>
                    <div id="agent10-period-display" style="font-size:14px; color:#374151;">기간: -</div>
                    <button class="btn btn-secondary" id="agent10-next-week">다음 1주 ▶</button>
                </div>

                <!-- 데이터 테이블 -->
                <div style="overflow:auto; border:1px solid #e5e7eb; border-radius:8px;">
                    <table style="width:100%; border-collapse:collapse;">
                        <thead style="background:#f9fafb;">
                            <tr>
                                <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">개념 제목</th>
                                <th style="text-align:right; padding:10px; border-bottom:1px solid #e5e7eb;">총 필기량</th>
                                <th style="text-align:right; padding:10px; border-bottom:1px solid #e5e7eb;">소요시간</th>
                                <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">마지막 필기시점</th>
                                <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">생성일</th>
                                <th style="text-align:center; padding:10px; border-bottom:1px solid #e5e7eb;">바로가기</th>
                            </tr>
                        </thead>
                        <tbody id="agent10-tbody"></tbody>
                    </table>
                </div>

                <!-- 분석 영역 -->
                <div style="background:#f9fafb; padding:16px; border-radius:8px; margin-top:16px;">
                    <h3 style="font-size:16px; font-weight:600; color:#1f2937; margin-bottom:8px;">💡 분석 결과</h3>
                    <div id="agent10-analysis-text" style="font-size:14px; color:#374151;">
                        가상 분석 결과를 표시합니다...
                    </div>
                </div>
            </div>

            <div class="modal-footer" style="padding: 20px; border-top: 1px solid #e5e7eb; display: flex; gap: 10px; justify-content: flex-end;">
                <button class="btn btn-secondary" onclick="closeModal()">닫기</button>
                <button class="btn btn-primary" onclick="completeAgent10()">확인 및 다음 단계</button>
            </div>
        </div>
    `;

    overlay.classList.add('active');

    // 이벤트 바인딩 및 데이터 로드
    // 모달을 열 때 주 오프셋 초기화 (현재 주)
    window.agent10WeekOffset = 0;
    bindAgent10Controls();
    loadAgent10Data();
}

function bindAgent10Controls() {
    const prevBtn = document.getElementById('agent10-prev-week');
    const nextBtn = document.getElementById('agent10-next-week');

    prevBtn.addEventListener('click', () => {
        window.agent10WeekOffset = (window.agent10WeekOffset || 0) + 1; // 과거로 이동
        loadAgent10Data();
    });

    nextBtn.addEventListener('click', () => {
        window.agent10WeekOffset = (window.agent10WeekOffset || 0) - 1; // 미래로 이동(보통 데이터 없음)
        loadAgent10Data();
    });
}

async function loadAgent10Data() {
    try {
        const studentId = window.phpData ? window.phpData.studentId : null;
        if (!studentId) {
            console.error('Student ID not found - File: agents/agent10_concept_notes/ui/agent.js, Line: ' + 78);
            return;
        }

        const weekOffset = window.agent10WeekOffset || 0;
        const url = `agents/agent10_concept_notes/agent.php?userid=${studentId}&week_offset=${weekOffset}`;
        const res = await fetch(url);
        const json = await res.json();

        if (!json.success) {
            console.error('Failed to load agent10 data:', json.error);
            renderAgent10Rows([]);
            document.getElementById('agent10-analysis-text').textContent = '데이터 로드 실패';
            return;
        }

        // 기간 표시
        const start = new Date(json.data.period.start * 1000);
        const end = new Date(json.data.period.end * 1000);
        const periodText = `${formatDate(start)} ~ ${formatDate(end)}`;
        document.getElementById('agent10-period-display').textContent = `기간: ${periodText}`;

        // 버튼 활성/비활성 단순화
        // - 이전 1주: 항상 이동 가능 (데이터 없으면 "표시할 데이터가 없습니다" 노출)
        // - 다음 1주: 현재 주(weekOffset <= 0)에서는 비활성, 과거 주에서는 활성
        const nextBtn = document.getElementById('agent10-next-week');
        nextBtn.disabled = (weekOffset <= 0);
        const prevBtn = document.getElementById('agent10-prev-week');
        prevBtn.disabled = false;

        renderAgent10Rows(json.data.rows || []);
        document.getElementById('agent10-analysis-text').textContent = json.data.analysis_text || '분석 결과가 없습니다.';
    } catch (err) {
        console.error('Error loading Agent10 data - File: agents/agent10_concept_notes/ui/agent.js, Line: ' + 110, err);
        document.getElementById('agent10-analysis-text').textContent = '데이터 로드 실패';
        renderAgent10Rows([]);
    }
}

function renderAgent10Rows(rows) {
    const tbody = document.getElementById('agent10-tbody');
    if (!tbody) return;
    if (!rows || rows.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" style="padding:14px; text-align:center; color:#6b7280;">표시할 데이터가 없습니다.</td></tr>`;
        return;
    }

    tbody.innerHTML = rows.map(r => {
        const lastStroke = r.tlaststroke ? formatDateTime(new Date(r.tlaststroke * 1000)) : '-';
        const createdAt = r.timecreated ? formatDateTime(new Date(r.timecreated * 1000)) : '-';
        const usedTime = typeof r.usedtime === 'number' && r.usedtime > 0 ? formatDuration(r.usedtime) : '-';
        const link = r.url ? `<a href="${r.url}" target="_blank">바로가기</a>` : '-';

        return `
            <tr>
                <td style="padding:10px; border-bottom:1px solid #f3f4f6;">${escapeHtml(r.contentstitle || '')}</td>
                <td style="padding:10px; border-bottom:1px solid #f3f4f6; text-align:right;">${Number(r.nstroke || 0).toLocaleString()}</td>
                <td style="padding:10px; border-bottom:1px solid #f3f4f6; text-align:right;">${usedTime}</td>
                <td style="padding:10px; border-bottom:1px solid #f3f4f6;">${lastStroke}</td>
                <td style="padding:10px; border-bottom:1px solid #f3f4f6;">${createdAt}</td>
                <td style="padding:10px; border-bottom:1px solid #f3f4f6; text-align:center;">${link}</td>
            </tr>
        `;
    }).join('');
}

function completeAgent10() {
    if (window.state) {
        if (!window.state.stepData) window.state.stepData = {};
        if (!window.state.stepData[10]) window.state.stepData[10] = { inputs: {}, outputs: {} };
        window.state.stepData[10].outputs['개념노트 분석'] = '완료';
        window.state.completedSteps.add(10);
        window.state.currentStep = 11;
        if (window.renderWorkflow) window.renderWorkflow();
    }
    closeModal();
}

function formatDate(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

function formatDateTime(date) {
    const h = String(date.getHours()).padStart(2, '0');
    const mi = String(date.getMinutes()).padStart(2, '0');
    return `${formatDate(date)} ${h}:${mi}`;
}

// usedtime(초 단위 가정)을 사람이 읽기 쉬운 형식으로 변환
function formatDuration(seconds) {
    const s = Math.max(0, Math.floor(seconds || 0));
    const h = Math.floor(s / 3600);
    const m = Math.floor((s % 3600) / 60);
    const sec = s % 60;
    if (h > 0) {
        return `${h}h ${m}m`;
    }
    if (m > 0) {
        return `${m}m ${sec}s`;
    }
    return `${sec}s`;
}

function escapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

console.log('✅ Agent 10 UI loaded');


