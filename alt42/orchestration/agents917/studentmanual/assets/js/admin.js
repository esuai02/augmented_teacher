/**
 * Student Manual System - Admin JavaScript
 * File: alt42/orchestration/agents/studentmanual/assets/js/admin.js
 *
 * 관리 페이지 기능: 항목 목록, 추가, 수정, 삭제, 파일 업로드
 */

(function() {
    'use strict';

    // 전역 변수
    let uploadedContents = [];
    let editingItemId = null;

    // DOM 요소
    const itemsList = document.getElementById('items-list');
    const itemForm = document.getElementById('item-form');
    const formTitle = document.getElementById('form-title');
    const addItemBtn = document.getElementById('add-item-btn');
    const cancelBtn = document.getElementById('cancel-btn');
    const fileUploadArea = document.getElementById('file-upload-area');
    const fileInput = document.getElementById('file-input');
    const linkInputGroup = document.getElementById('link-input-group');
    const contentTypeSelect = document.getElementById('content_type');
    const uploadedFilesDiv = document.getElementById('uploaded-files');
    const itemIdInput = document.getElementById('item-id');

    // 초기화
    function init() {
        setupEventListeners();
        loadItemsList();
    }

    // 이벤트 리스너 설정
    function setupEventListeners() {
        // 새 항목 추가 버튼
        if (addItemBtn) {
            addItemBtn.addEventListener('click', resetForm);
        }

        // 취소 버튼
        if (cancelBtn) {
            cancelBtn.addEventListener('click', resetForm);
        }

        // 폼 제출
        if (itemForm) {
            itemForm.addEventListener('submit', handleFormSubmit);
        }

        // 파일 업로드 영역
        if (fileUploadArea) {
            fileUploadArea.addEventListener('click', () => {
                if (contentTypeSelect.value !== 'link') {
                    fileInput.click();
                }
            });

            fileUploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                fileUploadArea.classList.add('dragover');
            });

            fileUploadArea.addEventListener('dragleave', () => {
                fileUploadArea.classList.remove('dragover');
            });

            fileUploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                fileUploadArea.classList.remove('dragover');
                if (e.dataTransfer.files.length > 0) {
                    handleFileUpload(e.dataTransfer.files[0]);
                }
            });
        }

        // 파일 입력
        if (fileInput) {
            fileInput.addEventListener('change', (e) => {
                if (e.target.files.length > 0) {
                    handleFileUpload(e.target.files[0]);
                }
            });
        }

        // 컨텐츠 타입 변경
        if (contentTypeSelect) {
            contentTypeSelect.addEventListener('change', (e) => {
                if (e.target.value === 'link') {
                    linkInputGroup.classList.remove('hidden');
                    fileUploadArea.style.display = 'none';
                } else {
                    linkInputGroup.classList.add('hidden');
                    fileUploadArea.style.display = 'block';
                }
            });
        }

        // 외부 링크 입력
        const externalUrlInput = document.getElementById('external_url');
        const addLinkBtn = document.getElementById('add-link-btn');
        
        if (externalUrlInput) {
            externalUrlInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    handleLinkUpload();
                }
            });
        }

        if (addLinkBtn) {
            addLinkBtn.addEventListener('click', handleLinkUpload);
        }
    }

    // 항목 목록 로드
    function loadItemsList() {
        fetch(window.apiBase + 'manage_item.php?action=list')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderItemsList(data.data);
                } else {
                    itemsList.innerHTML = '<p style="color: red;">항목을 불러오는 중 오류가 발생했습니다.</p>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                itemsList.innerHTML = '<p style="color: red;">항목을 불러오는 중 오류가 발생했습니다.</p>';
            });
    }

    // 항목 목록 렌더링
    function renderItemsList(items) {
        if (items.length === 0) {
            itemsList.innerHTML = '<p>등록된 항목이 없습니다.</p>';
            return;
        }

        itemsList.innerHTML = items.map(item => {
            const agentName = window.agents[item.agent_id] || item.agent_id;
            return `
                <div class="item-row">
                    <div class="item-info">
                        <h3>${escapeHtml(item.title)}</h3>
                        <p>${agentName} | ${item.contents ? item.contents.length + '개 컨텐츠' : '컨텐츠 없음'}</p>
                    </div>
                    <div class="item-actions">
                        <button class="btn-edit" onclick="editItem(${item.id})">수정</button>
                        <button class="btn-delete" onclick="deleteItem(${item.id})">삭제</button>
                    </div>
                </div>
            `;
        }).join('');
    }

    // 항목 수정
    window.editItem = function(itemId) {
        fetch(window.apiBase + 'manage_item.php?action=get&id=' + itemId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const item = data.data;
                    editingItemId = itemId;
                    itemIdInput.value = itemId;
                    document.getElementById('title').value = item.title;
                    document.getElementById('description').value = item.description;
                    document.getElementById('agent_id').value = item.agent_id;
                    formTitle.textContent = '메뉴얼 항목 수정';
                    
                    // 연결된 컨텐츠 표시
                    uploadedContents = item.contents || [];
                    renderUploadedFiles();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('항목을 불러오는 중 오류가 발생했습니다.');
            });
    };

    // 항목 삭제
    window.deleteItem = function(itemId) {
        if (!confirm('정말 이 항목을 삭제하시겠습니까?')) {
            return;
        }

        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', itemId);

        fetch(window.apiBase + 'manage_item.php', {
            method: 'DELETE',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('항목이 삭제되었습니다.');
                    loadItemsList();
                    resetForm();
                } else {
                    alert('삭제 중 오류가 발생했습니다: ' + (data.error || '알 수 없는 오류'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('삭제 중 오류가 발생했습니다.');
            });
    };

    // 파일 업로드 처리
    function handleFileUpload(file) {
        const contentType = contentTypeSelect.value;
        if (!contentType || contentType === 'link') {
            alert('컨텐츠 타입을 먼저 선택하세요.');
            return;
        }

        const formData = new FormData();
        formData.append('content_type', contentType);
        formData.append('file', file);

        // 업로드 중 표시
        const uploadArea = fileUploadArea;
        uploadArea.innerHTML = '<p>업로드 중...</p>';

        fetch(window.apiBase + 'upload_content.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                uploadArea.innerHTML = '<p>파일을 드래그하거나 클릭하여 업로드</p>';
                
                if (data.success) {
                    uploadedContents.push({
                        id: data.content_id,
                        content_type: data.content_type,
                        file_path: data.file_name,
                        file_url: data.file_url,
                        mime_type: data.mime_type
                    });
                    renderUploadedFiles();
                    fileInput.value = '';
                } else {
                    alert('업로드 실패: ' + (data.error || '알 수 없는 오류'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                uploadArea.innerHTML = '<p>파일을 드래그하거나 클릭하여 업로드</p>';
                alert('업로드 중 오류가 발생했습니다.');
            });
    }

    // 외부 링크 업로드 처리
    function handleLinkUpload() {
        const url = document.getElementById('external_url').value.trim();
        if (!url) {
            alert('URL을 입력하세요.');
            return;
        }

        const formData = new FormData();
        formData.append('content_type', 'link');
        formData.append('external_url', url);

        fetch(window.apiBase + 'upload_content.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    uploadedContents.push({
                        id: data.content_id,
                        content_type: 'link',
                        external_url: data.external_url
                    });
                    renderUploadedFiles();
                    document.getElementById('external_url').value = '';
                } else {
                    alert('링크 저장 실패: ' + (data.error || '알 수 없는 오류'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('링크 저장 중 오류가 발생했습니다.');
            });
    }

    // 업로드된 파일 목록 렌더링
    function renderUploadedFiles() {
        if (uploadedContents.length === 0) {
            uploadedFilesDiv.innerHTML = '';
            return;
        }

        uploadedFilesDiv.innerHTML = uploadedContents.map((content, index) => {
            let displayText = '';
            if (content.content_type === 'link') {
                displayText = content.external_url;
            } else {
                displayText = content.file_path || content.file_url || '파일';
            }

            return `
                <div class="uploaded-file">
                    <span>${getContentTypeIcon(content.content_type)} ${displayText}</span>
                    <button onclick="removeContent(${index})" style="background: #ef4444; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">삭제</button>
                </div>
            `;
        }).join('');
    }

    // 컨텐츠 타입 아이콘
    function getContentTypeIcon(type) {
        const icons = {
            'image': '🖼️',
            'video': '🎥',
            'audio': '🎵',
            'link': '🔗'
        };
        return icons[type] || '📄';
    }

    // 컨텐츠 제거
    window.removeContent = function(index) {
        uploadedContents.splice(index, 1);
        renderUploadedFiles();
    };

    // 폼 제출 처리
    function handleFormSubmit(e) {
        e.preventDefault();

        const title = document.getElementById('title').value.trim();
        const agentId = document.getElementById('agent_id').value;
        const description = document.getElementById('description').value.trim();

        if (!title || !agentId) {
            alert('제목과 에이전트는 필수입니다.');
            return;
        }

        const formData = new FormData();
        const isEdit = editingItemId !== null;

        if (isEdit) {
            formData.append('action', 'update');
            formData.append('id', editingItemId);
        } else {
            formData.append('action', 'create');
        }

        formData.append('title', title);
        formData.append('agent_id', agentId);
        formData.append('description', description);
        formData.append('content_ids', JSON.stringify(uploadedContents.map(c => c.id)));

        const method = isEdit ? 'PUT' : 'POST';
        const url = window.apiBase + 'manage_item.php';

        fetch(url, {
            method: method,
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(isEdit ? '항목이 수정되었습니다.' : '항목이 추가되었습니다.');
                    loadItemsList();
                    resetForm();
                } else {
                    alert('저장 실패: ' + (data.error || '알 수 없는 오류'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('저장 중 오류가 발생했습니다.');
            });
    }

    // 폼 리셋
    function resetForm() {
        editingItemId = null;
        itemIdInput.value = '';
        itemForm.reset();
        uploadedContents = [];
        renderUploadedFiles();
        formTitle.textContent = '새 메뉴얼 항목 추가';
        linkInputGroup.classList.add('hidden');
        fileUploadArea.style.display = 'block';
    }

    // HTML 이스케이프
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // 페이지 로드 시 초기화
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();

