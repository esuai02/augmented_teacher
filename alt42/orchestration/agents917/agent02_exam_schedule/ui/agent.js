/**
 * Agent 02: 시험일정 식별
 * 파일 위치: agents/agent02_exam_schedule/ui/agent.js
 *
 * orchestration_hs2의 시험일정 식별 기능을 orchestration의 우측 패널로 이식
 * - 시험일정 선택
 * - AI 전략 생성 (OpenAI API)
 * - 저장된 전략 보기 (의사결정 지식.md 새 탭 열기)
 */

(function() {
  'use strict';

  // =============================================================================
  // 우측 패널 렌더링 함수
  // =============================================================================

  window.renderAgent02Panel = function(panel) {
    if (!panel) {
      console.error('❌ Agent02: panel element not found - File: agent.js, Line: 18');
      return;
    }

    console.log('📅 Agent02: Rendering panel');

    // 시험일정 옵션 정의 (orchestration_hs2와 동일)
    const contextOptions = [
      '🏖️ 방학',
      '📅 D-2개월',
      '📆 D-1개월',
      '⏰ D-2주',
      '🚨 D-1주',
      '🔥 D-3일',
      '💯 D-1일',
      '📖 시험없음'
    ];

    // 시험일정별 상세 설명
    const contextDetails = {
      '🏖️ 방학': '주별 공부시간을 확정한 후 다음 학기 시험대비 + 개념선행 + 복습 & 심화 학습의 전체 혹은 일부를 선택한 다음 요일별 시간표에 반영합니다.',
      '📅 D-2개월': '개념공부 > 유형연습 > 심화학습 > 기출문제 풀이의 순서로 시험대비를 계획하고 시작합니다.',
      '📆 D-1개월': '개념공부 > 유형연습 > 심화학습 > 기출문제 풀이 중 현재 진행상황을 진단하고 남은 시험 기간에 대한 계획을 재조정합니다. 재조정 과정에서 생기는 딜레마는 검증된 Best practice를 토대로 접근합니다.',
      '⏰ D-2주': '개념공부 > 유형연습 > 심화학습 > 기출문제 풀이 중 현재 진행상황을 진단하고 마무리 전략을 준비합니다. 단기기억 청킹과 작업기억 청킹에 대한 최적화 전략을 기준으로 하며 이러한 최적화 경험이 부족한 경우 유경험자의 가이드를 습득하거나 도움을 받아서 진행합니다.',
      '🚨 D-1주': '실전상황에 대한 적용도를 높이기 위한 맞춤전략을 선택하는 과정이 중요합니다. 이 경우 학생이 실제 실행가능하고 효과적일 것이라고 느끼는지를 참고하여야 합니다. 이때 단순히 학생의 관점을 수영하기 보다는 학생의 관점의 숨은 의미를 포착하고 실제 작용하는 과정을 예측하여 반영합니다.',
      '🔥 D-3일': '기출문제 등을 통하여 학생의 실전 준비상태와 인지상태를 진단해 봅니다. 취약지점을 식별하고 집중보충하는 활동을 배치하고 반복 실전 연습을 통하여 극복하도록 돕습니다.',
      '💯 D-1일': '작업기억 활성화를 위하여 Speed 서술평가 등을 배치하고 마지막 기출문제 풀이 등을 배치합니다. 기출문제 풀이는 워밍업과정을 통하여 진입시 어느 정도의 성공을 예측하고 최적화 합니다.',
      '📖 시험없음': '일상적인 학습 상황. 현재 진도에 맞춘 학습과 복습. 장기적 학습 목표와 실력 향상에 집중.'
    };

    // state 초기화
    if (!window.state.selectedOptions) {
      window.state.selectedOptions = {};
    }
    if (!window.state.selectedOptions.contextMode) {
      window.state.selectedOptions.contextMode = '';
    }

    const selectedContext = window.state.selectedOptions.contextMode;
    const selectedDetail = contextDetails[selectedContext];

    // HTML 렌더링
    panel.innerHTML = `
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
        <div style="font-size:28px;">📅</div>
        <div>
          <div style="font-weight:700;color:#111827;font-size:18px;">시험일정 식별</div>
          <div style="color:#6b7280;font-size:13px;">Step 2</div>
        </div>
      </div>

      <div style="color:#374151;line-height:1.6;margin-bottom:20px;">
        <p>학습 맥락 및 긴급도를 파악하여 최적의 학습 전략을 수립합니다.</p>
      </div>

      <!-- 데이터 매핑 분석 버튼 -->
      <div style="margin-bottom:16px;">
        <a 
          id="data-mapping-analysis-link"
          href="#"
          target="_blank"
          onclick="
            const studentId = (window.phpData && window.phpData.studentId) ? window.phpData.studentId : '';
            const url = '/moodle/local/augmented_teacher/alt42/orchestration/agents/agent02_exam_schedule/ui/data_mapping_analysis.php?studentid=' + encodeURIComponent(studentId);
            this.href = url;
            return true;
          "
          style="
            display:inline-flex;
            align-items:center;
            gap:6px;
            padding:8px 12px;
            background:#667eea;
            color:white;
            text-decoration:none;
            border-radius:6px;
            font-size:12px;
            font-weight:600;
            transition:all 0.2s ease;
            cursor:pointer;
          "
          onmouseover="this.style.background='#5568d3';this.style.transform='translateY(-1px)'"
          onmouseout="this.style.background='#667eea';this.style.transform='translateY(0)'"
        >
          <span>📊</span>
          데이터 매핑 분석
        </a>
      </div>

      <!-- 시험일정 선택 버튼 그룹 -->
      <div style="margin-bottom:16px;">
        <label style="display:block;font-weight:600;color:#1f2937;margin-bottom:8px;font-size:14px;">
          📋 시험일정 선택
        </label>
        <div style="display:flex;flex-wrap:wrap;gap:8px;">
          ${contextOptions.map(opt => {
            const isActive = selectedContext === opt;
            return `
              <button
                class="chip ${isActive ? 'active' : ''}"
                data-context-option="${opt}"
                onclick="handleContextModeSelect('${opt}')"
                style="
                  padding:8px 12px;
                  border-radius:6px;
                  border:1px solid ${isActive ? '#3b82f6' : '#e5e7eb'};
                  background:${isActive ? '#3b82f6' : '#ffffff'};
                  color:${isActive ? '#ffffff' : '#374151'};
                  cursor:pointer;
                  font-size:12px;
                  transition:all 0.2s ease;
                "
                onmouseover="if(!this.classList.contains('active')) this.style.borderColor='#94a3b8'"
                onmouseout="if(!this.classList.contains('active')) this.style.borderColor='#e5e7eb'"
              >
                ${opt}
              </button>
            `;
          }).join('')}
        </div>
      </div>

      <!-- 선택된 시험일정 상세 설명 -->
      ${selectedDetail ? `
        <div style="margin-top:16px;margin-bottom:16px;">
          <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:16px;">
            <div style="display:flex;align-items:flex-start;gap:8px;">
              <div style="color:#3b82f6;font-size:18px;flex-shrink:0;">💡</div>
              <div>
                <strong style="color:#3b82f6;font-size:13px;display:block;margin-bottom:4px;">학습 전략:</strong>
                <span style="font-size:12px;color:#475569;line-height:1.6;">${selectedDetail}</span>
              </div>
            </div>
          </div>
        </div>
      ` : `
        <div style="margin-top:16px;margin-bottom:16px;">
          <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:16px;text-align:center;">
            <span style="font-size:12px;color:#94a3b8;">시험일정을 선택하면 맞춤형 학습 전략 가이드가 표시됩니다.</span>
          </div>
        </div>
      `}

      <!-- AI 전략 생성 섹션 -->
      ${selectedContext ? `
        <div style="margin-top:20px;">
          <div style="background:linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);border:1px solid #cbd5e1;padding:20px;border-radius:8px;">
            <div style="margin-bottom:16px;">
              <strong style="color:#1e293b;font-size:14px;display:flex;align-items:center;">
                <span style="margin-right:8px;">🎯</span>
                개인 맞춤형 시험준비 전략 생성
              </strong>
              <p style="font-size:11px;color:#64748b;margin:8px 0 0 28px;line-height:1.4;">
                Step 3 목표분석 결과와 선택한 시험일정을 바탕으로 AI가 맞춤형 전략을 생성합니다.
              </p>
            </div>

            <div style="display:flex;gap:8px;align-items:center;">
              <button
                id="generate-exam-strategy-btn"
                onclick="generateExamStrategy('${selectedContext}')"
                style="
                  background:linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
                  color:white;
                  border:none;
                  padding:10px 16px;
                  border-radius:6px;
                  font-size:13px;
                  font-weight:600;
                  cursor:pointer;
                  display:flex;
                  align-items:center;
                  gap:6px;
                  transition:all 0.2s ease;
                  box-shadow:0 1px 3px rgba(0,0,0,0.1);
                "
                onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 4px 12px rgba(59,130,246,0.3)'"
                onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 1px 3px rgba(0,0,0,0.1)'"
              >
                <span>📋</span>
                전략 생성하기
              </button>

              <button
                id="view-exam-strategies-btn"
                onclick="viewExamStrategies()"
                style="
                  background:white;
                  color:#64748b;
                  border:1px solid #cbd5e1;
                  padding:10px 16px;
                  border-radius:6px;
                  font-size:13px;
                  cursor:pointer;
                  display:flex;
                  align-items:center;
                  gap:6px;
                  transition:all 0.2s ease;
                "
                onmouseover="this.style.borderColor='#94a3b8';this.style.color='#475569'"
                onmouseout="this.style.borderColor='#cbd5e1';this.style.color='#64748b'"
              >
                <span>📊</span>
                저장된 전략 보기
              </button>
            </div>

            <!-- 로딩 및 결과 표시 영역 -->
            <div id="exam-strategy-result" style="margin-top:16px;display:none;">
              <!-- 동적으로 내용이 추가됩니다 -->
            </div>
          </div>
        </div>
      ` : ''}
    `;

    console.log('✅ Agent02: Panel rendered successfully');
  };

  // =============================================================================
  // 시험일정 선택 핸들러
  // =============================================================================

  window.handleContextModeSelect = function(option) {
    console.log('📅 Agent02: Context mode selected:', option);

    // state 업데이트
    if (!window.state.selectedOptions) {
      window.state.selectedOptions = {};
    }
    window.state.selectedOptions.contextMode = option;

    // 패널 재렌더링
    const panel = document.getElementById('detail-panel');
    if (panel && typeof window.renderAgent02Panel === 'function') {
      window.renderAgent02Panel(panel);
    }
  };

  // =============================================================================
  // AI 전략 생성 함수 (orchestration_hs2에서 이식)
  // =============================================================================

  window.generateExamStrategy = async function(examTimeline) {
    console.log('🎯 Agent02: 시험 전략 생성 시작:', examTimeline, '- File: agent.js, Line: 229');

    if (!examTimeline) {
      alert('시험일정을 먼저 선택해주세요.');
      return;
    }

    const resultDiv = document.getElementById('exam-strategy-result');
    const generateBtn = document.getElementById('generate-exam-strategy-btn');

    if (!resultDiv) {
      console.error('❌ exam-strategy-result div not found - File: agent.js, Line: 238');
      return;
    }

    // 로딩 상태 표시
    resultDiv.style.display = 'block';
    resultDiv.innerHTML = `
      <div style="padding:16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;text-align:center;">
        <div style="display:flex;align-items:center;justify-content:center;gap:8px;margin-bottom:8px;">
          <div style="width:20px;height:20px;border:2px solid #3b82f6;border-top:2px solid transparent;border-radius:50%;animation:spin 1s linear infinite;"></div>
          <span style="color:#3b82f6;font-weight:600;font-size:13px;">AI가 맞춤형 전략을 생성중입니다...</span>
        </div>
        <p style="font-size:11px;color:#64748b;margin:0;">Step 3 목표분석 결과와 시험일정을 바탕으로 개인화된 전략을 만들고 있어요.</p>
      </div>
    `;

    if (generateBtn) {
      generateBtn.disabled = true;
      generateBtn.style.opacity = '0.6';
      generateBtn.style.cursor = 'not-allowed';
    }

    try {
      // 1) omniui 시험 대비 에이전트와 동기화 (userid 기준)
      try {
        const syncUrl = '/moodle/local/augmented_teacher/alt42/orchestration7/api/sync_exam_prep.php';
        await fetch(syncUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            userid: window.phpData?.studentId || window.currentUserId,
            exam_timeline: examTimeline
          })
        });
      } catch (syncErr) {
        console.warn('⚠️ Agent02: omniui 동기화 실패(무시 가능):', syncErr);
      }

      // API 절대 경로 사용 (orchestration_hs2와 동일한 패턴)
      const apiUrl = '/moodle/local/augmented_teacher/alt42/orchestration7/agents/agent02_exam_schedule/api/exam_strategy_api.php';

      const response = await fetch(apiUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          exam_timeline: examTimeline,
          userid: window.phpData?.studentId || window.currentUserId
        })
      });

      const raw = await response.text();
      let data;
      try {
        data = JSON.parse(raw);
      } catch (e) {
        const snippet = (raw || '').slice(0, 400);
        throw new Error(`Invalid JSON${response.status ? ` (HTTP ${response.status})` : ''}: ${snippet}`);
      }

      if (data.success) {
        // orchestration_hs2와 동일한 형식으로 결과 표시
        const strategy = data.generated_strategy || '';
        const hasGoalAnalysis = data.has_goal_analysis || false;
        const generationTime = data.generation_time_ms || 0;

        // 스타일 인젝션 (한 번만)
        if (!document.getElementById('agent02-ex-report-style')) {
          const style = document.createElement('style');
          style.id = 'agent02-ex-report-style';
          style.textContent = `
            .ex-report{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;line-height:1.75;color:#1f2937;}
            .ex-container{max-width:980px;margin:0 auto;padding:8px 4px;}
            .ex-section{background:#ffffff;border:1px solid #e5e7eb;border-radius:10px;padding:16px;margin:12px 0;box-shadow:0 1px 2px rgba(0,0,0,0.04);}
            .ex-title{font-size:16px;font-weight:800;margin:0 0 10px 0;}
            .ex-title-overview{color:#0ea5e9;}
            .ex-title-phases{color:#059669;}
            .ex-title-practice{color:#4f46e5;}
            .ex-title-checklist{color:#b45309;}
            .ex-title-practical{color:#7c3aed;}
            .ex-report p{margin:0 0 12px 0;}
            .ex-report ul{margin:0 0 12px 20px;padding:0;}
            .ex-report li{margin:6px 0;}
            .ex-report table{width:100%;border-collapse:separate;border-spacing:0;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;margin:10px 0;}
            .ex-report th,.ex-report td{border-top:1px solid #e5e7eb;padding:10px;vertical-align:top;font-size:13px;}
            .ex-report th{background:#f3f4f6;color:#0f172a;text-align:left;}
            .ex-callout{background:#f8fafc;border:1px solid #e2e8f0;border-left:4px solid #6366f1;border-radius:8px;padding:12px;margin:12px 0;}
            @media (max-width:640px){.ex-section{padding:12px}.ex-title{font-size:15px}}
          `;
          document.head.appendChild(style);
        }

        resultDiv.innerHTML = `
          <div style="background:white;border:1px solid #d1d5db;border-radius:8px;overflow:hidden;">
            <!-- 헤더 -->
            <div style="background:linear-gradient(135deg, #10b981 0%, #059669 100%);padding:12px 16px;color:white;">
              <div style="display:flex;align-items:center;justify-content:space-between;">
                <div style="display:flex;align-items:center;gap:8px;">
                  <span style="font-size:16px;">🎯</span>
                  <strong style="font-size:14px;">개인 맞춤형 시험준비 전략</strong>
                </div>
                <div style="font-size:10px;opacity:0.9;">
                  ${data.exam_timeline} • ${generationTime > 0 ? generationTime + 'ms' : 'API'}
                </div>
              </div>
              ${!hasGoalAnalysis ? `
                <div style="margin-top:8px;padding:8px;background:rgba(255,255,255,0.1);border-radius:4px;font-size:11px;">
                  💡 <strong>팁:</strong> Step 3에서 목표분석을 먼저 실행하면 더욱 정확한 맞춤형 전략을 받을 수 있습니다.
                </div>
              ` : ''}
            </div>

            <!-- 전략 내용 (HTML 그대로 렌더) -->
            <div style="padding:16px;">
              <div class="ex-report ex-container">
                ${strategy}
              </div>

              <!-- 액션 버튼 -->
              <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap;">
                <button
                  onclick="copyExamStrategy('${data.strategy_id || ''}')"
                  style="
                    background:#f3f4f6;
                    color:#374151;
                    border:1px solid #d1d5db;
                    padding:6px 12px;
                    border-radius:4px;
                    font-size:11px;
                    cursor:pointer;
                    display:flex;
                    align-items:center;
                    gap:4px;
                  "
                  onmouseover="this.style.background='#e5e7eb'"
                  onmouseout="this.style.background='#f3f4f6'"
                >
                  📋 복사하기
                </button>

                <button
                  onclick="shareExamStrategy('${data.strategy_id || ''}')"
                  style="
                    background:#f3f4f6;
                    color:#374151;
                    border:1px solid #d1d5db;
                    padding:6px 12px;
                    border-radius:4px;
                    font-size:11px;
                    cursor:pointer;
                    display:flex;
                    align-items:center;
                    gap:4px;
                  "
                  onmouseover="this.style.background='#e5e7eb'"
                  onmouseout="this.style.background='#f3f4f6'"
                >
                  📤 공유하기
                </button>

                <button
                  onclick="regenerateExamStrategy('${examTimeline}')"
                  style="
                    background:#f3f4f6;
                    color:#374151;
                    border:1px solid #d1d5db;
                    padding:6px 12px;
                    border-radius:4px;
                    font-size:11px;
                    cursor:pointer;
                    display:flex;
                    align-items:center;
                    gap:4px;
                  "
                  onmouseover="this.style.background='#e5e7eb'"
                  onmouseout="this.style.background='#f3f4f6'"
                >
                  🔄 다시 생성
                </button>

                <a
                  href="/moodle/local/augmented_teacher/alt42/omniui/exam_preparation_system.php?userid=${encodeURIComponent(window.phpData?.studentId || window.currentUserId || '')}"
                  target="_blank"
                  style="
                    background:#3b82f6;
                    color:#fff;
                    border:1px solid #2563eb;
                    padding:6px 12px;
                    border-radius:4px;
                    font-size:11px;
                    cursor:pointer;
                    display:inline-flex;
                    align-items:center;
                    gap:4px;
                    text-decoration:none;
                  "
                >
                  🧭 시험 대비 에이전트 열기
                </a>
              </div>
            </div>
          </div>
        `;

        console.log('✅ Agent02: 전략 생성 성공');

      } else {
        const dbg = data && data.debug ? `\n(파일: ${data.debug.file || '-'} 라인: ${data.debug.line || '-'})` : '';
        throw new Error((data.message || '전략 생성 실패') + dbg);
      }

    } catch (error) {
      console.error('❌ Agent02: 전략 생성 실패:', error, '- File: agent.js, Line: 370');

      resultDiv.innerHTML = `
        <div style="padding:16px;background:#fef2f2;border:1px solid #fecaca;border-radius:6px;">
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
            <span style="font-size:20px;">⚠️</span>
            <strong style="color:#dc2626;font-size:13px;">전략 생성 실패</strong>
          </div>
          <p style="font-size:11px;color:#991b1b;margin:0;line-height:1.5;">
            전략을 생성하는 중 오류가 발생했습니다. 잠시 후 다시 시도해주세요.
            <br><br>
            <span style="color:#7f1d1d;font-family:monospace;">오류: ${error.message}</span>
          </p>
        </div>
      `;
    } finally {
      // 버튼 복원
      if (generateBtn) {
        generateBtn.disabled = false;
        generateBtn.style.opacity = '1';
        generateBtn.style.cursor = 'pointer';
      }
    }
  };

  // =============================================================================
  // 저장된 전략 보기 함수 (새 탭에서 마크다운 파일 열기)
  // =============================================================================

  window.viewExamStrategies = function() {
    console.log('📊 Agent02: 저장된 전략 보기');

    // 의사결정 지식.md 파일 경로
    const mdFilePath = 'agents/agent02_exam_schedule/의사결정 지식.md';

    // 새 탭에서 파일 열기
    const fullUrl = window.location.origin + window.location.pathname.replace(/\/[^/]*$/, '/') + mdFilePath;

    console.log('📂 Opening file:', fullUrl);

    // 새 탭에서 열기
    window.open(fullUrl, '_blank');

    console.log('✅ Agent02: 의사결정 지식.md 파일 열기 완료');
  };

  // =============================================================================
  // 보조 함수들
  // =============================================================================

  // 전략 복사 함수
  window.copyExamStrategy = function(strategyId) {
    const strategyText = document.querySelector('#exam-strategy-result pre')?.textContent;

    if (!strategyText) {
      alert('복사할 내용을 찾을 수 없습니다.');
      return;
    }

    if (navigator.clipboard) {
      navigator.clipboard.writeText(strategyText).then(() => {
        alert('전략이 클립보드에 복사되었습니다!');
      }).catch(err => {
        console.error('❌ 복사 실패:', err, '- File: agent.js, Line: 442');
        fallbackCopy(strategyText);
      });
    } else {
      fallbackCopy(strategyText);
    }
  };

  // 폴백 복사 함수
  function fallbackCopy(text) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-9999px';
    document.body.appendChild(textArea);
    textArea.select();

    try {
      document.execCommand('copy');
      alert('전략이 클립보드에 복사되었습니다!');
    } catch (err) {
      console.error('❌ 폴백 복사 실패:', err, '- File: agent.js, Line: 462');
      alert('복사에 실패했습니다. 수동으로 선택하여 복사해주세요.');
    }

    document.body.removeChild(textArea);
  }

  // 전략 공유 함수
  window.shareExamStrategy = function(strategyId) {
    const strategyText = document.querySelector('#exam-strategy-result pre')?.textContent;

    if (!strategyText) {
      alert('공유할 내용을 찾을 수 없습니다.');
      return;
    }

    if (navigator.share) {
      navigator.share({
        title: '개인 맞춤형 시험준비 전략',
        text: strategyText
      }).catch(err => {
        console.log('공유 취소됨:', err);
      });
    } else {
      // 폴백: 복사하기
      copyExamStrategy(strategyId);
    }
  };

  // 전략 재생성 함수
  window.regenerateExamStrategy = function(examTimeline) {
    if (confirm('새로운 전략을 생성하시겠습니까?')) {
      generateExamStrategy(examTimeline);
    }
  };

  // CSS 애니메이션 추가 (spin)
  if (!document.getElementById('agent02-spin-animation')) {
    const style = document.createElement('style');
    style.id = 'agent02-spin-animation';
    style.textContent = `
      @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
      }
    `;
    document.head.appendChild(style);
  }

  console.log('✅ Agent02 UI module loaded successfully');

  // 별칭 등록: index.php의 동일 함수명 충돌을 피하기 위한 UI 렌더러 별칭
  if (!window.renderAgent02PanelUI) {
    window.renderAgent02PanelUI = window.renderAgent02Panel;
  }

})();
