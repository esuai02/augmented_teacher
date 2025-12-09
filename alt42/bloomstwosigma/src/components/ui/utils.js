/**
 * UI Utilities
 * 공통 UI 유틸리티 함수들
 */

// 모달 닫기
function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

// 탭 전환
function switchTab(tabName) {
    // 탭 이름에서 '-tab' 제거
    const cleanTabName = tabName.replace('-tab', '');
    
    // 모든 탭 콘텐츠 숨기기
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.style.display = 'none';
        tab.classList.remove('active');
    });
    
    // 모든 탭 버튼 비활성화
    document.querySelectorAll('.nav-tab').forEach(tab => {
        tab.classList.remove('active', 'bg-purple-500', 'text-white');
        tab.classList.add('bg-white/10', 'text-gray-300');
    });
    
    // 선택된 탭 표시
    const targetTab = document.getElementById(cleanTabName + '-tab');
    if (targetTab) {
        targetTab.style.display = 'block';
        targetTab.classList.add('active');
    }
    
    // 해당 탭 버튼 활성화
    const targetBtn = document.querySelector(`[data-tab="${cleanTabName}"]`);
    if (targetBtn) {
        targetBtn.classList.remove('bg-white/10', 'text-gray-300');
        targetBtn.classList.add('active', 'bg-purple-500', 'text-white');
    }
    
    console.log(`탭 전환: ${cleanTabName}`);
}

// 페이지 로딩 시 탭 이벤트 리스너 등록
document.addEventListener('DOMContentLoaded', function() {
    // 탭 버튼 클릭 이벤트
    document.querySelectorAll('.nav-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            const tabName = this.getAttribute('data-tab');
            switchTab(tabName);
        });
    });
    
    // 새로운 탭 버튼 클릭 이벤트 - 사용 안함
    
    // 모달 외부 클릭 시 닫기
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
            }
        });
    });
});

// 테이블 데이터 모달 표시 (페이지네이션 지원)
let currentTableData = null;

async function showTableDataModal(tableName, page = 1, limit = 20) {
    const modal = document.getElementById('tablePreviewModal');
    const title = document.getElementById('previewTableTitle');
    const content = document.getElementById('previewTableContent');
    
    title.textContent = `${tableName} 테이블 데이터`;
    content.innerHTML = '<div class="text-center py-4"><div class="text-gray-400">데이터 로딩 중...</div></div>';
    
    modal.classList.remove('hidden');
    
    try {
        const offset = (page - 1) * limit;
        const response = await fetch('src/api/database_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=get_table_data&table_name=${tableName}&limit=${limit}&offset=${offset}`
        });
        
        const result = await response.json();
        
        if (result.error) {
            content.innerHTML = `<div class="text-red-400 text-center py-4">오류: ${result.error}</div>`;
            return;
        }
        
        currentTableData = result;
        renderTableDataContent(result);
        
    } catch (error) {
        console.error('테이블 데이터 로딩 실패:', error);
        content.innerHTML = '<div class="text-red-400 text-center py-4">데이터를 불러오는데 실패했습니다.</div>';
    }
}

// 테이블 데이터 내용 렌더링
function renderTableDataContent(tableData) {
    const content = document.getElementById('previewTableContent');
    
    if (!tableData.data || tableData.data.length === 0) {
        content.innerHTML = '<div class="text-gray-400 text-center py-4">데이터가 없습니다.</div>';
        return;
    }
    
    const { data, columns, current_page, total_pages, total_records, limit } = tableData;
    
    const tableHTML = `
        <div class="space-y-4">
            <!-- 테이블 정보 -->
            <div class="flex justify-between items-center text-sm text-gray-400">
                <span>전체 ${total_records}개 레코드</span>
                <span>페이지 ${current_page} / ${total_pages}</span>
            </div>
            
            <!-- 테이블 데이터 -->
            <div class="overflow-x-auto max-h-96">
                <table class="w-full text-sm">
                    <thead class="sticky top-0 bg-gray-800">
                        <tr class="border-b border-white/20">
                            ${columns.map(col => `<th class="text-left p-2 text-gray-300 bg-gray-800">${col}</th>`).join('')}
                        </tr>
                    </thead>
                    <tbody>
                        ${data.map((row, index) => `
                            <tr class="border-b border-white/10 hover:bg-white/5">
                                ${columns.map(col => `<td class="p-2 text-gray-400 max-w-xs truncate" title="${row[col] || ''}">${row[col] || ''}</td>`).join('')}
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
            
            <!-- 페이지네이션 -->
            <div class="flex justify-between items-center">
                <div class="flex space-x-2">
                    <button onclick="goToTablePage(1)" 
                            class="px-3 py-1 bg-gray-600 text-white rounded text-sm hover:bg-gray-500 disabled:opacity-50" 
                            ${current_page === 1 ? 'disabled' : ''}>
                        처음
                    </button>
                    <button onclick="goToTablePage(${current_page - 1})" 
                            class="px-3 py-1 bg-gray-600 text-white rounded text-sm hover:bg-gray-500 disabled:opacity-50"
                            ${current_page === 1 ? 'disabled' : ''}>
                        이전
                    </button>
                </div>
                
                <div class="flex space-x-1">
                    ${generatePageNumbers(current_page, total_pages)}
                </div>
                
                <div class="flex space-x-2">
                    <button onclick="goToTablePage(${current_page + 1})" 
                            class="px-3 py-1 bg-gray-600 text-white rounded text-sm hover:bg-gray-500 disabled:opacity-50"
                            ${current_page === total_pages ? 'disabled' : ''}>
                        다음
                    </button>
                    <button onclick="goToTablePage(${total_pages})" 
                            class="px-3 py-1 bg-gray-600 text-white rounded text-sm hover:bg-gray-500 disabled:opacity-50"
                            ${current_page === total_pages ? 'disabled' : ''}>
                        마지막
                    </button>
                </div>
            </div>
            
            <!-- 액션 버튼 -->
            <div class="flex justify-end space-x-3 pt-4 border-t border-white/20">
                <button onclick="addTableToExperiment()" 
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    실험에 추가
                </button>
                <button onclick="closeModal('tablePreviewModal')" 
                        class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                    닫기
                </button>
            </div>
        </div>
    `;
    
    content.innerHTML = tableHTML;
}

// 페이지 번호 생성
function generatePageNumbers(currentPage, totalPages) {
    const pages = [];
    const maxVisible = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
    let endPage = Math.min(totalPages, startPage + maxVisible - 1);
    
    if (endPage - startPage + 1 < maxVisible) {
        startPage = Math.max(1, endPage - maxVisible + 1);
    }
    
    for (let i = startPage; i <= endPage; i++) {
        const isActive = i === currentPage;
        pages.push(`
            <button onclick="goToTablePage(${i})" 
                    class="px-3 py-1 rounded text-sm ${isActive ? 'bg-blue-600 text-white' : 'bg-gray-600 text-white hover:bg-gray-500'}">
                ${i}
            </button>
        `);
    }
    
    return pages.join('');
}

// 테이블 페이지 이동
function goToTablePage(page) {
    if (selectedTable && currentTableData) {
        showTableDataModal(selectedTable, page, currentTableData.limit);
    }
}

// 테이블을 실험에 추가
function addTableToExperiment() {
    if (!selectedTable) {
        alert('테이블을 먼저 선택해주세요.');
        return;
    }
    
    // 실험 설명에 테이블 정보 추가
    const descriptionTextarea = document.querySelector('textarea[placeholder="실험에 대한 설명을 입력하세요..."]');
    if (descriptionTextarea) {
        const currentDescription = descriptionTextarea.value;
        const tableInfo = `\n\n[추가된 DB 테이블]\n- 테이블명: ${selectedTable}\n- 레코드 수: ${dbTables.find(t => t.name === selectedTable)?.records || 'Unknown'}\n- 추가일시: ${new Date().toLocaleString()}`;
        descriptionTextarea.value = currentDescription + tableInfo;
    }
    
    alert(`${selectedTable} 테이블이 실험에 추가되었습니다.`);
}

// 전역 함수 등록
window.closeModal = closeModal;
window.switchTab = switchTab;
window.showTableDataModal = showTableDataModal;
window.goToTablePage = goToTablePage;
window.addTableToExperiment = addTableToExperiment;