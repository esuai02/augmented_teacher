/**
 * 플러그인 필터 시스템 통합 코드
 * Integration code for adding plugin filter system to main interface
 */

// 메인 인터페이스에 필터 추가
function integratePluginFilter() {
    // 필터 버튼을 메뉴 인터페이스에 추가
    const addFilterButton = () => {
        const menuInterface = document.querySelector('.menu-interface');
        if (!menuInterface) return;
        
        // 필터 토글 버튼 추가
        const filterToggle = document.createElement('button');
        filterToggle.className = 'filter-toggle-btn';
        filterToggle.innerHTML = `
            <span class="icon">🔍</span>
            <span class="text">플러그인 필터</span>
            <span class="badge" id="filterBadge" style="display: none;">0</span>
        `;
        filterToggle.onclick = toggleFilterPanel;
        
        // 메뉴 헤더에 버튼 추가
        const menuHeader = menuInterface.querySelector('h2');
        if (menuHeader) {
            menuHeader.parentElement.style.display = 'flex';
            menuHeader.parentElement.style.justifyContent = 'space-between';
            menuHeader.parentElement.style.alignItems = 'center';
            menuHeader.parentElement.appendChild(filterToggle);
        }
    };
    
    // 필터 패널 생성
    const createFilterPanel = () => {
        const filterPanel = document.createElement('div');
        filterPanel.id = 'pluginFilterPanel';
        filterPanel.className = 'plugin-filter-panel';
        filterPanel.style.display = 'none';
        filterPanel.innerHTML = `
            <div class="filter-panel-header">
                <h3>플러그인 필터</h3>
                <button class="close-btn" onclick="toggleFilterPanel()">✕</button>
            </div>
            <div id="filterSystemContainer"></div>
        `;
        
        document.body.appendChild(filterPanel);
        
        // 필터 시스템 초기화
        const filterSystem = new PluginFilterSystem('filterSystemContainer');
        window.mainPluginFilter = filterSystem;
        
        // 필터 변경 시 메인 인터페이스 업데이트
        filterSystem.onFilterChange((filters) => {
            updateMainInterface(filters);
            updateFilterBadge(filters);
        });
        
        // 플러그인 선택 시 실행
        filterSystem.onPluginSelect((pluginId) => {
            executePluginById(pluginId);
            toggleFilterPanel(); // 패널 닫기
        });
    };
    
    // 스타일 추가
    const addIntegrationStyles = () => {
        const styles = `
            <style>
                .filter-toggle-btn {
                    background: #007bff;
                    color: white;
                    border: none;
                    padding: 8px 16px;
                    border-radius: 6px;
                    cursor: pointer;
                    font-size: 14px;
                    display: flex;
                    align-items: center;
                    gap: 6px;
                    transition: all 0.3s ease;
                    position: relative;
                }
                
                .filter-toggle-btn:hover {
                    background: #0056b3;
                    transform: translateY(-1px);
                    box-shadow: 0 2px 8px rgba(0, 123, 255, 0.3);
                }
                
                .filter-toggle-btn .badge {
                    background: #dc3545;
                    color: white;
                    padding: 2px 6px;
                    border-radius: 10px;
                    font-size: 11px;
                    font-weight: 600;
                    min-width: 18px;
                    text-align: center;
                }
                
                .plugin-filter-panel {
                    position: fixed;
                    top: 0;
                    right: 0;
                    width: 500px;
                    height: 100vh;
                    background: white;
                    box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
                    z-index: 1000;
                    overflow-y: auto;
                    transition: transform 0.3s ease;
                    transform: translateX(100%);
                }
                
                .plugin-filter-panel.active {
                    transform: translateX(0);
                }
                
                .filter-panel-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 20px;
                    border-bottom: 1px solid #e0e0e0;
                    position: sticky;
                    top: 0;
                    background: white;
                    z-index: 10;
                }
                
                .filter-panel-header h3 {
                    margin: 0;
                    font-size: 20px;
                    color: #333;
                }
                
                .filter-panel-header .close-btn {
                    background: none;
                    border: none;
                    font-size: 24px;
                    color: #999;
                    cursor: pointer;
                    padding: 0;
                    width: 32px;
                    height: 32px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 4px;
                    transition: all 0.2s;
                }
                
                .filter-panel-header .close-btn:hover {
                    background: #f0f0f0;
                    color: #333;
                }
                
                #filterSystemContainer {
                    padding: 20px;
                }
                
                /* 필터 적용 시 카드 하이라이트 */
                .menu-card.filter-match {
                    border: 2px solid #007bff;
                    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
                }
                
                .menu-card.filter-no-match {
                    opacity: 0.3;
                    pointer-events: none;
                }
                
                /* 오버레이 */
                .filter-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0, 0, 0, 0.3);
                    z-index: 999;
                    display: none;
                    transition: opacity 0.3s ease;
                }
                
                .filter-overlay.active {
                    display: block;
                }
                
                /* 반응형 디자인 */
                @media (max-width: 768px) {
                    .plugin-filter-panel {
                        width: 100%;
                    }
                }
            </style>
        `;
        
        document.head.insertAdjacentHTML('beforeend', styles);
    };
    
    // 초기화
    addIntegrationStyles();
    setTimeout(() => {
        addFilterButton();
        createFilterPanel();
        createOverlay();
    }, 1000);
}

// 필터 패널 토글
function toggleFilterPanel() {
    const panel = document.getElementById('pluginFilterPanel');
    const overlay = document.getElementById('filterOverlay');
    
    if (panel.classList.contains('active')) {
        panel.classList.remove('active');
        overlay.classList.remove('active');
    } else {
        panel.classList.add('active');
        overlay.classList.add('active');
        
        // 현재 카테고리와 탭으로 필터 설정
        if (window.mainPluginFilter && window.currentCategory) {
            document.getElementById('categoryFilter').value = window.currentCategory;
            window.mainPluginFilter.filters.category = window.currentCategory;
            window.mainPluginFilter.updateTabOptions();
            
            if (window.currentTab && window.currentTab.title) {
                document.getElementById('tabFilter').value = window.currentTab.title;
                window.mainPluginFilter.filters.tab = window.currentTab.title;
            }
            
            window.mainPluginFilter.applyFilters();
        }
    }
}

// 오버레이 생성
function createOverlay() {
    const overlay = document.createElement('div');
    overlay.id = 'filterOverlay';
    overlay.className = 'filter-overlay';
    overlay.onclick = toggleFilterPanel;
    document.body.appendChild(overlay);
}

// 필터 배지 업데이트
function updateFilterBadge(filters) {
    const badge = document.getElementById('filterBadge');
    if (!badge) return;
    
    let activeCount = 0;
    if (filters.category) activeCount++;
    if (filters.tab) activeCount++;
    if (filters.pluginType) activeCount++;
    if (filters.searchQuery) activeCount++;
    if (!filters.isActive) activeCount++;
    
    if (activeCount > 0) {
        badge.style.display = 'inline-block';
        badge.textContent = activeCount;
    } else {
        badge.style.display = 'none';
    }
}

// 메인 인터페이스 업데이트 (필터 적용)
function updateMainInterface(filters) {
    const menuCards = document.querySelectorAll('.menu-card');
    
    menuCards.forEach(card => {
        // 플러그인 추가 카드는 건너뛰기
        if (card.classList.contains('add-card')) return;
        
        let matches = true;
        
        // 카테고리 확인
        if (filters.category && window.currentCategory !== filters.category) {
            matches = false;
        }
        
        // 탭 확인
        if (filters.tab && window.currentTab && window.currentTab.title !== filters.tab) {
            matches = false;
        }
        
        // 플러그인 타입 확인
        if (filters.pluginType) {
            const onclick = card.getAttribute('onclick');
            if (onclick && !onclick.includes(`'${filters.pluginType}'`)) {
                matches = false;
            }
        }
        
        // 검색어 확인
        if (filters.searchQuery) {
            const title = card.querySelector('h4')?.textContent.toLowerCase() || '';
            const description = card.querySelector('.card-description')?.textContent.toLowerCase() || '';
            
            if (!title.includes(filters.searchQuery) && !description.includes(filters.searchQuery)) {
                matches = false;
            }
        }
        
        // 필터 결과 적용
        if (matches) {
            card.classList.remove('filter-no-match');
            card.classList.add('filter-match');
        } else {
            card.classList.remove('filter-match');
            card.classList.add('filter-no-match');
        }
    });
}

// ID로 플러그인 실행
async function executePluginById(pluginId) {
    try {
        // 플러그인 정보 가져오기
        const response = await fetch(`plugin_settings_api_real.php?action=getCardSettings&user_id=${window.ktmPluginClient?.currentUserId || 1}`);
        const result = await response.json();
        
        if (result.success) {
            const plugin = result.data.find(p => p.id === pluginId);
            if (plugin) {
                // executePluginAction 함수 호출
                if (typeof executePluginAction === 'function') {
                    executePluginAction(plugin.plugin_id, plugin.plugin_config || {});
                } else {
                    console.error('executePluginAction 함수를 찾을 수 없습니다.');
                }
            }
        }
    } catch (error) {
        console.error('플러그인 실행 실패:', error);
    }
}

// 페이지 로드 시 자동 실행
document.addEventListener('DOMContentLoaded', function() {
    // script.js가 로드된 후 실행
    if (typeof PluginFilterSystem !== 'undefined') {
        setTimeout(() => {
            integratePluginFilter();
        }, 2000);
    }
});

// 전역 함수로 노출
window.integratePluginFilter = integratePluginFilter;
window.toggleFilterPanel = toggleFilterPanel;