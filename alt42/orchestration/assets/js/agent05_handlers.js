/**
 * Agent05 학습감정 분석 - 이벤트 핸들러
 * File: orchestration/assets/js/agent05_handlers.js
 *
 * Agent05 UI 통합을 위한 핸들러
 */

/**
 * Agent05 패널 렌더링 (우측 detail-panel)
 * [agent05_handlers.js:9]
 */
window.renderAgent05Panel = function(panel) {
  console.log('[agent05_handlers.js:renderAgent05Panel] Agent05 패널 렌더링');

  if (!panel) {
    console.error('[agent05_handlers.js:renderAgent05Panel] panel element not found');
    return;
  }

  // Agent05ActivityCategories 존재 확인
  if (typeof window.Agent05ActivityCategories === 'undefined') {
    console.error('[agent05_handlers.js:renderAgent05Panel] Agent05ActivityCategories not loaded');
    panel.innerHTML = `
      <div style="padding:20px;background:#fee;border:2px solid #fcc;border-radius:8px;color:#c33;">
        ⚠️ Agent05 모듈을 불러올 수 없습니다. 페이지를 새로고침해주세요.
      </div>
    `;
    return;
  }

  const agent05Categories = window.Agent05ActivityCategories.getAllCategories();

  // 패널 HTML 생성
  let html = `
    <div id="agent05-container" style="padding:20px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.1);">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <h3 style="color:white;font-size:18px;font-weight:600;margin:0;text-shadow:0 2px 4px rgba(0,0,0,0.2);">
          🎭 학습감정 분석 인터페이스 (Agent05)
        </h3>
        <button onclick="document.getElementById('agent05-grid').style.display = document.getElementById('agent05-grid').style.display === 'none' ? 'grid' : 'none'; this.textContent = document.getElementById('agent05-grid').style.display === 'none' ? '펼치기' : '접기';"
                style="padding:8px 16px;background:rgba(255,255,255,0.2);color:white;border:2px solid rgba(255,255,255,0.5);border-radius:8px;cursor:pointer;font-size:13px;font-weight:600;transition:all 0.3s;backdrop-filter:blur(10px);"
                onmouseover="this.style.background='rgba(255,255,255,0.3)'"
                onmouseout="this.style.background='rgba(255,255,255,0.2)'">
          접기
        </button>
      </div>

      <div id="agent05-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;">
  `;

  // 활동 카드 렌더링
  agent05Categories.forEach((category, index) => {
    html += `
      <div class="agent05-activity-card"
           data-category-key="${category.key}"
           data-category-name="${category.name}"
           style="background:white;border-radius:15px;padding:25px;cursor:pointer;transition:all 0.3s ease;box-shadow:0 4px 15px rgba(0,0,0,0.1);"
           onmouseover="this.style.transform='translateY(-5px)';this.style.boxShadow='0 8px 25px rgba(0,0,0,0.2)';"
           onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 4px 15px rgba(0,0,0,0.1)';"
           onclick="handleAgent05CardClick('${category.key}', '${category.name}', ${JSON.stringify(category.subItems).replace(/"/g, '&quot;')})">
        <span style="font-size:3rem;margin-bottom:15px;display:block;">${category.icon}</span>
        <h3 style="color:#667eea;font-size:1.5rem;margin-bottom:10px;">${category.name}</h3>
        <div style="color:#666;font-size:0.95rem;line-height:1.5;">
          ${category.subItems.length}개 세부 활동 유형
        </div>
      </div>
    `;
  });

  html += `
      </div>

      <div style="margin-top:15px;padding:12px;background:rgba(255,255,255,0.15);border-radius:8px;font-size:13px;color:white;backdrop-filter:blur(10px);">
        💡 <strong>Tip:</strong> 활동 카드를 클릭하면 세부 항목을 선택할 수 있습니다.
      </div>
    </div>
  `;

  panel.innerHTML = html;
};

/**
 * Agent05 활동 카드 클릭 핸들러
 * [agent05_handlers.js:10]
 */
window.handleAgent05CardClick = function(categoryKey, categoryName, subItems) {
  console.log('[agent05_handlers.js:handleAgent05CardClick] 활동 선택:', categoryKey, categoryName);

  // subItems 파싱 (문자열인 경우)
  let parsedSubItems = subItems;
  if (typeof subItems === 'string') {
    try {
      parsedSubItems = JSON.parse(subItems.replace(/&quot;/g, '"'));
    } catch (e) {
      console.error('[agent05_handlers.js:handleAgent05CardClick] subItems 파싱 실패:', e);
      parsedSubItems = [];
    }
  }

  // 모달 오버레이 가져오기 또는 생성
  let modalOverlay = document.getElementById('modal-overlay');
  if (!modalOverlay) {
    console.log('[agent05_handlers.js:handleAgent05CardClick] modal-overlay 생성');
    modalOverlay = document.createElement('div');
    modalOverlay.id = 'modal-overlay';
    modalOverlay.className = 'modal-overlay';
    modalOverlay.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.6);display:flex;align-items:center;justify-content:center;z-index:10000;';
    document.body.appendChild(modalOverlay);
  }

  // 모달 컨텐츠 생성
  let modalHtml = `
    <div class="modal-content" style="background:white;border-radius:15px;padding:30px;max-width:600px;width:90%;max-height:80vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
      <h3 style="color:#667eea;margin-bottom:20px;font-size:1.5rem;">
        ${categoryName} - 세부 활동 선택
      </h3>
      <div style="display:flex;flex-direction:column;gap:10px;">
  `;

  // 세부 항목 버튼들
  if (parsedSubItems && parsedSubItems.length > 0) {
    parsedSubItems.forEach((item, index) => {
      modalHtml += `
        <button class="sub-item-btn"
                data-category-key="${categoryKey}"
                data-category-name="${categoryName}"
                data-item="${item}"
                data-index="${index}"
                style="width:100%;padding:12px 20px;border:2px solid #e2e8f0;background:#fff;color:#475569;border-radius:10px;cursor:pointer;transition:all 0.3s;text-align:left;font-size:15px;"
                onmouseover="this.style.borderColor='#667eea';this.style.background='#f0f4ff';"
                onmouseout="this.style.borderColor='#e2e8f0';this.style.background='#fff';"
                onclick="handleAgent05SubItemClick('${categoryKey}', '${categoryName}', '${item}', ${index})">
          ${index + 1}. ${item}
        </button>
      `;
    });
  } else {
    modalHtml += '<p style="color:#666;">표시할 항목이 없습니다.</p>';
  }

  modalHtml += `
      </div>
      <button class="btn-close"
              style="margin-top:20px;padding:10px 20px;background:#94a3b8;color:white;border:none;border-radius:8px;cursor:pointer;font-size:1rem;transition:background 0.3s;"
              onmouseover="this.style.background='#64748b';"
              onmouseout="this.style.background='#94a3b8';"
              onclick="closeAgent05Modal()">
        닫기
      </button>
    </div>
  `;

  modalOverlay.innerHTML = modalHtml;
  modalOverlay.style.display = 'flex';
};

/**
 * Agent05 세부 항목 클릭 핸들러
 * [agent05_handlers.js:88]
 */
window.handleAgent05SubItemClick = function(categoryKey, categoryName, itemName, itemIndex) {
  console.log('[agent05_handlers.js:handleAgent05SubItemClick] 세부 항목 선택:', categoryName, itemName);

  // 선택 상태 저장
  if (typeof window.Agent05State !== 'undefined') {
    window.Agent05State.selectedActivity = {
      key: categoryKey,
      name: categoryName
    };
    window.Agent05State.selectedSubItem = {
      categoryKey: categoryKey,
      categoryName: categoryName,
      itemName: itemName,
      itemIndex: itemIndex
    };
  }

  // 모달 닫기
  closeAgent05Modal();

  // 임시 메시지 표시
  showAgent05TemporaryMessage(categoryName, itemName);
};

/**
 * Agent05 모달 닫기
 * [agent05_handlers.js:118]
 */
window.closeAgent05Modal = function() {
  console.log('[agent05_handlers.js:closeAgent05Modal] 모달 닫기');
  const modalOverlay = document.getElementById('modal-overlay');
  if (modalOverlay) {
    modalOverlay.style.display = 'none';
    modalOverlay.innerHTML = '';
  }
};

/**
 * Agent05 임시 메시지 팝업 표시
 * [agent05_handlers.js:131]
 */
window.showAgent05TemporaryMessage = function(activityName, subItemName) {
  console.log('[agent05_handlers.js:showAgent05TemporaryMessage] 임시 메시지 표시');

  const modalOverlay = document.getElementById('modal-overlay');
  if (!modalOverlay) return;

  const messageHtml = `
    <div class="modal-content" style="background:white;border-radius:15px;padding:30px;max-width:500px;width:90%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
      <h3 style="color:#667eea;margin-bottom:20px;">
        📋 선택 완료
      </h3>
      <div style="margin-bottom:20px;">
        <p style="font-size:1.1rem;margin-bottom:10px;">
          <strong>활동:</strong> ${activityName}
        </p>
        <p style="font-size:1.1rem;margin-bottom:20px;">
          <strong>세부 항목:</strong> ${subItemName}
        </p>
        <div style="background:#f0f4ff;padding:20px;border-radius:10px;margin:20px 0;">
          <p style="color:#667eea;font-size:1.05rem;line-height:1.6;">
            추후 학생의 감정유형과 관련된 설문이 추가될 예정입니다.
          </p>
        </div>
      </div>
      <button class="btn-close"
              style="background:#667eea;color:white;border:none;border-radius:8px;padding:12px 30px;font-size:1rem;cursor:pointer;transition:background 0.3s;"
              onmouseover="this.style.background='#5568d3';"
              onmouseout="this.style.background='#667eea';"
              onclick="closeAgent05TemporaryMessage()">
        확인
      </button>
    </div>
  `;

  modalOverlay.innerHTML = messageHtml;
  modalOverlay.style.display = 'flex';
};

/**
 * Agent05 임시 메시지 팝업 닫기
 * [agent05_handlers.js:178]
 */
window.closeAgent05TemporaryMessage = function() {
  console.log('[agent05_handlers.js:closeAgent05TemporaryMessage] 임시 메시지 닫기');

  // 상태 초기화
  if (typeof window.Agent05State !== 'undefined') {
    window.Agent05State.selectedActivity = null;
    window.Agent05State.selectedSubItem = null;
  }

  // 모달 닫기
  closeAgent05Modal();
};
