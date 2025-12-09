/**
 * Agent 15: Problem Redefinition - Panel Render Function
 * File: agents/agent15_problem_redefinition/ui/agent.js:1
 *
 * 우측 패널에 iframe으로 agent15 UI 표시
 */

/**
 * Agent 15 패널 렌더링 함수
 * @param {HTMLElement} panelEl - 우측 패널 요소
 */
function renderAgent15Panel(panelEl) {
    console.log('[agent.js:14] renderAgent15Panel called');

    if (!panelEl) {
        console.error('[agent.js:17] Panel element not provided (file: agent.js, line: 17)');
        return;
    }

    // studentId 가져오기
    const studentId = window.phpData?.studentId || window.currentUserId || 2;
    console.log('[agent.js:23] Student ID:', studentId);

    // iframe URL 생성
    const iframeUrl = `agents/agent15_problem_redefinition/ui/index.php?userid=${studentId}`;
    console.log('[agent.js:27] Iframe URL:', iframeUrl);

    // 패널 HTML 생성
    panelEl.innerHTML = `
        <div style="height: 100%; display: flex; flex-direction: column;">
            <!-- 헤더 -->
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px; padding:20px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border-radius: 8px 8px 0 0;">
                <div style="font-size:28px;">🎯</div>
                <div>
                    <div style="font-weight:700; font-size:18px;">문제 재정의 & 개선방안</div>
                    <div style="font-size:13px; opacity:0.9;">Step 15 - GPT 기반 자동 분석</div>
                </div>
            </div>

            <!-- iframe 컨테이너 -->
            <div style="flex: 1; overflow: hidden; padding: 0;">
                <iframe
                    id="agent15-iframe"
                    src="${iframeUrl}"
                    style="width: 100%; height: 100%; border: none; display: block;"
                    frameborder="0"
                    allowfullscreen>
                </iframe>
            </div>

            <!-- 로딩 표시 (iframe 로드 완료 시 자동 숨김) -->
            <div id="agent15-loading" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; color: #64748b;">
                <div style="font-size: 48px; margin-bottom: 16px;">🔄</div>
                <div style="font-size: 16px; font-weight: 600;">Agent 15 로딩 중...</div>
            </div>
        </div>
    `;

    // iframe 로드 이벤트 리스너
    const iframe = document.getElementById('agent15-iframe');
    const loadingEl = document.getElementById('agent15-loading');

    if (iframe) {
        iframe.addEventListener('load', function() {
            console.log('[agent.js:69] Agent 15 iframe loaded successfully');

            // 로딩 표시 숨기기
            if (loadingEl) {
                loadingEl.style.display = 'none';
            }

            // iframe에 postMessage로 studentId 전달
            try {
                iframe.contentWindow.postMessage({
                    type: 'setUserId',
                    userId: studentId
                }, '*');
                console.log('[agent.js:81] Posted studentId to iframe:', studentId);
            } catch (e) {
                console.error('[agent.js:83] Failed to post message to iframe (file: agent.js, line: 83):', e);
            }
        });

        iframe.addEventListener('error', function() {
            console.error('[agent.js:88] Agent 15 iframe failed to load (file: agent.js, line: 88)');

            if (loadingEl) {
                loadingEl.innerHTML = `
                    <div style="font-size: 48px; margin-bottom: 16px;">❌</div>
                    <div style="font-size: 16px; font-weight: 600; color: #ef4444;">Agent 15 로드 실패</div>
                    <div style="font-size: 14px; margin-top: 8px; color: #6b7280;">iframe URL을 확인하세요</div>
                `;
            }
        });
    }

    console.log('[agent.js:102] renderAgent15Panel completed');
}

// 전역으로 함수 노출
window.renderAgent15Panel = renderAgent15Panel;

console.log('✅ Agent 15: agent.js loaded - renderAgent15Panel function registered');
