/**
 * Agent Analysis Report System
 *
 * Generates and displays AI-powered analysis reports for agent problems
 * Integrates with GPT API for intelligent problem analysis
 *
 * @requires agent_problems.js
 * @version 1.0
 * @date 2025-01-21
 */

/**
 * Generate analysis report for selected agent problem
 *
 * @param {Object} agent - Agent object from window.agentProblems
 * @param {string} problemText - Full text of selected problem
 * @param {number} problemIndex - Index of problem in agent.problems array
 * @returns {void}
 */
window.generateAnalysisReport = async function(agent, problemText, problemIndex, retryCount = 0) {
  console.log('[agent_analysis.js] Generating analysis for:', {
    agent: agent.name,
    agentNumber: agent.agentNumber,
    problemText: problemText,
    problemIndex: problemIndex,
    retryCount: retryCount
  });

  // Show loading state with timeout indicator
  showAnalysisLoading(agent, problemText, retryCount);

  try {
    // Get student ID from phpData
    const studentId = (typeof window.phpData !== 'undefined' && window.phpData.studentId)
      ? window.phpData.studentId
      : null;

    // Prepare request payload
    const requestData = {
      agent_id: agent.id,
      agent_number: agent.agentNumber,
      agent_name: agent.name,
      agent_description: agent.description,
      problem_text: problemText,
      problem_index: problemIndex,
      student_id: studentId,
      timestamp: Math.floor(Date.now() / 1000)
    };

    console.log('[agent_analysis.js] API request:', requestData);

    // Call analysis API with timeout
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 60000); // 60 second timeout

    const response = await fetch('agents/common/api/generate_agent_analysis.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(requestData),
      signal: controller.signal
    });

    clearTimeout(timeoutId);

    if (!response.ok) {
      throw new Error('API responded with status ' + response.status + ': ' + response.statusText);
    }

    const result = await response.json();
    console.log('[agent_analysis.js] API response:', result);

    if (result.success) {
      displayAnalysisReport(agent, problemText, result.analysis);
    } else {
      throw new Error(result.error || '알 수 없는 오류가 발생했습니다.');
    }

  } catch (error) {
    console.error('[agent_analysis.js] Analysis generation error:', error);

    // Handle timeout with retry option
    if (error.name === 'AbortError') {
      if (retryCount < 2) {
        showAnalysisTimeout(agent, problemText, problemIndex, retryCount);
      } else {
        showAnalysisError(agent, problemText, '분석 요청 시간이 초과되었습니다. (3회 시도 실패) - File: agent_analysis.js, Line: 20');
      }
    } else {
      showAnalysisError(agent, problemText, error.message);
    }
  }
};

/**
 * Show loading state in right panel
 *
 * @param {Object} agent - Agent object
 * @param {string} problemText - Problem being analyzed
 * @param {number} retryCount - Current retry attempt number
 * @returns {void}
 */
function showAnalysisLoading(agent, problemText, retryCount = 0) {
  const retryText = retryCount > 0 ? ` (재시도 ${retryCount}/2)` : '';
  const panelHtml = `
    <div id="analysis-panel" style="
      position: fixed;
      right: 0;
      top: 0;
      bottom: 0;
      width: 450px;
      background: white;
      box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
      z-index: 1000;
      overflow-y: auto;
      padding: 32px 28px;
      animation: slideInRight 0.3s ease;
    ">
      <button
        onclick="window.closeAnalysisPanel()"
        style="
          position: absolute;
          top: 20px;
          right: 20px;
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

      <div style="text-align: center; padding: 60px 20px;">
        <div style="font-size: 64px; margin-bottom: 20px; animation: spin 2s linear infinite;">
          ${agent.icon}
        </div>
        <h3 style="color: #212529; margin-bottom: 12px; font-size: 20px;">
          분석 중입니다...${retryText}
        </h3>
        <p style="color: #6c757d; font-size: 14px; line-height: 1.6; margin-bottom: 24px;">
          ${agent.name} 에이전트가 문제를 분석하고 있습니다.
        </p>
        <div style="
          background: #f8f9fa;
          border-radius: 8px;
          padding: 16px;
          text-align: left;
          margin-bottom: 28px;
        ">
          <p style="
            color: #495057;
            font-size: 13px;
            line-height: 1.6;
            margin: 0;
            font-weight: 500;
          ">
            📌 ${problemText}
          </p>
        </div>
        <div style="
          height: 4px;
          background: #e9ecef;
          border-radius: 2px;
          overflow: hidden;
          margin: 0 auto;
          max-width: 280px;
        ">
          <div style="
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            animation: progressBar 2s ease-in-out infinite;
          "></div>
        </div>
      </div>
    </div>
  `;

  // Remove existing panel
  const existingPanel = document.getElementById('analysis-panel');
  if (existingPanel) existingPanel.remove();

  // Inject new panel
  document.body.insertAdjacentHTML('beforeend', panelHtml);
}

/**
 * Display analysis report in right panel
 *
 * @param {Object} agent - Agent object
 * @param {string} problemText - Problem that was analyzed
 * @param {Object} analysis - Analysis result from API
 * @returns {void}
 */
function displayAnalysisReport(agent, problemText, analysis) {
  const panelHtml = `
    <div id="analysis-panel" style="
      position: fixed;
      right: 0;
      top: 0;
      bottom: 0;
      width: 450px;
      background: white;
      box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
      z-index: 1000;
      overflow-y: auto;
      padding: 32px 28px;
      animation: slideInRight 0.3s ease;
    ">
      <button
        onclick="window.closeAnalysisPanel()"
        style="
          position: absolute;
          top: 20px;
          right: 20px;
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

      <!-- Header -->
      <div style="margin-bottom: 28px; padding-right: 40px;">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
          <div style="font-size: 48px;">${agent.icon}</div>
          <div>
            <h3 style="margin: 0; font-size: 22px; color: #212529; font-weight: 700;">
              분석 리포트
            </h3>
            <p style="margin: 4px 0 0 0; font-size: 13px; color: #6c757d;">
              Agent ${agent.agentNumber.toString().padStart(2, '0')} - ${agent.name}
            </p>
          </div>
        </div>
      </div>

      <!-- Problem Context -->
      <div style="
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 10px;
        padding: 18px 20px;
        margin-bottom: 24px;
        color: white;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
      ">
        <div style="display: flex; align-items: center; margin-bottom: 8px;">
          <span style="font-size: 18px; margin-right: 8px;">⚠️</span>
          <h4 style="margin: 0; font-size: 15px; font-weight: 700;">선택된 문제</h4>
        </div>
        <p style="margin: 0; font-size: 14px; line-height: 1.6; font-weight: 500;">
          ${problemText}
        </p>
      </div>

      <!-- Analysis Sections -->
      <div style="display: flex; flex-direction: column; gap: 24px;">

        <!-- Problem Situation -->
        <div>
          <div style="
            display: flex;
            align-items: center;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
            margin-bottom: 12px;
          ">
            <span style="font-size: 20px; margin-right: 8px;">📋</span>
            <h4 style="
              margin: 0;
              font-size: 16px;
              color: #212529;
              font-weight: 700;
            ">문제 상황</h4>
          </div>
          <p style="
            color: #495057;
            font-size: 14px;
            line-height: 1.8;
            margin: 0;
          ">${analysis.problem_situation || '분석 중...'}</p>
        </div>

        <!-- Root Cause Analysis -->
        <div>
          <div style="
            display: flex;
            align-items: center;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
            margin-bottom: 12px;
          ">
            <span style="font-size: 20px; margin-right: 8px;">🔍</span>
            <h4 style="
              margin: 0;
              font-size: 16px;
              color: #212529;
              font-weight: 700;
            ">원인 분석</h4>
          </div>
          <p style="
            color: #495057;
            font-size: 14px;
            line-height: 1.8;
            margin: 0;
          ">${analysis.cause_analysis || '분석 중...'}</p>
        </div>

        <!-- Improvement Plan -->
        <div>
          <div style="
            display: flex;
            align-items: center;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
            margin-bottom: 12px;
          ">
            <span style="font-size: 20px; margin-right: 8px;">💡</span>
            <h4 style="
              margin: 0;
              font-size: 16px;
              color: #212529;
              font-weight: 700;
            ">개선 방안</h4>
          </div>
          <p style="
            color: #495057;
            font-size: 14px;
            line-height: 1.8;
            margin: 0;
          ">${analysis.improvement_plan || '분석 중...'}</p>
        </div>

        <!-- Expected Outcome -->
        <div>
          <div style="
            display: flex;
            align-items: center;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
            margin-bottom: 12px;
          ">
            <span style="font-size: 20px; margin-right: 8px;">🎯</span>
            <h4 style="
              margin: 0;
              font-size: 16px;
              color: #212529;
              font-weight: 700;
            ">예상 효과</h4>
          </div>
          <p style="
            color: #495057;
            font-size: 14px;
            line-height: 1.8;
            margin: 0;
          ">${analysis.expected_outcome || '분석 중...'}</p>
        </div>

      </div>

      <!-- Footer -->
      <div style="
        margin-top: 32px;
        padding-top: 20px;
        border-top: 1px solid #e9ecef;
        text-align: center;
        color: #6c757d;
        font-size: 12px;
      ">
        <p style="margin: 0;">
          🤖 AI 기반 분석 리포트 | 생성 시각: ${new Date().toLocaleString('ko-KR')}
        </p>
      </div>
    </div>
  `;

  // Remove existing panel
  const existingPanel = document.getElementById('analysis-panel');
  if (existingPanel) existingPanel.remove();

  // Inject new panel with fade effect
  document.body.insertAdjacentHTML('beforeend', panelHtml);

  console.log('[agent_analysis.js] Analysis report displayed');
}

/**
 * Show error state in right panel
 *
 * @param {Object} agent - Agent object
 * @param {string} problemText - Problem that failed to analyze
 * @param {string} errorMessage - Error message
 * @returns {void}
 */
function showAnalysisError(agent, problemText, errorMessage) {
  const panelHtml = `
    <div id="analysis-panel" style="
      position: fixed;
      right: 0;
      top: 0;
      bottom: 0;
      width: 450px;
      background: white;
      box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
      z-index: 1000;
      overflow-y: auto;
      padding: 32px 28px;
      animation: slideInRight 0.3s ease;
    ">
      <button
        onclick="window.closeAnalysisPanel()"
        style="
          position: absolute;
          top: 20px;
          right: 20px;
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

      <div style="text-align: center; padding: 60px 20px;">
        <div style="font-size: 64px; margin-bottom: 20px;">❌</div>
        <h3 style="color: #dc3545; margin-bottom: 12px; font-size: 20px;">
          분석 생성 실패
        </h3>
        <p style="color: #6c757d; font-size: 14px; line-height: 1.6; margin-bottom: 24px;">
          ${agent.name} 에이전트 분석 중 오류가 발생했습니다.
        </p>
        <div style="
          background: #fff5f5;
          border: 1px solid #feb2b2;
          border-radius: 8px;
          padding: 16px;
          text-align: left;
          margin-bottom: 20px;
        ">
          <p style="color: #c53030; font-size: 13px; margin: 0; font-weight: 500;">
            ⚠️ ${errorMessage}
          </p>
        </div>
        <div style="
          background: #f8f9fa;
          border-radius: 8px;
          padding: 16px;
          text-align: left;
        ">
          <p style="color: #495057; font-size: 13px; line-height: 1.6; margin: 0;">
            <strong>선택된 문제:</strong><br>
            ${problemText}
          </p>
        </div>
        <button
          onclick="window.generateAnalysisReport(window.agentProblems[${agent.agentNumber - 1}], '${problemText.replace(/'/g, "\\'")}', 0)"
          style="
            margin-top: 24px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 24px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
          "
          onmouseover="this.style.background='#2563eb'"
          onmouseout="this.style.background='#3b82f6'"
        >
          🔄 다시 시도
        </button>
      </div>
    </div>
  `;

  // Remove existing panel
  const existingPanel = document.getElementById('analysis-panel');
  if (existingPanel) existingPanel.remove();

  // Inject error panel
  document.body.insertAdjacentHTML('beforeend', panelHtml);

  console.error('[agent_analysis.js] Error panel displayed:', errorMessage);
}

/**
 * Close analysis panel with animation
 *
 * @returns {void}
 */
window.closeAnalysisPanel = function() {
  const panel = document.getElementById('analysis-panel');
  if (panel) {
    panel.style.animation = 'slideOutRight 0.3s ease';
    setTimeout(function() {
      panel.remove();
    }, 300);
    console.log('[agent_analysis.js] Analysis panel closed');
  }
};

// CSS Animations for panel
if (!document.getElementById('agent-analysis-animations')) {
  const style = document.createElement('style');
  style.id = 'agent-analysis-animations';
  style.textContent = `
    @keyframes slideInRight {
      from {
        transform: translateX(100%);
        opacity: 0;
      }
      to {
        transform: translateX(0);
        opacity: 1;
      }
    }

    @keyframes slideOutRight {
      from {
        transform: translateX(0);
        opacity: 1;
      }
      to {
        transform: translateX(100%);
        opacity: 0;
      }
    }

    @keyframes spin {
      from {
        transform: rotate(0deg);
      }
      to {
        transform: rotate(360deg);
      }
    }

    @keyframes progressBar {
      0% {
        transform: translateX(-100%);
      }
      50% {
        transform: translateX(0%);
      }
      100% {
        transform: translateX(100%);
      }
    }

    /* Scrollbar styling for analysis panel */
    #analysis-panel {
      scrollbar-width: thin;
      scrollbar-color: #667eea #f1f3f5;
    }

    #analysis-panel::-webkit-scrollbar {
      width: 8px;
    }

    #analysis-panel::-webkit-scrollbar-track {
      background: #f1f3f5;
      border-radius: 4px;
    }

    #analysis-panel::-webkit-scrollbar-thumb {
      background: #667eea;
      border-radius: 4px;
    }

    #analysis-panel::-webkit-scrollbar-thumb:hover {
      background: #5568d3;
    }
  `;
  document.head.appendChild(style);
}

/**
 * Show timeout warning with retry button
 *
 * @param {Object} agent - Agent object
 * @param {string} problemText - Problem being analyzed
 * @param {number} problemIndex - Problem index
 * @param {number} retryCount - Current retry count
 * @returns {void}
 */
function showAnalysisTimeout(agent, problemText, problemIndex, retryCount) {
  const panelHtml = `
    <div id="analysis-panel" class="warning-message" style="
      position: fixed;
      right: 0;
      top: 0;
      bottom: 0;
      width: 450px;
      background: white;
      box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
      z-index: 1000;
      overflow-y: auto;
      padding: 32px 28px;
      animation: slideInRight 0.3s ease;
    ">
      <button
        onclick="window.closeAnalysisPanel()"
        aria-label="패널 닫기"
        style="
          position: absolute;
          top: 20px;
          right: 20px;
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

      <div style="text-align: center; padding: 40px 20px;">
        <div style="font-size: 64px; margin-bottom: 20px;">⏱️</div>
        <h3 style="color: #f59e0b; margin-bottom: 16px; font-size: 22px; font-weight: 700;">
          분석 요청 시간 초과
        </h3>
        <p style="color: #6c757d; font-size: 14px; line-height: 1.6; margin-bottom: 24px;">
          서버 응답 시간이 초과되었습니다.<br>
          GPT API가 응답하는데 시간이 오래 걸리고 있습니다.
        </p>

        <div style="background: #fffbeb; border: 1px solid #fcd34d; border-radius: 8px; padding: 16px; margin-bottom: 28px; text-align: left;">
          <p style="margin: 0 0 8px 0; color: #92400e; font-size: 13px; font-weight: 600;">
            📌 분석 중인 문제:
          </p>
          <p style="margin: 0; color: #78350f; font-size: 13px; line-height: 1.6;">
            ${problemText}
          </p>
        </div>

        <div style="background: #f0fdfa; border: 1px solid #5eead4; border-radius: 8px; padding: 16px; margin-bottom: 28px; text-align: left;">
          <p style="margin: 0 0 8px 0; color: #115e59; font-size: 13px; font-weight: 600;">
            💡 재시도 정보:
          </p>
          <p style="margin: 0; color: #134e4a; font-size: 13px; line-height: 1.6;">
            현재 시도: ${retryCount + 1} / 3회<br>
            재시도하면 다시 분석을 요청합니다.
          </p>
        </div>

        <button
          onclick="window.generateAnalysisReport(
            window.agentProblems[${agent.agentNumber - 1}],
            '${problemText.replace(/'/g, "\\'")}',
            ${problemIndex},
            ${retryCount + 1}
          )"
          style="
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 14px 32px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            transition: all 0.2s ease;
            width: 100%;
          "
          onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(102, 126, 234, 0.4)';"
          onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(102, 126, 234, 0.3)';"
        >
          🔄 재시도 (${retryCount + 1}/2)
        </button>

        <button
          onclick="window.closeAnalysisPanel()"
          style="
            background: transparent;
            color: #6c757d;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 12px 24px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            margin-top: 12px;
            transition: all 0.2s ease;
            width: 100%;
          "
          onmouseover="this.style.borderColor='#adb5bd'; this.style.color='#495057';"
          onmouseout="this.style.borderColor='#dee2e6'; this.style.color='#6c757d';"
        >
          취소
        </button>
      </div>
    </div>
  `;

  const existingPanel = document.getElementById('analysis-panel');
  if (existingPanel) {
    existingPanel.remove();
  }

  document.body.insertAdjacentHTML('beforeend', panelHtml);
  console.log('[agent_analysis.js] Showing timeout warning for retry', retryCount + 1);
}

console.log('[agent_analysis.js] Loaded successfully. Functions available:',
  'generateAnalysisReport()', 'closeAnalysisPanel()');
