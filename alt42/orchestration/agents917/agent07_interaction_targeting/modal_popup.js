/**
 * Agent 07 (상호작용 타게팅) - Modal Popup System
 *
 * Modal popup for guidance mode selection and problem targeting
 * Pattern reference: orchestration_hs2 guidance mode selection
 * Implementation location: alt42/orchestration
 *
 * @requires guidance_modes_data.js (window.agent07GuidanceModes)
 * @requires agent_analysis.js (window.generateAnalysisReport)
 * @version 1.0
 * @date 2025-01-22
 */

// Initialize selections storage
if (typeof window.agent07Selections === 'undefined') {
  window.agent07Selections = {};
}

/**
 * Show guidance mode popup by index
 *
 * @param {number} index - Index in window.agent07GuidanceModes array (0-8)
 * @returns {void}
 */
window.showAgent07ModePopup = function(index) {
  // Validate data availability
  if (typeof window.agent07GuidanceModes === 'undefined') {
    console.error('[modal_popup.js] window.agent07GuidanceModes not loaded. Please include guidance_modes_data.js first.');
    alert('에이전트 데이터 로딩 실패. 페이지를 새로고침해주세요.');
    return;
  }

  // Validate index
  const mode = window.agent07GuidanceModes[index];
  if (!mode) {
    console.error('[modal_popup.js] Invalid mode index:', index);
    alert('잘못된 모드 인덱스입니다. (Index: ' + index + ')');
    return;
  }

  console.log('[modal_popup.js] Showing popup for mode:', mode.name, '(Index:', index, ')');

  // Call full popup function
  showAgent07ModePopupFull(mode.id, mode.name, mode.icon, mode.tooltip, mode.issues);
};

/**
 * Show full guidance mode popup with mode details and problem list
 *
 * @param {string} modeId - Mode identifier (e.g., 'curriculum')
 * @param {string} modeName - Mode display name (e.g., '커리큘럼')
 * @param {string} modeIcon - Mode icon emoji
 * @param {string} modeTooltip - Mode description (multiline)
 * @param {Array<string>} issues - Array of 6 problem items
 * @returns {void}
 */
function showAgent07ModePopupFull(modeId, modeName, modeIcon, modeTooltip, issues) {
  // Remove any existing popup
  const existingPopup = document.getElementById('agent07-mode-popup');
  if (existingPopup) {
    existingPopup.remove();
  }

  // Create popup HTML
  const popupHtml = `
    <div id="agent07-mode-popup" style="
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.5);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 10000;
      animation: fadeIn 0.2s ease;
    " onclick="if(event.target === this) window.closeAgent07ModePopup();">
      <div style="
        background: white;
        border-radius: 12px;
        padding: 24px;
        max-width: 600px;
        width: 90%;
        max-height: 80vh;
        overflow-y: auto;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        animation: scaleIn 0.3s ease;
      ">
        <!-- Header -->
        <div style="
          display: flex;
          align-items: center;
          gap: 10px;
          margin-bottom: 16px;
          border-bottom: 1px solid #e2e8f0;
          padding-bottom: 16px;
        ">
          <span style="font-size: 24px;">${modeIcon}</span>
          <h3 style="
            margin: 0;
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
            flex: 1;
          ">${modeName} 모드</h3>
          <button
            onclick="window.closeAgent07ModePopup()"
            style="
              background: none;
              border: none;
              font-size: 24px;
              cursor: pointer;
              color: #64748b;
              line-height: 1;
              padding: 4px 8px;
              transition: color 0.2s;
            "
            onmouseover="this.style.color='#dc2626'"
            onmouseout="this.style.color='#64748b'"
            title="닫기"
          >✕</button>
        </div>

        <!-- Mode Description -->
        <div style="margin-bottom: 20px;">
          <div style="
            font-size: 14px;
            line-height: 1.6;
            color: #475569;
            white-space: pre-line;
            padding: 12px;
            background: #f8fafc;
            border-radius: 6px;
            border-left: 3px solid #3b82f6;
          ">
            ${modeTooltip.replace(/\\n/g, '\n')}
          </div>
        </div>

        <!-- Problem List -->
        <div style="margin-bottom: 20px;">
          <h4 style="
            font-size: 16px;
            font-weight: 600;
            color: #dc2626;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
          ">
            <span>⚠️</span>
            <span>주요 타게팅 문제점 (${issues.length}개)</span>
          </h4>
          <div style="display: flex; flex-direction: column; gap: 8px;">
            ${issues.map((issue, idx) => `
              <div
                class="agent07-problem-item"
                style="
                  padding: 12px;
                  background: #fef2f2;
                  border: 1px solid #fecaca;
                  border-radius: 6px;
                  font-size: 13px;
                  line-height: 1.5;
                  color: #7f1d1d;
                  cursor: pointer;
                  transition: all 0.15s;
                "
                onmouseover="this.style.background='#fee2e2'; this.style.borderColor='#fca5a5';"
                onmouseout="this.style.background='#fef2f2'; this.style.borderColor='#fecaca';"
                onclick="window.selectAgent07Problem('${modeId}', '${modeName}', '${modeIcon}', \`${modeTooltip.replace(/`/g, '\\`')}\`, '${issue.replace(/'/g, "\\'")}')"
              >
                <span style="font-weight: 600;">▶</span> ${issue}
              </div>
            `).join('')}
          </div>
        </div>

        <!-- Footer Info -->
        <div style="
          background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
          border-radius: 6px;
          padding: 12px 16px;
          margin-bottom: 16px;
          border-left: 3px solid #667eea;
        ">
          <p style="
            margin: 0;
            font-size: 12px;
            line-height: 1.5;
            color: #0c4a6e;
          ">
            💡 문제를 클릭하면 AI 분석 리포트가 자동으로 생성되어 우측 패널에 표시됩니다.
          </p>
        </div>

        <!-- Close Button -->
        <button
          style="
            width: 100%;
            padding: 12px;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            color: #475569;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s;
          "
          onmouseover="this.style.background='#e2e8f0'"
          onmouseout="this.style.background='#f1f5f9'"
          onclick="window.closeAgent07ModePopup()"
        >
          닫기
        </button>
      </div>
    </div>
  `;

  // Add popup to body
  document.body.insertAdjacentHTML('beforeend', popupHtml);

  // Add escape key listener
  const escapeHandler = function(e) {
    if (e.key === 'Escape' || e.keyCode === 27) {
      window.closeAgent07ModePopup();
      document.removeEventListener('keydown', escapeHandler);
    }
  };
  document.addEventListener('keydown', escapeHandler);
}

/**
 * Close guidance mode popup
 *
 * @returns {void}
 */
window.closeAgent07ModePopup = function() {
  const popup = document.getElementById('agent07-mode-popup');
  if (popup) {
    popup.style.animation = 'fadeOut 0.2s ease';
    setTimeout(function() {
      popup.remove();
    }, 200);
    console.log('[modal_popup.js] Popup closed');
  }
};

/**
 * Handle problem selection and trigger analysis generation
 *
 * @param {string} modeId - Mode identifier
 * @param {string} modeName - Mode display name
 * @param {string} modeIcon - Mode icon emoji
 * @param {string} modeTooltip - Mode description
 * @param {string} issue - Selected problem text
 * @returns {void}
 */
window.selectAgent07Problem = function(modeId, modeName, modeIcon, modeTooltip, issue) {
  console.log('[modal_popup.js] Problem selected:', {
    mode: modeName,
    modeId: modeId,
    issue: issue
  });

  // Store selection
  window.agent07Selections[modeId] = {
    name: modeName,
    icon: modeIcon,
    tooltip: modeTooltip,
    issue: issue
  };

  // Close the popup
  window.closeAgent07ModePopup();

  // Check if analysis generator function exists
  if (typeof window.generateAnalysisReport !== 'function') {
    console.error('[modal_popup.js] window.generateAnalysisReport function not found. Please include agent_analysis.js');
    alert('분석 생성 기능이 로드되지 않았습니다. 페이지를 새로고침해주세요.');
    return;
  }

  // Construct agent object for Agent 07
  const agent07 = window.agentProblems ? window.agentProblems[6] : null; // Index 6 = Agent 07
  const agent = {
    agentNumber: 7,
    name: agent07 ? agent07.name : '상호작용 타게팅',
    icon: agent07 ? agent07.icon : '🎯',
    description: agent07 ? agent07.description : '학습 상호작용 최적화 및 타게팅 전문가'
  };

  // Construct enriched problem text with mode context
  const enrichedProblemText = `【${modeName} 모드】\n\n${issue}\n\n◆ 모드 설명:\n${modeTooltip.replace(/\\n/g, '\n')}`;

  // Generate analysis report (function defined in agent_analysis.js)
  // This will display the report in the right panel
  window.generateAnalysisReport(agent, enrichedProblemText, 0);
};

// CSS Animations
if (!document.getElementById('agent07-popup-animations')) {
  const style = document.createElement('style');
  style.id = 'agent07-popup-animations';
  style.textContent = `
    @keyframes fadeIn {
      from {
        opacity: 0;
      }
      to {
        opacity: 1;
      }
    }

    @keyframes fadeOut {
      from {
        opacity: 1;
      }
      to {
        opacity: 0;
      }
    }

    @keyframes scaleIn {
      from {
        transform: scale(0.9);
        opacity: 0;
      }
      to {
        transform: scale(1);
        opacity: 1;
      }
    }

    /* Scrollbar styling for popup */
    #agent07-mode-popup > div {
      scrollbar-width: thin;
      scrollbar-color: #667eea #f1f3f5;
    }

    #agent07-mode-popup > div::-webkit-scrollbar {
      width: 8px;
    }

    #agent07-mode-popup > div::-webkit-scrollbar-track {
      background: #f1f3f5;
      border-radius: 4px;
    }

    #agent07-mode-popup > div::-webkit-scrollbar-thumb {
      background: #667eea;
      border-radius: 4px;
    }

    #agent07-mode-popup > div::-webkit-scrollbar-thumb:hover {
      background: #5568d3;
    }
  `;
  document.head.appendChild(style);
}

console.log('[modal_popup.js] ✅ Loaded successfully. Functions available:',
  'showAgent07ModePopup()', 'closeAgent07ModePopup()', 'selectAgent07Problem()');
