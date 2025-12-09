<?php   
// 일반 요청 처리
$gettext = $DB->get_record_sql("SELECT * FROM {abessi_stickynotes} WHERE userid = ? AND type = ? AND hide = 0 ORDER BY id DESC LIMIT 1", array($studentid, $pagetype));
$postittext = $gettext ? $gettext->content : '';

// 마지막 수정/생성 시간 확인
$createdTime = $gettext ? ($gettext->timecreated ?? $gettext->timemodified ?? 0) : 0;
$oneWeekAgo = time() - (7 * 24 * 60 * 60); // 1주일 전 타임스탬프
$isOlderThanWeek = $createdTime < $oneWeekAgo;

// AJAX 저장 경로
global $CFG;
$saveUrl = $CFG->wwwroot . '/local/augmented_teacher/LLM/postit_save.php';
?>

<style>
  /* 포스트잇 스타일 */
  .postit-container {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 350px;
    z-index: 1000;
    transition: all 0.3s ease;
  }
  
  .postit-container.minimized {
    width: 50px; /* 350px의 1/3 크기 */
    height: 20px;
	margin-right: 30px;
    overflow: hidden; /* 불필요한 내용 숨김 */
  }
  
  .postit {
    background: linear-gradient(135deg, #fffbe0 0%, #fff59d 100%);
    border: 1px solid #ffd54f;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.15), 0 2px 4px rgba(0,0,0,0.1);
    position: relative;
    font-family: 'Comic Sans MS', cursive, sans-serif;
    font-size: 1.9rem;
    line-height: 1.5;
    color: #2d3748;
    transform: rotate(-2deg);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    overflow: hidden;
  }
  
  /* 상단 광택 효과 (접착 부분 표현) */
  .postit::before {
    content: '';
    position: absolute;
    top: -8px;
    left: 50%;
    transform: translateX(-50%);
    width: 60px;
    height: 25px;
    background: rgba(255, 255, 255, 0.6);
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  }
  
  .postit:hover {
    transform: rotate(0deg) scale(1.02);
    box-shadow: 0 8px 16px rgba(0,0,0,0.2), 0 4px 8px rgba(0,0,0,0.15);
  }
  
  /* 헤더 영역 숨김 */
  .postit-header,
  .postit-title,
  .postit-toggle {
    display: none !important;
  }
  
  /* 바디 래퍼 제거: padding을 content로 이전 */
  .postit-body {
    padding: 0; /* 더 이상 사용하지 않음 */
    max-height: none;
  }
  
  .postit-content {
    padding: 1.5rem;
    word-wrap: break-word;
    white-space: pre-wrap;
    outline: none;
    max-height: 350px;
    overflow-y: auto;
    cursor: pointer;
    min-height: 50px;
    transition: max-height 0.3s ease, padding 0.3s ease;
  }
  
  .postit-container.minimized .postit-content {
    display: none;
  }
  
  /* 페이지 타입별 색상 무시하여 항상 노란색으로 유지 */
  .postit[class*='type-'] {
    background: linear-gradient(135deg, #fffbe0 0%, #fff59d 100%) !important;
    border-color: #ffd54f !important;
  }
  
  .postit-empty {
    color: #9ca3af;
    font-style: italic;
  }
  
  .postit-editing {
    transform: rotate(0deg) scale(1.05);
    box-shadow: 0 10px 20px rgba(0,0,0,0.25);
  }
  
  .postit-save-indicator {
    position: absolute;
    bottom: 5px;
    right: 10px;
    font-size: 1.6rem;
    color: #10b981;
    opacity: 0;
    transition: opacity 0.3s;
  }
  
  .postit-save-indicator.show {
    opacity: 1;
  }
  
  /* 최소화 상태에서는 회전 효과 제거 */
  .postit-container.minimized .postit {
    height: 100%;
  }
  
  .postit-container.minimized .postit:hover {
    transform: scale(1.05);
  }
  
  /* 최소화 상태에서 내용 완전히 숨김 */
  .postit-container.minimized .postit-content {
    display: none;
  }
  
  /* 최소화 상태에서 저장됨 인디케이터 숨김 */
  .postit-container.minimized .postit-save-indicator {
    display: none;
  }
  
  /* 페이지 타입별 색상 */
  .postit.type-homework {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border-color: #f59e0b;
  }
  
  .postit.type-homework .postit-header {
    background: rgba(245, 158, 11, 0.2);
    border-bottom-color: rgba(245, 158, 11, 0.3);
  }
  
  .postit.type-lesson {
    background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
    border-color: #10b981;
  }
  
  .postit.type-lesson .postit-header {
    background: rgba(16, 185, 129, 0.2);
    border-bottom-color: rgba(16, 185, 129, 0.3);
  }
  
  .postit.type-exam {
    background: linear-gradient(135deg, #fecaca 0%, #fca5a5 100%);
    border-color: #ef4444;
  }
  
  .postit.type-exam .postit-header {
    background: rgba(239, 68, 68, 0.2);
    border-bottom-color: rgba(239, 68, 68, 0.3);
  }
  
  .postit.type-note {
    background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
    border-color: #6366f1;
  }
  
  .postit.type-note .postit-header {
    background: rgba(99, 102, 241, 0.2);
    border-bottom-color: rgba(99, 102, 241, 0.3);
  }
  
  /* chapter 타입 추가 */
  .postit.type-chapter {
    background: linear-gradient(135deg, #fce7f3 0%, #fbcfe8 100%);
    border-color: #ec4899;
  }
  
  .postit.type-chapter .postit-header {
    background: rgba(236, 72, 153, 0.2);
    border-bottom-color: rgba(236, 72, 153, 0.3);
  }
</style>

<div class="postit-container" id="postit-container-<?php echo htmlspecialchars($pagetype, ENT_QUOTES, 'UTF-8'); ?>-<?php echo htmlspecialchars($studentid, ENT_QUOTES, 'UTF-8'); ?>">
  <div class="postit <?php echo 'type-' . htmlspecialchars($pagetype, ENT_QUOTES, 'UTF-8'); ?>" 
       id="postit-<?php echo htmlspecialchars($pagetype, ENT_QUOTES, 'UTF-8'); ?>-<?php echo htmlspecialchars($studentid, ENT_QUOTES, 'UTF-8'); ?>">
    <div class="postit-content" contenteditable="false">
      <?php if(!empty($postittext)): ?>
        <?php echo htmlspecialchars($postittext, ENT_QUOTES, 'UTF-8'); ?>
      <?php else: ?>
        <span class="postit-empty">클릭하여 메모 작성</span>
      <?php endif; ?>
    </div>
    <div class="postit-save-indicator">저장됨 ✓</div>
  </div>
</div>

<script>
(function() {
  const container = document.getElementById('postit-container-<?php echo htmlspecialchars($pagetype, ENT_QUOTES, 'UTF-8'); ?>-<?php echo htmlspecialchars($studentid, ENT_QUOTES, 'UTF-8'); ?>');
  const postit = document.getElementById('postit-<?php echo htmlspecialchars($pagetype, ENT_QUOTES, 'UTF-8'); ?>-<?php echo htmlspecialchars($studentid, ENT_QUOTES, 'UTF-8'); ?>');
  const content = postit.querySelector('.postit-content');
  const saveIndicator = postit.querySelector('.postit-save-indicator');
  const studentId = '<?php echo htmlspecialchars($studentid, ENT_QUOTES, 'UTF-8'); ?>';
  const pageType = '<?php echo htmlspecialchars($pagetype, ENT_QUOTES, 'UTF-8'); ?>';
  const saveUrl = '<?php echo $saveUrl; ?>';
  let saveTimeout;
  let isEditing = false;
  let originalText = '';
  let isMinimized = false;
  
  // PHP에서 전달받은 값
  const hasContent = <?php echo (!empty($postittext)) ? 'true' : 'false'; ?>;
  const isOlderThanWeek = <?php echo $isOlderThanWeek ? 'true' : 'false'; ?>;
  
  // 자동 최소화 조건 확인
  const shouldAutoMinimize = !hasContent || isOlderThanWeek;
  
  // localStorage에서 최소화 상태 복원 (수동으로 변경한 경우 우선)
  const storedState = localStorage.getItem('postit-state-' + pageType + '-' + studentId);
  const hasManualState = localStorage.getItem('postit-manual-state-' + pageType + '-' + studentId) === 'true';
  
  // 수동으로 상태를 변경한 적이 있으면 저장된 상태 사용, 없으면 자동 최소화 조건 적용
  if (hasManualState && storedState) {
    isMinimized = storedState === 'minimized';
  } else {
    // 초기에는 항상 펼쳐진 상태로 시작
    isMinimized = false;
    
    // 5초 후 자동 최소화 (수동으로 변경하지 않은 경우에만)
    if (shouldAutoMinimize) {
      setTimeout(() => {
        // 수동으로 상태를 변경하지 않았다면 최소화
        const currentManualState = localStorage.getItem('postit-manual-state-' + pageType + '-' + studentId) === 'true';
        if (!currentManualState) {
          isMinimized = true;
          container.classList.add('minimized');
          localStorage.setItem('postit-state-' + pageType + '-' + studentId, 'minimized');
        }
      }, 5000); // 5초 후 실행
    }
  }
  
  // 초기 상태 적용
  if (isMinimized) {
    container.classList.add('minimized');
  }
  
  // 포스트잇 더블클릭 시 최소화/펼치기
  postit.addEventListener('dblclick', function(e) {
    // 편집 중이면 무시
    if (isEditing) return;
    e.stopPropagation();
    isMinimized = !isMinimized;
    
    // 수동 상태 변경 표시
    localStorage.setItem('postit-manual-state-' + pageType + '-' + studentId, 'true');
    
    if (isMinimized) {
      container.classList.add('minimized');
      localStorage.setItem('postit-state-' + pageType + '-' + studentId, 'minimized');
    } else {
      container.classList.remove('minimized');
      localStorage.setItem('postit-state-' + pageType + '-' + studentId, 'expanded');
    }
  });
  
  // URL을 링크로 변환하는 함수
  function convertUrlsToLinks(text) {
    const urlRegex = /(https?:\/\/[^\s]+)/g;
    return text.replace(urlRegex, '<a href="$1" target="_blank" style="color: #3b82f6; text-decoration: underline;">$1</a>');
  }
  
  // 초기 로드 시 URL 변환
  if (!content.querySelector('.postit-empty')) {
    let currentText = content.textContent;
    if (currentText) {
      // 앞뒤 공백(특히 \n)을 제거하여 불필요한 빈 줄 방지
      currentText = currentText.trimStart();
      content.innerHTML = convertUrlsToLinks(currentText);
    }
  }
  
  // 클릭하여 편집 모드 시작
  content.addEventListener('click', function(e) {
    // 최소화 상태에서는 작동하지 않음
    if (isMinimized) return;
    
    // 링크를 클릭한 경우는 편집 모드로 전환하지 않음
    if (e.target.tagName === 'A') {
      return;
    }
    
    if (!isEditing) {
      e.stopPropagation();
      startEdit();
    }
  });
  
  // 편집 모드 시작
  function startEdit() {
    isEditing = true;
    
    // 현재 텍스트만 추출 (HTML 태그 제거)
    originalText = content.textContent;
    
    // 편집 모드에서는 순수 텍스트만 표시
    content.innerHTML = originalText;
    content.contentEditable = 'true';
    postit.classList.add('postit-editing');
    
    // 빈 메모일 경우 텍스트 제거
    if (content.querySelector('.postit-empty') || originalText === '클릭하여 메모 작성') {
      content.innerHTML = '';
    }
    
    content.focus();
    
    // 전체 텍스트 선택
    const range = document.createRange();
    range.selectNodeContents(content);
    const selection = window.getSelection();
    selection.removeAllRanges();
    selection.addRange(range);
  }
  
  // 편집 모드 종료
  function endEdit() {
    isEditing = false;
    content.contentEditable = 'false';
    postit.classList.remove('postit-editing');
    
    const currentText = content.textContent.trim();
    
    // 빈 내용일 경우 기본 텍스트 표시
    if (currentText === '') {
      content.innerHTML = '<span class="postit-empty">클릭하여 메모 작성</span>';
    } else {
      // URL을 링크로 변환하여 표시
      content.innerHTML = convertUrlsToLinks(currentText);
    }
  }
  
  // 포커스 아웃 시 저장
  content.addEventListener('blur', function() {
    if (isEditing) {
      saveContent();
      endEdit();
    }
  });
  
  // 입력 시 자동 저장 (디바운싱)
  content.addEventListener('input', function() {
    clearTimeout(saveTimeout);
    saveTimeout = setTimeout(saveContent, 1000); // 1초 후 자동 저장
  });
  
  // Ctrl+Enter로 저장하고 편집 종료
  content.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.key === 'Enter') {
      e.preventDefault();
      saveContent();
      endEdit();
    }
  });
  
  // 내용 저장
  function saveContent() {
    const text = content.textContent.trim();
    
    console.log('저장 시도:', { studentId, pageType, text });
    console.log('저장 URL:', saveUrl);
    
    // 새로운 내용이 저장되면 수동 상태 리셋
    if (text) {
      localStorage.removeItem('postit-manual-state-' + pageType + '-' + studentId);
    }
    
    // AJAX 요청
    const xhr = new XMLHttpRequest();
    xhr.open('POST', saveUrl, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onload = function() {
      console.log('응답 상태:', xhr.status);
      console.log('응답 내용:', xhr.responseText);
      
      if (xhr.status === 200) {
        try {
          const response = JSON.parse(xhr.responseText);
          console.log('파싱된 응답:', response);
          
          if (response.success) {
            // 저장 완료 표시
            saveIndicator.classList.add('show');
            setTimeout(() => {
              saveIndicator.classList.remove('show');
            }, 2000);
          } else {
            console.error('저장 실패:', response.message);
            alert('저장 실패: ' + response.message);
          }
        } catch (e) {
          console.error('저장 응답 파싱 오류:', e);
          console.error('원본 응답:', xhr.responseText);
          alert('응답 처리 중 오류가 발생했습니다.');
        }
      } else {
        console.error('HTTP 오류:', xhr.status);
        alert('서버 연결 오류가 발생했습니다.');
      }
    };
    
    xhr.onerror = function() {
      console.error('네트워크 오류');
      alert('네트워크 오류가 발생했습니다.');
    };
    
    const params = 'ajax_action=save_postit' +
                  '&studentid=' + encodeURIComponent(studentId) +
                  '&pagetype=' + encodeURIComponent(pageType) +
                  '&content=' + encodeURIComponent(text);
    
    console.log('전송 파라미터:', params);
    xhr.send(params);
  }
  
  // 페이지 클릭 시 편집 모드 종료
  document.addEventListener('click', function(e) {
    if (isEditing && !postit.contains(e.target)) {
      saveContent();
      endEdit();
    }
  });
})();
</script>

