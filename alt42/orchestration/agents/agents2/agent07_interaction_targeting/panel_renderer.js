/**
 * Agent 07 (상호작용 타게팅) - Panel Renderer
 *
 * Renders right panel with 9 guidance mode cards in 3×3 grid
 * Pattern reference: orchestration_hs2 guidance mode selection
 * Implementation location: alt42/orchestration
 *
 * @requires guidance_modes_data.js (window.agent07GuidanceModes)
 * @version 1.0
 * @date 2025-01-22
 */

/**
 * Render Agent 07 right panel with guidance mode selection
 *
 * @returns {string} HTML string for right panel content
 */
window.renderAgent07Panel = function() {
  // Validate data availability
  if (typeof window.agent07GuidanceModes === 'undefined') {
    console.error('[panel_renderer.js] window.agent07GuidanceModes not loaded. Please include guidance_modes_data.js first.');
    return '<div style="padding: 20px; color: #dc2626;">에이전트 데이터 로딩 실패. 페이지를 새로고침해주세요.</div>';
  }

  const modes = window.agent07GuidanceModes;

  // Get Agent 07 info from main agent data (if available)
  const agent07 = window.agentProblems ? window.agentProblems[6] : null; // Index 6 = Agent 07
  const agentIcon = agent07 ? agent07.icon : '🎯';
  const agentName = agent07 ? agent07.name : '상호작용 타게팅';
  const agentDesc = agent07 ? agent07.description : '학습 상호작용 최적화 및 타게팅 전문가';

  // Check if any selections exist
  const selections = window.agent07Selections || {};

  return `
    <!-- Agent 07 Header -->
    <div style="
      padding: 24px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-radius: 16px 16px 0 0;
      color: white;
      box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    ">
      <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 12px;">
        <div style="font-size: 56px; line-height: 1;">${agentIcon}</div>
        <div>
          <h3 style="margin: 0 0 8px 0; font-size: 24px; font-weight: 700;">
            Agent 07 - ${agentName}
          </h3>
          <p style="margin: 0; font-size: 14px; opacity: 0.95; line-height: 1.5;">
            ${agentDesc}
          </p>
        </div>
      </div>
    </div>

    <!-- Guidance Mode Selection Section -->
    <div style="
      padding: 28px;
      background: white;
      border-radius: 0 0 16px 16px;
    ">
      <div style="margin-bottom: 20px;">
        <h4 style="
          margin: 0 0 8px 0;
          font-size: 18px;
          font-weight: 700;
          color: #1e293b;
          display: flex;
          align-items: center;
          gap: 8px;
        ">
          <span>📚</span>
          <span>지도 모드 선택</span>
        </h4>
        <p style="
          margin: 0;
          font-size: 13px;
          color: #64748b;
          line-height: 1.6;
        ">
          학습 상황에 맞는 지도 모드를 선택하면 해당 모드의 주요 문제점을 분석할 수 있습니다.
        </p>
      </div>

      <!-- 3×3 Grid of Guidance Mode Cards -->
      <div style="
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0;
        margin-bottom: 24px;
      ">
        ${modes.map((mode, index) => {
          const hasSelection = selections[mode.id];
          const isSelected = !!hasSelection;

          return `
            <div style="position: relative;">
              <button
                id="agent07-mode-btn-${mode.id}"
                data-mode-index="${index}"
                onclick="window.showAgent07ModePopup(${index})"
                class="agent07-mode-btn ${isSelected ? 'selected' : ''}"
                style="
                  width: 135px;
                  height: 36px;
                  padding: 0;
                  border-radius: 4px;
                  border: ${isSelected ? '3px solid #667eea' : '1px solid #e2e8f0'};
                  background: ${isSelected ? 'linear-gradient(135deg, rgba(102, 126, 234, 0.5), rgba(118, 75, 162, 0.4))' : '#fff'};
                  color: ${isSelected ? '#fff' : '#64748b'};
                  font-size: 10px;
                  font-weight: ${isSelected ? '700' : '500'};
                  cursor: pointer;
                  transition: all 0.15s;
                  display: flex;
                  align-items: center;
                  justify-content: center;
                  gap: 2px;
                  position: relative;
                  box-shadow: ${isSelected ? '0 3px 12px rgba(102, 126, 234, 0.5)' : 'none'};
                "
                onmouseover="if(!this.classList.contains('selected')){this.style.background='#f8fafc'; this.style.borderColor='#cbd5e1';}"
                onmouseout="if(!this.classList.contains('selected')){this.style.background='#fff'; this.style.borderColor='#e2e8f0';}"
                title="${mode.name} 모드"
              >
                <span style="font-size: 11px;">${mode.icon}</span>
                <span>${mode.name}</span>
                ${isSelected ? `
                  <div class="selected-badge" style="
                    position: absolute;
                    top: -6px;
                    right: -6px;
                    width: 20px;
                    height: 20px;
                    background: #10b981;
                    border: 2px solid white;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.4);
                  ">
                    <span style="color: white; font-size: 12px; font-weight: 700;">✓</span>
                  </div>
                ` : ''}
              </button>
            </div>
          `;
        }).join('')}
      </div>

      <!-- Info Box -->
      <div style="
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        border-radius: 8px;
        padding: 16px 20px;
        border-left: 4px solid #3b82f6;
        margin-bottom: 20px;
      ">
        <div style="display: flex; align-items: flex-start; gap: 12px;">
          <span style="font-size: 20px; line-height: 1;">💡</span>
          <div style="flex: 1;">
            <p style="margin: 0; font-size: 13px; line-height: 1.6; color: #0c4a6e;">
              <strong>지도 모드 선택 방법:</strong><br>
              각 모드 버튼을 클릭하면 해당 모드의 상세 설명과 타게팅 가능한 주요 문제점 목록이 나타납니다.
              문제점을 선택하면 AI 분석 리포트가 자동 생성됩니다.
            </p>
          </div>
        </div>
      </div>

      <!-- Selection Summary (if any selections exist) -->
      ${Object.keys(selections).length > 0 ? `
        <div style="
          background: #f8fafc;
          border: 1px solid #e2e8f0;
          border-radius: 8px;
          padding: 16px 20px;
        ">
          <h5 style="
            margin: 0 0 12px 0;
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 6px;
          ">
            <span>✓</span>
            <span>선택된 문제점 (${Object.keys(selections).length}개 모드)</span>
          </h5>
          <div style="display: flex; flex-direction: column; gap: 8px;">
            ${Object.entries(selections).map(([modeId, selection]) => {
              const mode = modes.find(m => m.id === modeId);
              return mode ? `
                <div style="
                  padding: 10px 12px;
                  background: white;
                  border: 1px solid #e2e8f0;
                  border-radius: 6px;
                  font-size: 12px;
                  line-height: 1.5;
                  color: #475569;
                ">
                  <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                    <span style="font-size: 14px;">${mode.icon}</span>
                    <strong style="color: #1e293b;">${mode.name}</strong>
                  </div>
                  <div style="color: #64748b; padding-left: 20px;">
                    ▶ ${selection.issue}
                  </div>
                </div>
              ` : '';
            }).join('')}
          </div>
        </div>
      ` : ''}
    </div>
  `;
};

console.log('[panel_renderer.js] ✅ Agent 07 panel renderer loaded successfully');
