/**
 * Step 3 Goal Analysis Handler
 * File: assets/js/step3_goal_analysis.js:1
 */

(function() {
  'use strict';

  // Goal type configurations
  const goalTypes = {
    quarter: { id: 'quarter', name: '분기목표', icon: '📊', color: '#6366f1', knowledgeFile: '분기목표 지식.md' },
    weekly: { id: 'weekly', name: '주간목표', icon: '📅', color: '#8b5cf6', knowledgeFile: '주간목표 지식.md' },
    today: { id: 'today', name: '오늘목표', icon: '📝', color: '#10b981', knowledgeFile: '오늘목표 지식.md' },
    pomodoro: { id: 'pomodoro', name: '포모도르', icon: '⏱️', color: '#f59e0b', knowledgeFile: '포모도르 지식.md' },
    curriculum: { id: 'curriculum', name: '커리큘럼', icon: '📚', color: '#ef4444', knowledgeFile: '커리큘럼 지식.md' }
  };

  /**
   * Render goal analysis UI in right panel
   * @param {HTMLElement} container - Right panel container
   */
  window.renderGoalAnalysisUI = function(container) {
    const html = `
      <div class="goal-analysis-panel">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
          <h3 style="margin:0;">목표 및 계획 분석</h3>
          <button class="knowledge-btn" onclick="openKnowledgeFile('의사결정 지식.md')" title="의사결정 지식 파일 보기">
            📚 지식파일
          </button>
        </div>
        <p class="description">분석할 목표 유형을 선택하세요</p>

        <div class="goal-type-buttons">
          ${Object.values(goalTypes).map(type => `
            <div style="display:flex;gap:8px;align-items:stretch;">
              <button
                class="goal-type-btn"
                data-type="${type.id}"
                style="border-left: 4px solid ${type.color};flex:1;">
                <span class="icon">${type.icon}</span>
                <span class="name">${type.name}</span>
              </button>
              <button
                class="knowledge-btn-small"
                onclick="openKnowledgeFile('${type.knowledgeFile}')"
                title="${type.name} 지식 파일 보기">
                📚
              </button>
            </div>
          `).join('')}
        </div>

        <div class="selected-type-info" style="display:none;">
          <div class="selected-badge">
            <span class="badge-icon"></span>
            <span class="badge-text"></span>
          </div>
        </div>

        <button class="execute-btn" disabled>
          <span>분석 실행</span>
        </button>

        <!-- 활동 조정 임베드 영역 (분석 실행 버튼 아래) -->
        <div id="activity-coordination-embed" style="margin-top:16px;"></div>

        <div class="analysis-result" style="display:none;">
          <h4>분석 결과</h4>
          <div class="result-content"></div>
          <div class="result-stats"></div>
        </div>

        <div class="loading-indicator" style="display:none;">
          <div class="spinner"></div>
          <p>분석 중...</p>
        </div>
      </div>
    `;

    container.innerHTML = html;
    // orchestration91 - 3-activity-coordination 임베드 (userid 전달)
    try {
      const userId = (window.phpData && window.phpData.studentId) ? window.phpData.studentId : (window.currentUserId || '');
      const target = container.querySelector('#activity-coordination-embed');
      if (target) {
        const src = `/moodle/local/augmented_teacher/alt42/orchestration91/3-activity-coordination/index.php?userid=${encodeURIComponent(userId)}`;
        target.innerHTML = `
          <div style="width:100%; overflow-x:auto; -webkit-overflow-scrolling: touch;">
            <iframe src="${src}"
              style="width:1200px; min-height:75vh; border:0; border-radius:12px; background:#fff; display:block;"
              allow="clipboard-write;" referrerpolicy="same-origin"></iframe>
          </div>
        `;
      }
    } catch (e) {
      console.error('[Step3] activity-coordination embed failed:', e);
    }
    attachEventListeners(container);
  };

  /**
   * Attach event listeners
   */
  function attachEventListeners(container) {
    const buttons = container.querySelectorAll('.goal-type-btn');
    const executeBtn = container.querySelector('.execute-btn');
    const selectedInfo = container.querySelector('.selected-type-info');

    let selectedType = null;

    // Type selection
    buttons.forEach(btn => {
      btn.addEventListener('click', function() {
        // Remove active class from all
        buttons.forEach(b => b.classList.remove('active'));
        this.classList.add('active');

        selectedType = this.dataset.type;
        const typeConfig = goalTypes[selectedType];

        // Update selected info
        selectedInfo.style.display = 'block';
        selectedInfo.querySelector('.badge-icon').textContent = typeConfig.icon;
        selectedInfo.querySelector('.badge-text').textContent = typeConfig.name;

        // Enable execute button
        executeBtn.disabled = false;

        // Update state
        if (typeof state !== 'undefined') {
          if (!state.selectedOptions) state.selectedOptions = {};
          state.selectedOptions.goalAnalysisType = selectedType;
        }
      });
    });

    // Execute analysis
    executeBtn.addEventListener('click', function() {
      if (!selectedType) return;
      executeGoalAnalysis(selectedType, container);
    });
  }

  /**
   * Execute goal analysis API call
   */
  async function executeGoalAnalysis(type, container) {
    const loadingEl = container.querySelector('.loading-indicator');
    const resultEl = container.querySelector('.analysis-result');
    const executeBtn = container.querySelector('.execute-btn');

    try {
      // Show loading
      loadingEl.style.display = 'block';
      resultEl.style.display = 'none';
      executeBtn.disabled = true;

      // API call
      const response = await fetch('api/goal_analysis_executor.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'execute',
          type: type,
          userid: window.phpData?.studentId || window.currentUserId || 2
        })
      });

      const data = await response.json();

      if (!data.success) {
        throw new Error(data.error || 'API call failed. File: step3_goal_analysis.js:142');
      }

      // Display results
      displayResults(data.data, container);

      // Update state
      if (typeof state !== 'undefined') {
        if (!state.selectedOptions) state.selectedOptions = {};
        state.selectedOptions.goalAnalysisResult = data.data;
      }

    } catch (error) {
      console.error('Goal analysis error:', error);
      alert('분석 중 오류가 발생했습니다: ' + error.message);
    } finally {
      loadingEl.style.display = 'none';
      executeBtn.disabled = false;
    }
  }

  /**
   * Display analysis results
   */
  function displayResults(data, container) {
    const resultEl = container.querySelector('.analysis-result');
    const contentEl = resultEl.querySelector('.result-content');
    const statsEl = resultEl.querySelector('.result-stats');

    // Format analysis text with line breaks
    const formattedAnalysis = (data.analysis || '').replace(/\n/g, '<br>');

    contentEl.innerHTML = `
      <div class="analysis-text">${formattedAnalysis}</div>
    `;

    statsEl.innerHTML = `
      <div class="stat-item">
        <span class="stat-label">분석 ID:</span>
        <span class="stat-value">${data.id}</span>
      </div>
      <div class="stat-item">
        <span class="stat-label">효과성 점수:</span>
        <span class="stat-value">${data.score}/100</span>
      </div>
      <div class="stat-item">
        <span class="stat-label">분석 시간:</span>
        <span class="stat-value">${data.statistics?.analysis_time || '-'}</span>
      </div>
    `;

    resultEl.style.display = 'block';
  }

  /**
   * Open knowledge file in new tab for editing
   * @param {string} filename - Knowledge file name
   */
  window.openKnowledgeFile = function(filename) {
    const url = `knowledge_editor.php?file=${encodeURIComponent(filename)}`;
    window.open(url, '_blank', 'width=1200,height=800');
  };

})();
