/**
 * Agent Problem Popup System
 *
 * Displays popup with agent-specific problems for targeted intervention
 * Pattern reference: orchestration_hs2 guidance mode selection (for reference only)
 * Implementation location: alt42/orchestration
 *
 * @requires agent_problems.js (window.agentProblems)
 * @version 1.0
 * @date 2025-01-21
 */

/**
 * Show agent problem selection popup
 *
 * @param {number} agentIndex - Index of agent in window.agentProblems array (0-20)
 * @returns {void}
 */
window.showAgentProblemPopup = function(agentIndex) {
  // Validate agent data availability
  if (typeof window.agentProblems === 'undefined') {
    console.error('[agent_popup.js] window.agentProblems not loaded. Please include agent_problems.js first.');
    alert('에이전트 데이터 로딩 실패. 페이지를 새로고침해주세요.');
    return;
  }

  // Validate agent index
  const agent = window.agentProblems[agentIndex];
  if (!agent) {
    console.error('[agent_popup.js] Invalid agent index:', agentIndex);
    alert('잘못된 에이전트 인덱스입니다. (Index: ' + agentIndex + ')');
    return;
  }

  // Validate problems array
  if (!agent.problems || !Array.isArray(agent.problems) || agent.problems.length === 0) {
    console.warn('[agent_popup.js] Agent has no problems defined:', agent.name);
    alert(agent.name + ' 에이전트의 문제 목록이 비어있습니다.');
    return;
  }

  console.log('[agent_popup.js] Showing popup for agent:', agent.name, '(Index:', agentIndex, ')');

  // Remove existing popup if any
  const existingPopup = document.getElementById('agent-problem-popup');
  if (existingPopup) {
    existingPopup.remove();
  }

  // Generate problem list HTML with accessibility attributes
  const problemsHtml = agent.problems.map((problem, idx) => `
    <div
      class="agent-problem-item"
      role="button"
      tabindex="0"
      aria-label="문제 ${idx + 1}: ${problem}"
      onclick="window.selectAgentProblem(${agentIndex}, ${idx}, '${problem.replace(/'/g, "\\'")}')"
      onkeydown="if(event.key === 'Enter' || event.key === ' ') { event.preventDefault(); window.selectAgentProblem(${agentIndex}, ${idx}, '${problem.replace(/'/g, "\\'")}'); }"
      style="
        padding: 14px 18px;
        margin: 10px 0;
        background: #f8f9fa;
        border-left: 4px solid #667eea;
        cursor: pointer;
        transition: all 0.2s ease;
        border-radius: 6px;
        font-size: 15px;
        line-height: 1.6;
      "
      onmouseover="this.style.background='#e9ecef'; this.style.borderLeftColor='#5568d3';"
      onmouseout="this.style.background='#f8f9fa'; this.style.borderLeftColor='#667eea';"
    >
      <span style="color: #495057; font-weight: 500;">${problem}</span>
    </div>
  `).join('');

  // Create full popup HTML with ARIA attributes
  const popupHtml = `
    <div id="agent-problem-popup"
      role="dialog"
      aria-modal="true"
      aria-labelledby="agent-popup-title"
      aria-describedby="agent-popup-description"
      style="
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
      ">
      <div style="
        background: white;
        border-radius: 16px;
        padding: 36px;
        max-width: 650px;
        max-height: 85vh;
        overflow-y: auto;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        animation: scaleIn 0.3s ease;
      ">
        <!-- Header Section -->
        <div style="display: flex; align-items: center; margin-bottom: 28px; border-bottom: 2px solid #e9ecef; padding-bottom: 20px;">
          <div style="font-size: 72px; margin-right: 20px;">${agent.icon}</div>
          <div style="flex: 1;">
            <h3 id="agent-popup-title" style="margin: 0 0 8px 0; font-size: 28px; color: #212529; font-weight: 700;">
              Agent ${agent.agentNumber.toString().padStart(2, '0')} - ${agent.name}
            </h3>
            <p id="agent-popup-description" style="margin: 0; font-size: 14px; color: #6c757d; line-height: 1.5;">
              ${agent.description}
            </p>
          </div>
          <button
            onclick="window.closeAgentProblemPopup()"
            aria-label="팝업 닫기"
            style="
              margin-left: 16px;
              background: none;
              border: none;
              font-size: 28px;
              cursor: pointer;
              color: #6c757d;
              line-height: 1;
              padding: 4px 8px;
              transition: color 0.2s ease;
            "
            onmouseover="this.style.color='#dc3545'"
            onmouseout="this.style.color='#6c757d'"
            title="닫기"
          >✕</button>
        </div>

        <!-- Description Info Box -->
        <div style="
          background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
          border-radius: 8px;
          padding: 16px 20px;
          margin-bottom: 28px;
          color: white;
          box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        ">
          <div style="display: flex; align-items: center;">
            <span style="font-size: 20px; margin-right: 10px;">ℹ️</span>
            <p style="margin: 0; font-size: 14px; line-height: 1.6; font-weight: 500;">
              이 에이전트가 개선할 수 있는 주요 문제 영역입니다. 해당하는 문제를 선택하면 맞춤형 분석 리포트가 생성됩니다.
            </p>
          </div>
        </div>

        <!-- Problems Section -->
        <div style="margin-bottom: 20px;">
          <h4 style="
            color: #dc3545;
            font-size: 20px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            font-weight: 700;
          ">
            <span style="margin-right: 10px;">⚠️</span>
            주요 타게팅 문제점 (${agent.problems.length}개)
          </h4>
          <div style="margin-top: 12px;">
            ${problemsHtml}
          </div>
        </div>

        <!-- Footer Hint -->
        <div style="
          margin-top: 24px;
          padding-top: 20px;
          border-top: 1px solid #e9ecef;
          text-align: center;
          color: #6c757d;
          font-size: 13px;
        ">
          💡 문제를 클릭하면 AI 분석 리포트가 우측 패널에 표시됩니다
        </div>
      </div>
    </div>
  `;

  // Inject popup into DOM
  document.body.insertAdjacentHTML('beforeend', popupHtml);

  // Add backdrop click handler to close
  document.getElementById('agent-problem-popup').addEventListener('click', function(e) {
    if (e.target.id === 'agent-problem-popup') {
      window.closeAgentProblemPopup();
    }
  });

  // Add escape key listener
  const escapeHandler = function(e) {
    if (e.key === 'Escape' || e.keyCode === 27) {
      window.closeAgentProblemPopup();
      document.removeEventListener('keydown', escapeHandler);
    }
  };
  document.addEventListener('keydown', escapeHandler);
};

/**
 * Close agent problem popup with animation
 *
 * @returns {void}
 */
window.closeAgentProblemPopup = function() {
  const popup = document.getElementById('agent-problem-popup');
  if (popup) {
    popup.style.animation = 'fadeOut 0.2s ease';
    setTimeout(function() {
      popup.remove();
    }, 200);
    console.log('[agent_popup.js] Popup closed');
  }
};

/**
 * Handle problem selection and trigger analysis generation
 *
 * @param {number} agentIndex - Index of selected agent (0-20)
 * @param {number} problemIndex - Index of selected problem within agent.problems
 * @param {string} problemText - Full text of the selected problem
 * @returns {void}
 */
window.selectAgentProblem = function(agentIndex, problemIndex, problemText) {
  const agent = window.agentProblems[agentIndex];

  if (!agent) {
    console.error('[agent_popup.js] Invalid agent index in selectAgentProblem:', agentIndex);
    alert('에이전트 정보를 찾을 수 없습니다.');
    return;
  }

  console.log('[agent_popup.js] Problem selected:', {
    agent: agent.name,
    agentNumber: agent.agentNumber,
    problemIndex: problemIndex,
    problemText: problemText
  });

  // Close the popup
  window.closeAgentProblemPopup();

  // Check if analysis generator function exists
  if (typeof window.generateAnalysisReport !== 'function') {
    console.error('[agent_popup.js] window.generateAnalysisReport function not found. Please include agent_analysis.js');
    alert('분석 생성 기능이 로드되지 않았습니다. 페이지를 새로고침해주세요.');
    return;
  }

  // Generate analysis report (function defined in agent_analysis.js)
  window.generateAnalysisReport(agent, problemText, problemIndex);
};

// CSS Animations
if (!document.getElementById('agent-popup-animations')) {
  const style = document.createElement('style');
  style.id = 'agent-popup-animations';
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
    #agent-problem-popup > div {
      scrollbar-width: thin;
      scrollbar-color: #667eea #f1f3f5;
    }

    #agent-problem-popup > div::-webkit-scrollbar {
      width: 8px;
    }

    #agent-problem-popup > div::-webkit-scrollbar-track {
      background: #f1f3f5;
      border-radius: 4px;
    }

    #agent-problem-popup > div::-webkit-scrollbar-thumb {
      background: #667eea;
      border-radius: 4px;
    }

    #agent-problem-popup > div::-webkit-scrollbar-thumb:hover {
      background: #5568d3;
    }
  `;
  document.head.appendChild(style);
}

console.log('[agent_popup.js] Loaded successfully. Functions available:',
  'showAgentProblemPopup()', 'closeAgentProblemPopup()', 'selectAgentProblem()');
