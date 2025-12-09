/**
 * Database Tracking Component JavaScript
 * 데이터베이스 연결 및 테이블 관리 기능
 */

// DB 관련 상태
let dbTables = [];
let selectedTable = null;
let selectedTableFields = [];
let fieldMeanings = {}; // 필드 의미 저장
let conditions = [];
let conditionCounter = 0;
let currentModalPage = 1;
let modalPagination = null;
let tableDescriptions = {}; // 테이블 설명 저장
let selectedTableForDescription = null; // 설명 입력을 위해 선택된 테이블

// DB 테이블 모달 표시
async function showDBTablesModal(page = 1) {
    const modal = document.getElementById('dbTablesModal');
    const tablesList = document.getElementById('modalDbTablesList');
    
    currentModalPage = page;
    modal.classList.remove('hidden');
    tablesList.innerHTML = '<div class="text-gray-400 text-center py-4">테이블 목록을 로딩중...</div>';
    
    // 모든 테이블 설명 로드
    await loadAllTableDescriptions();
    
    await loadDBTablesPage(page);
}

// showSelectedTableInfo 호출 후 테이블 설명도 로드
async function loadTableDescriptionForMainPage() {
    if (!selectedTable) return;
    
    // 테이블 설명이 이미 로드되어 있으면 UI만 업데이트
    const tableDesc = tableDescriptions[selectedTable];
    const typeSelect = document.getElementById('mainTableType');
    const descInput = document.getElementById('mainTableDescription');
    
    if (typeSelect && descInput) {
        if (tableDesc) {
            typeSelect.value = tableDesc.type || '사용자 정보';
            descInput.value = tableDesc.description || '';
        } else {
            typeSelect.value = '사용자 정보';
            descInput.value = '';
        }
    }
}

// DB 테이블 페이지 로딩
async function loadDBTablesPage(page = 1, search = '') {
    const tablesList = document.getElementById('modalDbTablesList');
    
    try {
        const response = await fetch('src/api/database_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=get_db_tables&page=${page}&limit=50&search=${encodeURIComponent(search)}`
        });
        
        const result = await response.json();
        
        if (result.error) {
            tablesList.innerHTML = `<div class="text-red-400 text-center py-4">오류: ${result.error}</div>`;
            return;
        }
        
        dbTables = result.tables || [];
        modalPagination = result.pagination || null;
        currentModalPage = page;
        
        renderModalTablesList();
        renderModalPagination();
        
    } catch (error) {
        console.error('DB 테이블 목록 조회 실패:', error);
        tablesList.innerHTML = `<div class="text-red-400 text-center py-4">오류: ${error.message}</div>`;
    }
}

// 모달 테이블 목록 렌더링
function renderModalTablesList() {
    const tablesList = document.getElementById('modalDbTablesList');
    const tablesInfo = document.getElementById('modalTablesInfo');
    
    if (dbTables.length === 0) {
        tablesList.innerHTML = '<div class="text-gray-400 text-center py-4">테이블이 없습니다.</div>';
        tablesInfo.textContent = '테이블 없음';
        return;
    }
    
    tablesList.innerHTML = dbTables.map(table => {
        const tableDesc = tableDescriptions[table.name];
        const currentType = tableDesc ? tableDesc.type : '사용자 정보';
        const currentDescription = tableDesc ? tableDesc.description : '';
        
        return `
        <div class="bg-white/5 border border-white/20 rounded-lg p-4 hover:bg-white/10 transition-all cursor-pointer"
             onclick="selectTableFromModal('${table.name}')">
            <div class="flex justify-between items-start mb-2">
                <h4 class="font-medium text-white">${table.name}</h4>
                <div class="flex items-center gap-2">
                    ${tableDesc ? `<span class="text-xs px-2 py-1 bg-purple-500 text-white rounded font-medium">${currentType}</span>` : ''}
                    <span class="text-xs text-gray-400">${table.records}개</span>
                </div>
            </div>
            
            ${currentDescription ? `
                <div class="text-sm text-blue-300 mb-2">
                    <i class="fas fa-info-circle mr-1"></i>
                    ${currentDescription}
                </div>
            ` : ''}
            
            <div class="text-xs text-gray-400 space-y-1">
                <div>엔진: ${table.engine}</div>
                <div>크기: ${table.size}</div>
                ${table.last_update ? `<div>수정: ${table.last_update}</div>` : ''}
            </div>
        </div>
    `}).join('');
    
    // 페이지네이션 정보 표시
    if (modalPagination) {
        const { current_page, total_pages, total_tables, limit } = modalPagination;
        const startItem = ((current_page - 1) * limit) + 1;
        const endItem = Math.min(current_page * limit, total_tables);
        tablesInfo.textContent = `${startItem}-${endItem} / 총 ${total_tables}개 테이블 (페이지 ${current_page}/${total_pages})`;
    } else {
        tablesInfo.textContent = `총 ${dbTables.length}개 테이블`;
    }
}

// 모달 페이지네이션 렌더링
function renderModalPagination() {
    const paginationContainer = document.getElementById('modalPagination');
    
    if (!modalPagination || modalPagination.total_pages <= 1) {
        paginationContainer.innerHTML = '';
        return;
    }
    
    const { current_page, total_pages } = modalPagination;
    const maxVisible = 5;
    let startPage = Math.max(1, current_page - Math.floor(maxVisible / 2));
    let endPage = Math.min(total_pages, startPage + maxVisible - 1);
    
    if (endPage - startPage + 1 < maxVisible) {
        startPage = Math.max(1, endPage - maxVisible + 1);
    }
    
    let paginationHTML = '';
    
    // 처음/이전 버튼
    paginationHTML += `
        <button onclick="goToModalPage(1)" 
                class="px-2 py-1 bg-gray-600 text-white rounded text-xs hover:bg-gray-500 disabled:opacity-50"
                ${current_page === 1 ? 'disabled' : ''}>
            처음
        </button>
        <button onclick="goToModalPage(${current_page - 1})" 
                class="px-2 py-1 bg-gray-600 text-white rounded text-xs hover:bg-gray-500 disabled:opacity-50"
                ${current_page === 1 ? 'disabled' : ''}>
            이전
        </button>
    `;
    
    // 페이지 번호
    for (let i = startPage; i <= endPage; i++) {
        const isActive = i === current_page;
        paginationHTML += `
            <button onclick="goToModalPage(${i})" 
                    class="px-2 py-1 rounded text-xs ${isActive ? 'bg-blue-600 text-white' : 'bg-gray-600 text-white hover:bg-gray-500'}">
                ${i}
            </button>
        `;
    }
    
    // 다음/마지막 버튼
    paginationHTML += `
        <button onclick="goToModalPage(${current_page + 1})" 
                class="px-2 py-1 bg-gray-600 text-white rounded text-xs hover:bg-gray-500 disabled:opacity-50"
                ${current_page === total_pages ? 'disabled' : ''}>
            다음
        </button>
        <button onclick="goToModalPage(${total_pages})" 
                class="px-2 py-1 bg-gray-600 text-white rounded text-xs hover:bg-gray-500 disabled:opacity-50"
                ${current_page === total_pages ? 'disabled' : ''}>
            마지막
        </button>
    `;
    
    paginationContainer.innerHTML = paginationHTML;
}

// 모달 페이지 이동
function goToModalPage(page) {
    if (page < 1 || (modalPagination && page > modalPagination.total_pages)) {
        return;
    }
    
    const searchTerm = document.getElementById('modalTableSearchInput').value;
    loadDBTablesPage(page, searchTerm);
}

// 모달에서 테이블 선택
async function selectTableFromModal(tableName) {
    selectedTable = tableName;
    
    // 상단 편집 섹션에 테이블 정보 로드
    loadTableToEditSection(tableName);
    
    // 모달 닫기
    closeModal('dbTablesModal');
    
    // 선택된 테이블 정보 표시
    showSelectedTableInfo(tableName);
    
    // 필드 정보 가져오기
    await loadTableFields(tableName);
}

// 상단 편집 섹션에 테이블 정보 로드
function loadTableToEditSection(tableName) {
    const editSection = document.getElementById('selectedTableEditSection');
    const nameDisplay = document.getElementById('selectedTableNameDisplay');
    const typeSelect = document.getElementById('editTableType');
    const descInput = document.getElementById('editTableDescription');
    
    // 선택된 테이블명 표시
    nameDisplay.textContent = tableName;
    selectedTableForDescription = tableName;
    
    // 기존 설명이 있으면 로드
    const tableDesc = tableDescriptions[tableName];
    if (tableDesc) {
        typeSelect.value = tableDesc.type || '사용자 정보';
        descInput.value = tableDesc.description || '';
    } else {
        typeSelect.value = '사용자 정보';
        descInput.value = '';
    }
    
    // 편집 섹션 표시
    editSection.style.display = 'block';
}

// 선택된 테이블 정보 표시
function showSelectedTableInfo(tableName) {
    const selectedInfo = document.getElementById('selectedTableInfo');
    const tableNameEl = document.getElementById('selectedTableName');
    const tableDetailsEl = document.getElementById('selectedTableDetails');
    const typeSelect = document.getElementById('mainTableType');
    const descInput = document.getElementById('mainTableDescription');
    const initialState = document.getElementById('initialState');
    
    const tableInfo = dbTables.find(t => t.name === tableName);
    
    tableNameEl.textContent = tableName;
    tableDetailsEl.textContent = tableInfo ? `${tableInfo.records}개 레코드, ${tableInfo.size}` : '';
    
    // 테이블 설명 정보 로드
    const tableDesc = tableDescriptions[tableName];
    if (tableDesc) {
        typeSelect.value = tableDesc.type || '사용자 정보';
        descInput.value = tableDesc.description || '';
    } else {
        typeSelect.value = '사용자 정보';
        descInput.value = '';
    }
    
    selectedInfo.style.display = 'block';
    initialState.style.display = 'none';
}

// 테이블 필드 로딩
async function loadTableFields(tableName) {
    try {
        // 필드 정보 가져오기
        const fieldsResponse = await fetch('src/api/database_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=get_table_fields&table_name=${tableName}`
        });
        
        const fieldsResult = await fieldsResponse.json();
        
        if (fieldsResult.error) {
            alert('필드 정보 로딩 실패: ' + fieldsResult.error);
            return;
        }
        
        selectedTableFields = fieldsResult.fields || [];
        
        // 필드의 comment를 fieldMeanings에 로드
        fieldMeanings = {};
        selectedTableFields.forEach(field => {
            if (field.comment) {
                fieldMeanings[field.name] = field.comment;
            }
        });
        
        renderFieldsList();
        showFieldsSection();
        
    } catch (error) {
        console.error('필드 정보 로딩 실패:', error);
        alert('필드 정보를 불러오는데 실패했습니다.');
    }
}

// 필드 목록 렌더링
function renderFieldsList() {
    const fieldsList = document.getElementById('fieldsList');
    
    fieldsList.innerHTML = selectedTableFields.map(field => `
        <div class="bg-white/5 border border-white/20 rounded-lg p-3 mb-2">
            <div class="flex items-center gap-3">
                <!-- 필드 정보 -->
                <div class="flex-shrink-0" style="width: 200px;">
                    <div class="flex justify-between items-center mb-1">
                        <span class="font-medium text-white text-sm">${field.name}</span>
                        <span class="text-xs text-gray-400">${field.type}</span>
                    </div>
                    ${field.key ? `<div class="text-xs text-yellow-400">${field.key}</div>` : ''}
                    ${field.null === 'NO' ? `<div class="text-xs text-red-400">NOT NULL</div>` : ''}
                </div>
                
                <!-- 의미 입력 (가득 채우기) -->
                <div class="flex-1">
                    <input type="text" 
                           class="field-meaning-input w-full px-3 py-2 bg-gray-700 border border-white/20 rounded text-sm text-white placeholder-gray-400"
                           placeholder="필드 의미 입력..."
                           value="${fieldMeanings[field.name] || ''}"
                           onchange="updateFieldMeaning('${field.name}', this.value)"
                           onclick="event.stopPropagation()">
                </div>
                
                <!-- 조건 추가 버튼 (텍스트 박스 우측에 붙이기) -->
                <div class="flex-shrink-0">
                    <button onclick="addFieldCondition('${field.name}', '${field.type}')"
                            class="add-condition-square-btn w-8 h-8 bg-blue-500 hover:bg-blue-600 text-white flex items-center justify-center rounded"
                            title="조건 추가">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

// 필드 섹션 표시
function showFieldsSection() {
    document.getElementById('fieldsSection').style.display = 'block';
}

// 필드 조건 추가
function addFieldCondition(fieldName, fieldType) {
    const conditionId = `condition_${++conditionCounter}`;
    
    const condition = {
        id: conditionId,
        field: fieldName,
        type: fieldType,
        operator: '=',
        value: '',
        connector: 'AND'
    };
    
    conditions.push(condition);
    renderConditionsList();
    showConditionsSection();
    updateSQLPreview();
}

// 조건 추가 (빈 조건)
function addCondition() {
    if (selectedTableFields.length === 0) {
        alert('먼저 테이블을 선택해주세요.');
        return;
    }
    
    const conditionId = `condition_${++conditionCounter}`;
    
    const condition = {
        id: conditionId,
        field: selectedTableFields[0].name,
        type: selectedTableFields[0].type,
        operator: '=',
        value: '',
        connector: 'AND'
    };
    
    conditions.push(condition);
    renderConditionsList();
    showConditionsSection();
    updateSQLPreview();
}

// 조건 목록 렌더링
function renderConditionsList() {
    const conditionsList = document.getElementById('conditionsList');
    
    conditionsList.innerHTML = conditions.map((condition, index) => `
        <div class="bg-white/5 border border-white/20 rounded-lg p-3">
            <div class="grid grid-cols-12 gap-2 items-center">
                ${index > 0 ? `
                    <select class="col-span-2 px-2 py-1 bg-gray-700 border border-white/20 rounded text-xs"
                            onchange="updateCondition('${condition.id}', 'connector', this.value)">
                        <option value="AND" ${condition.connector === 'AND' ? 'selected' : ''}>AND</option>
                        <option value="OR" ${condition.connector === 'OR' ? 'selected' : ''}>OR</option>
                    </select>
                ` : '<div class="col-span-2"></div>'}
                
                <select class="col-span-3 px-2 py-1 bg-gray-700 border border-white/20 rounded text-xs"
                        onchange="updateCondition('${condition.id}', 'field', this.value)">
                    ${selectedTableFields.map(field => 
                        `<option value="${field.name}" ${condition.field === field.name ? 'selected' : ''}>${field.name}</option>`
                    ).join('')}
                </select>
                
                <select class="col-span-2 px-2 py-1 bg-gray-700 border border-white/20 rounded text-xs"
                        onchange="updateCondition('${condition.id}', 'operator', this.value)">
                    <option value="=" ${condition.operator === '=' ? 'selected' : ''}>=</option>
                    <option value="!=" ${condition.operator === '!=' ? 'selected' : ''}>!=</option>
                    <option value=">" ${condition.operator === '>' ? 'selected' : ''}>&gt;</option>
                    <option value="<" ${condition.operator === '<' ? 'selected' : ''}>&lt;</option>
                    <option value=">=" ${condition.operator === '>=' ? 'selected' : ''}>&gt;=</option>
                    <option value="<=" ${condition.operator === '<=' ? 'selected' : ''}>&lt;=</option>
                    <option value="LIKE" ${condition.operator === 'LIKE' ? 'selected' : ''}>LIKE</option>
                    <option value="NOT LIKE" ${condition.operator === 'NOT LIKE' ? 'selected' : ''}>NOT LIKE</option>
                </select>
                
                <input type="text" 
                       class="col-span-3 px-2 py-1 bg-gray-700 border border-white/20 rounded text-xs condition-value-input"
                       placeholder="값 입력..."
                       value="${condition.value}"
                       onchange="updateCondition('${condition.id}', 'value', this.value)">
                
                <button onclick="removeCondition('${condition.id}')"
                        class="col-span-1 text-red-400 hover:text-red-300 transition-colors p-1 remove-condition-btn"
                        title="조건 제거">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `).join('');
}

// 조건 업데이트
function updateCondition(conditionId, property, value) {
    const condition = conditions.find(c => c.id === conditionId);
    if (condition) {
        condition[property] = value;
        updateSQLPreview();
    }
}

// 조건 제거
function removeCondition(conditionId) {
    conditions = conditions.filter(c => c.id !== conditionId);
    renderConditionsList();
    updateSQLPreview();
    
    if (conditions.length === 0) {
        document.getElementById('conditionsSection').style.display = 'none';
    }
}

// 조건 섹션 표시
function showConditionsSection() {
    document.getElementById('conditionsSection').style.display = 'block';
}

// SQL 미리보기 업데이트
function updateSQLPreview() {
    const sqlPreview = document.getElementById('sqlPreview');
    
    if (!selectedTable || conditions.length === 0) {
        sqlPreview.textContent = `SELECT * FROM ${selectedTable || 'table_name'}`;
        return;
    }
    
    let whereClause = conditions.map((condition, index) => {
        let clause = '';
        if (index > 0) {
            clause += ` ${condition.connector} `;
        }
        
        let value = condition.value;
        if (condition.operator === 'LIKE' || condition.operator === 'NOT LIKE') {
            value = `'%${value}%'`;
        } else if (isNaN(value) && value !== '') {
            value = `'${value}'`;
        }
        
        clause += `${condition.field} ${condition.operator} ${value || '?'}`;
        return clause;
    }).join('');
    
    sqlPreview.textContent = `SELECT * FROM ${selectedTable} WHERE ${whereClause}`;
}

// 필드 의미 업데이트
async function updateFieldMeaning(fieldName, meaning) {
    fieldMeanings[fieldName] = meaning;
    
    // DB에 자동 저장
    await saveFieldDescription(fieldName, meaning);
    
    // 의미가 입력된 필드들을 실험 설명에 자동 추가
    updateExperimentDescription();
}

// 실험 설명 업데이트 (필드 의미 포함)
function updateExperimentDescription() {
    const descriptionTextarea = document.querySelector('#experiment-description');
    if (!descriptionTextarea || !selectedTable) return;
    
    const currentDescription = descriptionTextarea.value;
    const tableSection = `[${selectedTable} 테이블 필드 의미]`;
    
    // 기존 테이블 섹션 제거
    const lines = currentDescription.split('\n');
    const filteredLines = [];
    let inTableSection = false;
    
    for (const line of lines) {
        if (line.includes('[') && line.includes('테이블 필드 의미]')) {
            inTableSection = true;
            continue;
        }
        if (inTableSection && line.trim() === '') {
            inTableSection = false;
            continue;
        }
        if (!inTableSection) {
            filteredLines.push(line);
        }
    }
    
    // 의미가 입력된 필드들만 추가
    const meaningfulFields = Object.entries(fieldMeanings)
        .filter(([field, meaning]) => meaning && meaning.trim() !== '')
        .map(([field, meaning]) => `- ${field}: ${meaning}`);
    
    if (meaningfulFields.length > 0) {
        const newDescription = filteredLines.join('\n') + '\n\n' + tableSection + '\n' + meaningfulFields.join('\n');
        descriptionTextarea.value = newDescription;
    } else {
        descriptionTextarea.value = filteredLines.join('\n');
    }
}

// 저장된 필드 설명 로딩 - MySQL COLUMN COMMENT에서 직접 가져오므로 더이상 필요없음
/*
async function loadFieldDescriptions(tableName) {
    try {
        console.log(`필드 설명 로딩 시도: ${tableName}`);
        
        const response = await fetch('src/api/database_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=get_field_descriptions&table_name=${encodeURIComponent(tableName)}`
        });
        
        const result = await response.json();
        
        if (result.success && result.descriptions) {
            console.log(`필드 설명 로딩 성공: ${result.count || 0}개 필드`);
            
            // 저장된 설명들을 fieldMeanings에 로드
            fieldMeanings = {};
            Object.keys(result.descriptions).forEach(fieldName => {
                fieldMeanings[fieldName] = result.descriptions[fieldName].description || '';
                console.log(`로드된 필드 설명: ${fieldName} = "${fieldMeanings[fieldName]}"`);
            });
        } else {
            console.log('필드 설명 로딩 실패 또는 데이터 없음:', result.error || '데이터 없음');
            fieldMeanings = {};
        }
        
    } catch (error) {
        console.error('필드 설명 로딩 실패:', error);
        fieldMeanings = {};
    }
}
*/

// 필드 설명 저장
async function saveFieldDescription(fieldName, description) {
    if (!selectedTable || !fieldName) {
        console.log('필드 설명 저장 실패: 테이블 또는 필드명이 비어있음');
        return;
    }
    
    try {
        console.log(`필드 설명 저장 시도: ${selectedTable}.${fieldName} = "${description}"`);
        
        const response = await fetch('src/api/database_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=save_field_description&table_name=${encodeURIComponent(selectedTable)}&field_name=${encodeURIComponent(fieldName)}&description=${encodeURIComponent(description)}`
        });
        
        const result = await response.json();
        
        if (result.error) {
            console.error('필드 설명 저장 실패:', result.error);
        } else if (result.success) {
            console.log('필드 설명 저장 성공:', result.message);
            console.log('저장된 데이터:', result);
        }
        
    } catch (error) {
        console.error('필드 설명 저장 실패:', error);
    }
}

// 모든 필드 설명 일괄 저장
async function saveAllFieldDescriptions() {
    if (!selectedTable || Object.keys(fieldMeanings).length === 0) return;
    
    try {
        const response = await fetch('src/api/database_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=save_multiple_field_descriptions&table_name=${selectedTable}&field_descriptions=${encodeURIComponent(JSON.stringify(fieldMeanings))}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            console.log('모든 필드 설명 저장 완료:', result.message);
        } else {
            console.error('일괄 저장 실패:', result.error);
        }
        
    } catch (error) {
        console.error('일괄 저장 실패:', error);
    }
}

// 선택된 테이블 초기화
function clearSelectedTable() {
    selectedTable = null;
    selectedTableFields = [];
    fieldMeanings = {};
    conditions = [];
    
    document.getElementById('selectedTableInfo').style.display = 'none';
    document.getElementById('fieldsSection').style.display = 'none';
    document.getElementById('conditionsSection').style.display = 'none';
    document.getElementById('initialState').style.display = 'block';
}

// 쿼리 실행
async function executeQuery() {
    if (!selectedTable || conditions.length === 0) {
        alert('테이블과 조건을 선택해주세요.');
        return;
    }
    
    // SQL 쿼리를 실행하는 대신 결과 미리보기 모달 표시
    showTableDataModal(selectedTable, 1, 20);
}

// 쿼리 저장
async function saveQuery() {
    if (!selectedTable || conditions.length === 0) {
        alert('테이블과 조건을 선택해주세요.');
        return;
    }
    
    const queryData = {
        table: selectedTable,
        conditions: conditions,
        fieldMeanings: fieldMeanings,
        sql: document.getElementById('sqlPreview').textContent,
        created_at: new Date().toLocaleString()
    };
    
    // 실험 설명에 쿼리 정보 추가
    const descriptionTextarea = document.querySelector('#experiment-description');
    if (descriptionTextarea) {
        const currentDescription = descriptionTextarea.value;
        let queryInfo = `\n\n[저장된 DB 쿼리]\n- 테이블: ${selectedTable}\n- 조건: ${conditions.length}개\n- SQL: ${queryData.sql}\n- 저장일시: ${queryData.created_at}`;
        
        // 필드 의미 정보 추가
        const meaningfulFields = Object.entries(fieldMeanings)
            .filter(([field, meaning]) => meaning && meaning.trim() !== '')
            .map(([field, meaning]) => `  * ${field}: ${meaning}`);
        
        if (meaningfulFields.length > 0) {
            queryInfo += `\n- 필드 의미:\n${meaningfulFields.join('\n')}`;
        }
        
        descriptionTextarea.value = currentDescription + queryInfo;
    }
    
    // 실험 관리자에 DB 연결 추가
    if (window.experimentManager && window.experimentManager.experimentId) {
        try {
            await window.experimentManager.addDatabaseToExperiment(selectedTable, conditions);
            console.log('DB 연결이 실험에 추가되었습니다.');
        } catch (error) {
            console.error('DB 연결 추가 실패:', error);
        }
    }
    
    alert('쿼리가 실험에 저장되었습니다.');
}

// 모달 테이블 검색
function searchModalTables() {
    const searchTerm = document.getElementById('modalTableSearchInput').value;
    
    // 검색어가 변경되면 첫 번째 페이지로 이동하며 검색
    loadDBTablesPage(1, searchTerm);
}

// DB 정보 보기
async function showDBInfo() {
    try {
        const response = await fetch('src/api/database_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_db_stats'
        });
        
        const result = await response.json();
        
        if (result.error) {
            alert('DB 정보 조회 오류: ' + result.error);
            return;
        }
        
        showDBInfoModal(result);
        
    } catch (error) {
        console.error('DB 정보 조회 실패:', error);
        alert('DB 정보를 불러오는데 실패했습니다.');
    }
}

// DB 정보 모달 표시
function showDBInfoModal(dbInfo) {
    const modal = document.getElementById('dbInfoModal');
    const content = document.getElementById('dbInfoContent');
    
    const infoHTML = `
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-white/5 p-3 rounded">
                    <div class="text-sm text-gray-400">데이터베이스</div>
                    <div class="text-lg font-medium">${dbInfo.database_name || 'mathking'}</div>
                </div>
                <div class="bg-white/5 p-3 rounded">
                    <div class="text-sm text-gray-400">테이블 수</div>
                    <div class="text-lg font-medium">${dbInfo.table_count || 0}개</div>
                </div>
                <div class="bg-white/5 p-3 rounded">
                    <div class="text-sm text-gray-400">전체 레코드</div>
                    <div class="text-lg font-medium">${dbInfo.total_records || 0}개</div>
                </div>
                <div class="bg-white/5 p-3 rounded">
                    <div class="text-sm text-gray-400">DB 크기</div>
                    <div class="text-lg font-medium">${dbInfo.total_size || 'Unknown'}</div>
                </div>
            </div>
            ${dbInfo.version ? `
                <div class="bg-white/5 p-3 rounded">
                    <div class="text-sm text-gray-400">MySQL 버전</div>
                    <div class="text-lg font-medium">${dbInfo.version}</div>
                </div>
            ` : ''}
        </div>
    `;
    
    content.innerHTML = infoHTML;
    modal.classList.remove('hidden');
}

// 모든 테이블 설명 로드
async function loadAllTableDescriptions() {
    try {
        const response = await fetch('src/api/database_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_all_table_descriptions'
        });
        
        const result = await response.json();
        
        if (result.success && result.descriptions) {
            tableDescriptions = result.descriptions;
            console.log(`테이블 설명 로드 완료: ${result.count || 0}개 테이블`);
        } else {
            tableDescriptions = {};
        }
        
    } catch (error) {
        console.error('테이블 설명 로드 실패:', error);
        tableDescriptions = {};
    }
}

// 설명이 있는 테이블 목록 렌더링
function renderDescribedTablesList() {
    const describedTablesContent = document.getElementById('describedTablesContent');
    
    if (!describedTablesContent) return;
    
    // 설명이 있는 테이블만 필터링
    const describedTables = Object.entries(tableDescriptions)
        .filter(([tableName, tableDesc]) => 
            tableDesc && tableDesc.description && tableDesc.description.trim() !== '')
        .map(([tableName, tableDesc]) => ({
            name: tableName,
            type: tableDesc.type,
            description: tableDesc.description
        }))
        .sort((a, b) => a.type.localeCompare(b.type));
    
    if (describedTables.length === 0) {
        describedTablesContent.innerHTML = `
            <div class="text-gray-500 text-sm text-center py-4">
                아직 설명이 등록된 테이블이 없습니다.
            </div>
        `;
        return;
    }
    
    describedTablesContent.innerHTML = describedTables.map(table => `
        <div class="bg-white/5 border border-white/20 rounded-lg p-3 hover:bg-white/10 transition-all cursor-pointer"
             onclick="selectDescribedTable('${table.name}')">
            <div class="flex justify-between items-start mb-2">
                <h5 class="font-medium text-white text-sm">${table.name}</h5>
                <span class="text-xs px-2 py-1 bg-purple-500 text-white rounded font-medium">${table.type}</span>
            </div>
            <div class="text-sm text-blue-300">
                <i class="fas fa-info-circle mr-1"></i>
                ${table.description}
            </div>
        </div>
    `).join('');
}

// 설명이 있는 테이블에서 선택
async function selectDescribedTable(tableName) {
    // 선택된 테이블로 설정
    selectedTable = tableName;
    
    // 선택된 테이블 정보 표시
    showSelectedTableInfo(tableName);
    
    // 필드 정보 가져오기
    await loadTableFields(tableName);
}

// 페이지 로드 시 설명이 있는 테이블 목록 로드
async function loadInitialDescribedTables() {
    try {
        await loadAllTableDescriptions();
        renderDescribedTablesList();
    } catch (error) {
        console.error('초기 테이블 목록 로드 실패:', error);
    }
}

// 테이블 설명 편집을 위해 선택
function selectTableForDescription(tableName) {
    selectedTableForDescription = tableName;
    
    // UI 업데이트
    const descInput = document.getElementById('tableDescriptionText');
    const typeSelect = document.getElementById('tableDescriptionType');
    const infoDiv = document.getElementById('selectedTableForDescription');
    
    // 기존 설명이 있으면 로드
    const existingDesc = tableDescriptions[tableName];
    if (existingDesc) {
        typeSelect.value = existingDesc.type || 'A';
        descInput.value = existingDesc.description || '';
    } else {
        typeSelect.value = 'A';
        descInput.value = '';
    }
    
    descInput.disabled = false;
    infoDiv.innerHTML = `<i class="fas fa-table mr-1"></i> 선택된 테이블: <strong>${tableName}</strong>`;
}

// 테이블 설명 업데이트 (메인 페이지에서 호출)
async function updateTableDescriptionFromMain() {
    if (!selectedTable) return;
    
    const type = document.getElementById('mainTableType').value;
    const description = document.getElementById('mainTableDescription').value;
    
    try {
        const response = await fetch('src/api/database_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=save_table_description&table_name=${encodeURIComponent(selectedTable)}&type=${type}&description=${encodeURIComponent(description)}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            // 로컬 상태 업데이트
            tableDescriptions[selectedTable] = {
                type: type,
                description: description,
                timemodified: result.timestamp
            };
            
            console.log('테이블 설명 저장 완료:', selectedTable);
            
            // 초기 목록 업데이트
            renderDescribedTablesList();
            
            // 저장 성공 시각적 피드백
            const descInput = document.getElementById('mainTableDescription');
            if (descInput) {
                descInput.classList.add('ring-2', 'ring-green-500');
                setTimeout(() => {
                    descInput.classList.remove('ring-2', 'ring-green-500');
                }, 1000);
            }
        } else {
            console.error('테이블 설명 저장 실패:', result.error);
        }
        
    } catch (error) {
        console.error('테이블 설명 저장 실패:', error);
    }
}

// 테이블 설명 업데이트 (편집 섹션에서 호출)
async function updateTableDescriptionFromEdit() {
    if (!selectedTableForDescription) return;
    
    const type = document.getElementById('editTableType').value;
    const description = document.getElementById('editTableDescription').value;
    
    try {
        const response = await fetch('src/api/database_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=save_table_description&table_name=${encodeURIComponent(selectedTableForDescription)}&type=${type}&description=${encodeURIComponent(description)}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            // 로컬 상태 업데이트
            tableDescriptions[selectedTableForDescription] = {
                type: type,
                description: description,
                timemodified: result.timestamp
            };
            
            // 테이블 목록 다시 렌더링하여 변경사항 반영
            renderModalTablesList();
            
            // 초기 목록 업데이트
            renderDescribedTablesList();
            
            console.log('테이블 설명 저장 완료:', selectedTableForDescription);
            
            // 저장 성공 시각적 피드백
            const descInput = document.getElementById('editTableDescription');
            if (descInput) {
                descInput.classList.add('ring-2', 'ring-green-500');
                setTimeout(() => {
                    descInput.classList.remove('ring-2', 'ring-green-500');
                }, 1000);
            }
        } else {
            console.error('테이블 설명 저장 실패:', result.error);
        }
        
    } catch (error) {
        console.error('테이블 설명 저장 실패:', error);
    }
}

// 테이블 설명 업데이트 (개별 카드에서 호출) - 더이상 사용 안함
async function updateTableDescriptionFromCard(tableName, type, description) {
    try {
        const response = await fetch('src/api/database_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=save_table_description&table_name=${encodeURIComponent(tableName)}&type=${type}&description=${encodeURIComponent(description)}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            // 로컬 상태 업데이트
            tableDescriptions[tableName] = {
                type: type,
                description: description,
                timemodified: result.timestamp
            };
            
            console.log('테이블 설명 저장 완료:', tableName);
            
            // 저장 성공 시각적 피드백 (옵션)
            const textarea = document.getElementById(`desc_${tableName}`);
            if (textarea) {
                textarea.classList.add('ring-2', 'ring-green-500');
                setTimeout(() => {
                    textarea.classList.remove('ring-2', 'ring-green-500');
                }, 1000);
            }
        } else {
            console.error('테이블 설명 저장 실패:', result.error);
        }
        
    } catch (error) {
        console.error('테이블 설명 저장 실패:', error);
    }
}

// 기존 함수는 유지 (호환성을 위해)
async function updateSelectedTableDescription() {
    if (!selectedTableForDescription) return;
    
    const type = document.getElementById('tableDescriptionType').value;
    const description = document.getElementById('tableDescriptionText').value;
    
    try {
        const response = await fetch('src/api/database_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=save_table_description&table_name=${encodeURIComponent(selectedTableForDescription)}&type=${type}&description=${encodeURIComponent(description)}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            // 로컬 상태 업데이트
            tableDescriptions[selectedTableForDescription] = {
                type: type,
                description: description,
                timemodified: result.timestamp
            };
            
            // 테이블 목록 다시 렌더링
            renderModalTablesList();
            
            console.log('테이블 설명 저장 완료:', selectedTableForDescription);
        } else {
            console.error('테이블 설명 저장 실패:', result.error);
        }
        
    } catch (error) {
        console.error('테이블 설명 저장 실패:', error);
    }
}

// 전역 함수 등록
window.showDBTablesModal = showDBTablesModal;
window.loadDBTablesPage = loadDBTablesPage;
window.goToModalPage = goToModalPage;
window.selectTableFromModal = selectTableFromModal;
window.clearSelectedTable = clearSelectedTable;
window.updateFieldMeaning = updateFieldMeaning;
window.saveFieldDescription = saveFieldDescription;
window.saveAllFieldDescriptions = saveAllFieldDescriptions;
window.addFieldCondition = addFieldCondition;
window.addCondition = addCondition;
window.updateCondition = updateCondition;
window.removeCondition = removeCondition;
window.executeQuery = executeQuery;
window.saveQuery = saveQuery;
window.searchModalTables = searchModalTables;
window.showDBInfo = showDBInfo;
window.selectTableForDescription = selectTableForDescription;
window.updateSelectedTableDescription = updateSelectedTableDescription;
window.updateTableDescriptionFromCard = updateTableDescriptionFromCard;
window.updateTableDescriptionFromEdit = updateTableDescriptionFromEdit;
window.updateTableDescriptionFromMain = updateTableDescriptionFromMain;
window.selectDescribedTable = selectDescribedTable;
window.loadInitialDescribedTables = loadInitialDescribedTables;