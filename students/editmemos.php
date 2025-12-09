<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
  
$userid = $_GET["userid"];
$timecreated=time(); 

$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;
if($role==='student')
{
    echo '편집 권한이 없습니다.';
    exit();
}
// 각 타입별 최신 메모 조회
$types = array(
    'timescaffolding' => '포모도르',
    'chapter' => '컨텐츠 페이지',
    'edittoday' => '목표설정',
    'mystudy' => '내공부방',
    'today' => '공부결과'
);

$memos = array();
foreach($types as $type => $label) {
    $sql = "SELECT * FROM {abessi_stickynotes} 
            WHERE userid = :userid AND type = :type 
            ORDER BY id DESC LIMIT 1";
    
    $params = array('userid' => $userid, 'type' => $type);
    $memo = $DB->get_record_sql($sql, $params);
    
    if($memo) {
        $memos[$type] = $memo;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>메모 편집</title>
    <style>
        body {
            font-family: 'Malgun Gothic', sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .memo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
        }
        .memo-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: box-shadow 0.3s;
            position: relative;
        }
        .memo-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .memo-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
        }
        .memo-type {
            font-size: 18px;
            font-weight: bold;
            color: #1976d2;
        }
        .memo-date {
            font-size: 12px;
            color: #666;
        }
        .memo-content {
            min-height: 100px;
            padding: 10px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            background-color: #fafafa;
            cursor: pointer;
            white-space: pre-wrap;
            word-wrap: break-word;
            transition: all 0.2s ease;
            overflow: hidden;
        }
        .memo-content:hover {
            background-color: #f0f0f0;
            border-color: #ccc;
        }
        .memo-content.editing {
            background-color: white;
            border-color: #1976d2;
            cursor: text;
            box-shadow: 0 0 0 2px rgba(25, 118, 210, 0.2);
            padding: 0;
        }
        
        .edit-wrapper {
            width: 100%;
            box-sizing: border-box;
        }
        
        textarea.edit-field {
            width: 100%;
            min-height: 120px;
            padding: 10px;
            border: none;
            border-radius: 4px;
            font-family: inherit;
            font-size: 14px;
            resize: vertical;
            outline: none;
            transition: all 0.2s ease;
            box-sizing: border-box;
            background-color: transparent;
            line-height: 1.4;
        }
        
        textarea.edit-field:focus {
            background-color: rgba(25, 118, 210, 0.02);
        }
        
        .edit-controls {
            margin: 8px 10px 10px 10px;
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            padding-top: 8px;
            border-top: 1px solid #f0f0f0;
        }
        .btn {
            padding: 6px 16px;
            border: none;
            border-radius: 4px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 500;
            white-space: nowrap;
        }
        .btn-save {
            background-color: #1976d2;
            color: white;
        }
        .btn-save:hover {
            background-color: #1565c0;
        }
        .btn-save:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        .btn-cancel {
            background-color: #f5f5f5;
            color: #666;
            border: 1px solid #ddd;
        }
        .btn-cancel:hover {
            background-color: #e0e0e0;
        }
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s;
        }
        .loading-overlay.show {
            opacity: 1;
            visibility: visible;
        }
        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #1976d2;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .save-status {
            margin-top: 10px;
            font-size: 12px;
            color: #4caf50;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .save-status.show {
            opacity: 1;
        }
        .save-status.error {
            color: #f44336;
        }
        .empty-memo {
            text-align: center;
            color: #999;
            padding: 40px;
            background-color: #f9f9f9;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .empty-memo:hover {
            background-color: #f0f0f0;
            color: #666;
        }
        .click-hint {
            font-size: 11px;
            color: #999;
            margin-top: 5px;
            font-style: italic;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="container">
        <h1>학습 메모 편집</h1>
        <div class="memo-grid">
            <?php foreach($types as $type => $label): ?>
                <div class="memo-card">
                    <div class="memo-header">
                        <span class="memo-type"><?php echo $label; ?></span>
                        <?php if(isset($memos[$type])): ?>
                            <span class="memo-date">
                                <?php echo date('Y-m-d H:i', $memos[$type]->created_at); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if(isset($memos[$type])): ?>
                        <div class="memo-content" 
                             data-id="<?php echo $memos[$type]->id; ?>"
                             data-type="<?php echo $type; ?>"
                             data-created-at="<?php echo $memos[$type]->created_at; ?>">
                            <?php echo htmlspecialchars($memos[$type]->content); ?>
                        </div>
                        <div class="click-hint">클릭하여 편집</div>
                        <div class="save-status"></div>
                    <?php else: ?>
                        <div class="empty-memo" data-type="<?php echo $type; ?>">
                            아직 작성된 메모가 없습니다.<br>
                            <span style="font-size: 13px;">클릭하여 새 메모 작성</span>
                        </div>
                        <div class="save-status"></div>
                    <?php endif; ?>
                    <div class="loading-overlay">
                        <div class="spinner"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        let editingElement = null;
        let savingInProgress = false;
        
        // 메모 클릭시 편집 (싱글클릭으로 변경)
        $(document).on('click', '.memo-content, .empty-memo', function(e) {
            e.stopPropagation();
            
            // 이미 편집중이거나 저장중이면 무시
            if (editingElement || savingInProgress) {
                return;
            }
            
            var $this = $(this);
            var isEmptyMemo = $this.hasClass('empty-memo');
            var memoCard = $this.closest('.memo-card');
            var type = memoCard.find('.memo-type').text();
            
            // 현재 내용 가져오기
            var currentContent = isEmptyMemo ? '' : $this.text();
            var memoId = isEmptyMemo ? 0 : $this.data('id');
            var timeCreated = isEmptyMemo ? 0 : $this.data('created-at');
            
            // 타입 매핑
            var typeMap = {
                '포모도르': 'timescaffolding',
                '컨텐츠 페이지': 'chapter',
                '목표설정': 'edittoday',
                '내공부방': 'mystudy',
                '공부결과': 'today'
            };
            var memoType = isEmptyMemo ? $this.data('type') : typeMap[type];
            
            // 편집 UI 생성
            var editHtml = `
                <div class="edit-wrapper">
                    <textarea class="edit-field" placeholder="메모 내용을 입력하세요...">${currentContent}</textarea>
                    <div class="edit-controls">
                        <button class="btn btn-cancel">취소</button>
                        <button class="btn btn-save">저장</button>
                    </div>
                </div>
            `;
            
            // 원본 저장
            editingElement = {
                element: $this,
                content: currentContent,
                isEmptyMemo: isEmptyMemo,
                memoCard: memoCard,
                originalHtml: $this.prop('outerHTML') // 원본 HTML 저장
            };
            
            // 편집 UI로 교체 (부드러운 전환을 위해 페이드 효과 추가)
            if (isEmptyMemo) {
                $this.fadeOut(150, function() {
                    $this.replaceWith(editHtml);
                    memoCard.find('.edit-wrapper').hide().fadeIn(150);
                    focusAndSetupEvents();
                });
            } else {
                $this.addClass('editing');
                $this.fadeOut(100, function() {
                    $this.html(editHtml);
                    $this.fadeIn(100, function() {
                        focusAndSetupEvents();
                    });
                });
            }
            
            function focusAndSetupEvents() {
                // textarea에 포커스
                var textarea = memoCard.find('.edit-field');
                setTimeout(function() {
                    textarea.focus();
                    // 커서를 텍스트 끝으로 이동
                    if (textarea[0]) {
                        textarea[0].setSelectionRange(textarea.val().length, textarea.val().length);
                    }
                }, 50);
                
                // 저장 버튼 클릭
                memoCard.find('.btn-save').on('click', function() {
                    if (savingInProgress) return;
                    
                    var newContent = textarea.val().trim();
                    
                    if (newContent === '') {
                        alert('내용을 입력해주세요.');
                        textarea.focus();
                        return;
                    }
                    
                    saveMemo(memoCard, memoId, memoType, newContent, timeCreated, currentContent);
                });
                
                // 취소 버튼 클릭
                memoCard.find('.btn-cancel').on('click', function() {
                    cancelEdit();
                });
                
                // Enter+Ctrl/Cmd로 저장
                textarea.on('keydown', function(e) {
                    if ((e.ctrlKey || e.metaKey) && e.keyCode === 13) {
                        memoCard.find('.btn-save').click();
                    } else if (e.keyCode === 27) { // ESC
                        cancelEdit();
                    }
                });
                
                // 자동 높이 조절
                textarea.on('input', function() {
                    autoResize(this);
                });
                
                // 초기 높이 설정
                autoResize(textarea[0]);
            }
        });
        
        // 편집 취소 함수
        function cancelEdit() {
            if (!editingElement) return;
            
            var memoCard = editingElement.memoCard;
            var editWrapper = memoCard.find('.edit-wrapper');
            
            if (editingElement.isEmptyMemo) {
                // 빈 메모인 경우 원본으로 복원
                editWrapper.fadeOut(150, function() {
                    editWrapper.replaceWith(editingElement.element);
                    editingElement.element.hide().fadeIn(150);
                    editingElement = null;
                });
            } else {
                // 기존 메모인 경우 원본 내용으로 복원
                editWrapper.fadeOut(100, function() {
                    editingElement.element.removeClass('editing')
                                          .text(editingElement.content)
                                          .hide()
                                          .fadeIn(100);
                    editingElement = null;
                });
            }
        }
        
        // 텍스트 영역 자동 높이 조절 함수
        function autoResize(textarea) {
            if (!textarea) return;
            
            // 높이를 초기화하여 정확한 스크롤 높이 측정
            textarea.style.height = 'auto';
            
            // 최소 높이와 최대 높이 설정
            var minHeight = 120;
            var maxHeight = 300;
            var scrollHeight = textarea.scrollHeight;
            
            // 계산된 높이 적용 (최소/최대 범위 내에서)
            var newHeight = Math.max(minHeight, Math.min(maxHeight, scrollHeight));
            textarea.style.height = newHeight + 'px';
            
            // 최대 높이에 도달한 경우 스크롤 표시
            if (scrollHeight > maxHeight) {
                textarea.style.overflowY = 'auto';
            } else {
                textarea.style.overflowY = 'hidden';
            }
        }
        
        // 메모 저장 함수
        function saveMemo(memoCard, memoId, memoType, newContent, timeCreated, originalContent) {
            savingInProgress = true;
            
            // 로딩 표시
            var loadingOverlay = memoCard.find('.loading-overlay');
            var saveBtn = memoCard.find('.btn-save');
            
            loadingOverlay.addClass('show');
            saveBtn.prop('disabled', true).text('저장중...');
            
            // AJAX로 저장
            $.ajax({
                url: 'savememo.php',
                method: 'POST',
                data: {
                    id: memoId,
                    userid: <?php echo $userid; ?>,
                    type: memoType,
                    content: newContent,
                    created_at: timeCreated
                },
                success: function(response) {
                    console.log('서버 응답:', response); // 디버깅용 로그
                    
                    try {
                        var data = typeof response === 'string' ? JSON.parse(response) : response;
                        
                        if (data.success) {
                            // 저장 성공
                            if (editingElement.isEmptyMemo) {
                                // 새 메모가 생성된 경우
                                showSaveStatus(memoCard, '저장되었습니다!', false);
                                setTimeout(function() {
                                    location.reload();
                                }, 1000);
                            } else {
                                // 기존 메모 업데이트 - 부드러운 전환 적용
                                var editWrapper = memoCard.find('.edit-wrapper');
                                editWrapper.fadeOut(150, function() {
                                    editingElement.element.removeClass('editing')
                                                          .text(newContent)
                                                          .data('id', data.id)
                                                          .data('created-at', data.created_at)
                                                          .hide()
                                                          .fadeIn(150);
                                    
                                    // 날짜 업데이트 (새로 생성된 경우)
                                    if (data.action === 'duplicated') {
                                        var dateSpan = memoCard.find('.memo-date');
                                        var newDate = new Date(data.created_at * 1000);
                                        dateSpan.text(formatDate(newDate));
                                    }
                                    
                                    showSaveStatus(memoCard, '저장되었습니다!', false);
                                    editingElement = null;
                                });
                            }
                        } else {
                            var errorMsg = data.error || '알 수 없는 오류가 발생했습니다.';
                            console.error('저장 실패:', errorMsg); // 디버깅용 로그
                            showSaveStatus(memoCard, '저장 실패: ' + errorMsg, true);
                            saveBtn.prop('disabled', false).text('저장');
                        }
                    } catch (parseError) {
                        console.error('JSON 파싱 오류:', parseError, '원본 응답:', response);
                        showSaveStatus(memoCard, '서버 응답 파싱 오류: ' + parseError.message, true);
                        saveBtn.prop('disabled', false).text('저장');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX 오류:', {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        responseText: xhr.responseText,
                        error: error
                    });
                    
                    var errorMsg = '서버 연결 오류';
                    if (xhr.status === 404) {
                        errorMsg = 'savememo.php 파일을 찾을 수 없습니다.';
                    } else if (xhr.status === 500) {
                        errorMsg = '서버 내부 오류: ' + (xhr.responseText || error);
                    } else if (xhr.responseText) {
                        errorMsg += ': ' + xhr.responseText;
                    }
                    
                    showSaveStatus(memoCard, errorMsg, true);
                    saveBtn.prop('disabled', false).text('저장');
                },
                complete: function() {
                    loadingOverlay.removeClass('show');
                    savingInProgress = false;
                }
            });
        }
        
        // 저장 상태 메시지 표시
        function showSaveStatus(memoCard, message, isError) {
            var saveStatus = memoCard.find('.save-status');
            saveStatus.text(message)
                      .toggleClass('error', isError)
                      .addClass('show');
            
            setTimeout(function() {
                saveStatus.removeClass('show');
            }, 3000);
        }
        
        // 날짜 포맷 함수
        function formatDate(date) {
            var year = date.getFullYear();
            var month = String(date.getMonth() + 1).padStart(2, '0');
            var day = String(date.getDate()).padStart(2, '0');
            var hours = String(date.getHours()).padStart(2, '0');
            var minutes = String(date.getMinutes()).padStart(2, '0');
            
            return `${year}-${month}-${day} ${hours}:${minutes}`;
        }
        
        // 외부 클릭시 편집 취소 방지
        $(document).on('click', function(e) {
            if (editingElement && !$(e.target).closest('.memo-card').length) {
                e.preventDefault();
            }
        });
    });
    </script>
</body>
</html>