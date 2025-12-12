/**
 * Agent 11 - Problem Notes Analysis UI
 * File: agents/agent11_problem_notes/ui/agent.js
 */

function showAgent11Modal() {
    const overlay = document.getElementById('modal-overlay');
    const wrapper = document.getElementById('modal-content-wrapper');

    wrapper.innerHTML = `
        <div class="modal-content" style="width: 80vw; max-width: 80vw;">
            <div class="modal-header">
                <h2>📋 Step 11: 문제노트 분석</h2>
                <button class="modal-close" onclick="closeModal()">×</button>
            </div>

            <div class="modal-body" style="max-height: 75vh; overflow-y: auto;">
                <!-- 기간 및 페이지네이션 컨트롤 -->
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
                    <button class="btn btn-secondary" id="agent11-prev-week">◀ 이전 1주</button>
                    <div id="agent11-period-display" style="font-size:14px; color:#374151;">기간: -</div>
                    <button class="btn btn-secondary" id="agent11-next-week">다음 1주 ▶</button>
                </div>

                <!-- 탭 네비게이션 -->
                <div style="display:flex; gap:8px; margin-bottom:16px; border-bottom:2px solid #e5e7eb;">
                    <button class="agent11-tab" data-tab="attempt" style="padding:10px 20px; border:none; background:none; cursor:pointer; border-bottom:3px solid #3b82f6; font-weight:600; color:#3b82f6;">
                        풀이노트 (<span id="agent11-count-attempt">0</span>)
                    </button>
                    <button class="agent11-tab" data-tab="begin" style="padding:10px 20px; border:none; background:none; cursor:pointer; border-bottom:3px solid transparent; color:#6b7280;">
                        준비노트 (<span id="agent11-count-begin">0</span>)
                    </button>
                    <button class="agent11-tab" data-tab="essay" style="padding:10px 20px; border:none; background:none; cursor:pointer; border-bottom:3px solid transparent; color:#6b7280;">
                        서술평가 (<span id="agent11-count-essay">0</span>)
                    </button>
                </div>

                <!-- 데이터 테이블 -->
                <div style="overflow:auto; border:1px solid #e5e7eb; border-radius:8px;">
                    <table style="width:100%; border-collapse:collapse;">
                        <thead style="background:#f9fafb;">
                            <tr>
                                <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">문제 제목</th>
                                <th style="text-align:right; padding:10px; border-bottom:1px solid #e5e7eb;">총 필기량</th>
                                <th style="text-align:right; padding:10px; border-bottom:1px solid #e5e7eb;">소요시간</th>
                                <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">마지막 필기시점</th>
                                <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">생성일</th>
                                <th style="text-align:center; padding:10px; border-bottom:1px solid #e5e7eb;">상태</th>
                                <th style="text-align:center; padding:10px; border-bottom:1px solid #e5e7eb;">바로가기</th>
                            </tr>
                        </thead>
                        <tbody id="agent11-tbody"></tbody>
                    </table>
                </div>

                <!-- 분석 영역 -->
                <div style="background:#f9fafb; padding:16px; border-radius:8px; margin-top:16px;">
                    <h3 style="font-size:16px; font-weight:600; color:#1f2937; margin-bottom:8px;">💡 분석 결과</h3>
                    <div id="agent11-analysis-text" style="font-size:14px; color:#374151;">
                        분석 결과를 불러오는 중...
                    </div>
                </div>
            </div>

            <div class="modal-footer" style="padding: 20px; border-top: 1px solid #e5e7eb; display: flex; gap: 10px; justify-content: flex-end;">
                <button class="btn btn-secondary" onclick="closeModal()">닫기</button>
                <button class="btn btn-primary" onclick="completeAgent11()">확인 및 다음 단계</button>
            </div>
        </div>
    `;

    overlay.classList.add('active');

    // 이벤트 바인딩 및 데이터 로드
    window.agent11WeekOffset = 0;
    window.agent11CurrentTab = 'attempt'; // 기본 탭
    window.agent11Data = null; // 전역 데이터 저장
    bindAgent11Controls();
    loadAgent11Data();
}

function bindAgent11Controls() {
    const prevBtn = document.getElementById('agent11-prev-week');
    const nextBtn = document.getElementById('agent11-next-week');

    prevBtn.addEventListener('click', () => {
        window.agent11WeekOffset = (window.agent11WeekOffset || 0) + 1; // 과거로 이동
        loadAgent11Data();
    });

    nextBtn.addEventListener('click', () => {
        window.agent11WeekOffset = (window.agent11WeekOffset || 0) - 1; // 미래로 이동
        loadAgent11Data();
    });

    // 탭 클릭 이벤트
    const tabButtons = document.querySelectorAll('.agent11-tab');
    tabButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const tab = e.currentTarget.getAttribute('data-tab');
            window.agent11CurrentTab = tab;
            updateAgent11TabUI();
            renderAgent11CurrentTab();
        });
    });
}

async function loadAgent11Data() {
    try {
        const studentId = window.phpData ? window.phpData.studentId : null;
        if (!studentId) {
            console.error('Student ID not found - File: agents/agent11_problem_notes/ui/agent.js, Line: ' + new Error().lineNumber);
            return;
        }

        const weekOffset = window.agent11WeekOffset || 0;
        const url = `agents/agent11_problem_notes/agent.php?userid=${studentId}&week_offset=${weekOffset}`;
        const res = await fetch(url);
        const json = await res.json();

        if (!json.success) {
            console.error('Failed to load agent11 data:', json.error);
            renderAgent11Rows([]);
            document.getElementById('agent11-analysis-text').textContent = '데이터 로드 실패';
            return;
        }

        // 전역 데이터 저장
        window.agent11Data = json.data;

        // 기간 표시
        const start = new Date(json.data.period.start * 1000);
        const end = new Date(json.data.period.end * 1000);
        const periodText = `${formatDate(start)} ~ ${formatDate(end)}`;
        document.getElementById('agent11-period-display').textContent = `기간: ${periodText}`;

        // 버튼 활성/비활성
        const nextBtn = document.getElementById('agent11-next-week');
        nextBtn.disabled = (weekOffset <= 0);
        const prevBtn = document.getElementById('agent11-prev-week');
        prevBtn.disabled = false;

        // 탭별 카운트 업데이트
        document.getElementById('agent11-count-attempt').textContent = json.data.tabs.attempt.count;
        document.getElementById('agent11-count-begin').textContent = json.data.tabs.begin.count;
        document.getElementById('agent11-count-essay').textContent = json.data.tabs.essay.count;

        // 현재 탭 렌더링
        renderAgent11CurrentTab();

        // 분석 텍스트
        document.getElementById('agent11-analysis-text').textContent = json.data.analysis_text || '분석 결과가 없습니다.';
    } catch (err) {
        console.error('Error loading Agent11 data - File: agents/agent11_problem_notes/ui/agent.js', err);
        document.getElementById('agent11-analysis-text').textContent = '데이터 로드 실패';
        renderAgent11Rows([]);
    }
}

function updateAgent11TabUI() {
    const tabButtons = document.querySelectorAll('.agent11-tab');
    tabButtons.forEach(btn => {
        const tab = btn.getAttribute('data-tab');
        if (tab === window.agent11CurrentTab) {
            btn.style.borderBottom = '3px solid #3b82f6';
            btn.style.color = '#3b82f6';
            btn.style.fontWeight = '600';
        } else {
            btn.style.borderBottom = '3px solid transparent';
            btn.style.color = '#6b7280';
            btn.style.fontWeight = '400';
        }
    });
}

function renderAgent11CurrentTab() {
    if (!window.agent11Data) {
        renderAgent11Rows([]);
        return;
    }

    const tab = window.agent11CurrentTab;
    const tabData = window.agent11Data.tabs[tab];
    if (!tabData) {
        renderAgent11Rows([]);
        return;
    }

    renderAgent11Rows(tabData.rows || []);
}

function renderAgent11Rows(rows) {
    const tbody = document.getElementById('agent11-tbody');
    if (!tbody) return;

    if (!rows || rows.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" style="padding:14px; text-align:center; color:#6b7280;">표시할 데이터가 없습니다.</td></tr>`;
        return;
    }

    tbody.innerHTML = rows.map(r => {
        const lastStroke = r.tlaststroke ? formatDateTime(new Date(r.tlaststroke * 1000)) : '-';
        const createdAt = r.timecreated ? formatDateTime(new Date(r.timecreated * 1000)) : '-';
        const usedTime = typeof r.usedtime === 'number' && r.usedtime > 0 ? formatDuration(r.usedtime) : '-';
        const link = r.url ? `<a href="${r.url}" target="_blank" style="color:#3b82f6; text-decoration:none;">바로가기</a>` : '-';
        const statusText = getStatusText(r.status || '');
        const statusColor = getStatusColor(r.status || '');

        return `
            <tr>
                <td style="padding:10px; border-bottom:1px solid #f3f4f6;">${escapeHtml(r.contentstitle || '')}</td>
                <td style="padding:10px; border-bottom:1px solid #f3f4f6; text-align:right;">${Number(r.nstroke || 0).toLocaleString()}</td>
                <td style="padding:10px; border-bottom:1px solid #f3f4f6; text-align:right;">${usedTime}</td>
                <td style="padding:10px; border-bottom:1px solid #f3f4f6;">${lastStroke}</td>
                <td style="padding:10px; border-bottom:1px solid #f3f4f6;">${createdAt}</td>
                <td style="padding:10px; border-bottom:1px solid #f3f4f6; text-align:center;">
                    <span style="background:${statusColor}; color:white; padding:4px 10px; border-radius:4px; font-size:12px;">${statusText}</span>
                </td>
                <td style="padding:10px; border-bottom:1px solid #f3f4f6; text-align:center;">${link}</td>
            </tr>
        `;
    }).join('');
}

function getStatusText(status) {
    const statusMap = {
        'begin': '풀이중',
        'incorrect': '준비중',
        'exam': '시험',
        'complete': '완료',
        'review': '복습'
    };
    return statusMap[status] || status;
}

function getStatusColor(status) {
    const colorMap = {
        'begin': '#3b82f6',      // 파랑
        'incorrect': '#f59e0b',   // 주황
        'exam': '#ef4444',        // 빨강
        'complete': '#10b981',    // 초록
        'review': '#8b5cf6'       // 보라
    };
    return colorMap[status] || '#6b7280';
}

function completeAgent11() {
    if (window.state) {
        if (!window.state.stepData) window.state.stepData = {};
        if (!window.state.stepData[11]) window.state.stepData[11] = { inputs: {}, outputs: {} };
        window.state.stepData[11].outputs['문제노트 분석'] = '완료';
        window.state.completedSteps.add(11);
        window.state.currentStep = 12;
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

console.log('✅ Agent 11 UI loaded');
