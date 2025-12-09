/**
 * 탭별 플러그인 필터링 시스템
 * Tab-based Plugin Filtering System
 */

class PluginFilterSystem {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        this.filters = {
            category: null,
            tab: null,
            pluginType: null,
            searchQuery: '',
            isActive: true
        };
        this.callbacks = {
            onFilterChange: null,
            onPluginSelect: null
        };
        this.init();
    }

    init() {
        this.createFilterUI();
        this.attachEventListeners();
        this.loadFilterState();
    }

    createFilterUI() {
        this.container.innerHTML = `
            <div class="plugin-filter-system">
                <div class="filter-header">
                    <h3>플러그인 필터</h3>
                    <button class="filter-reset-btn" onclick="pluginFilter.resetFilters()">
                        <span class="icon">🔄</span> 필터 초기화
                    </button>
                </div>
                
                <div class="filter-controls">
                    <!-- 카테고리 필터 -->
                    <div class="filter-group">
                        <label class="filter-label">카테고리</label>
                        <select id="categoryFilter" class="filter-select">
                            <option value="">전체 카테고리</option>
                            <option value="menu_tab">수학교실</option>
                            <option value="student_management">학생관리</option>
                            <option value="class_management">학급운영</option>
                            <option value="administration">행정업무</option>
                            <option value="communication">소통채널</option>
                            <option value="viral">바이럴 마케팅</option>
                        </select>
                    </div>
                    
                    <!-- 탭 필터 -->
                    <div class="filter-group">
                        <label class="filter-label">탭</label>
                        <select id="tabFilter" class="filter-select" disabled>
                            <option value="">탭을 선택하세요</option>
                        </select>
                    </div>
                    
                    <!-- 플러그인 타입 필터 -->
                    <div class="filter-group">
                        <label class="filter-label">플러그인 타입</label>
                        <select id="pluginTypeFilter" class="filter-select">
                            <option value="">전체 타입</option>
                            <option value="internal_link">내부 링크</option>
                            <option value="external_link">외부 링크</option>
                            <option value="send_message">메시지 발송</option>
                            <option value="agent">에이전트</option>
                            <option value="default_card">기본 카드</option>
                        </select>
                    </div>
                    
                    <!-- 검색 필터 -->
                    <div class="filter-group filter-search">
                        <label class="filter-label">검색</label>
                        <div class="search-input-wrapper">
                            <input type="text" id="searchFilter" class="filter-input" placeholder="플러그인 이름 검색...">
                            <span class="search-icon">🔍</span>
                        </div>
                    </div>
                    
                    <!-- 활성 상태 필터 -->
                    <div class="filter-group filter-checkbox">
                        <label class="checkbox-label">
                            <input type="checkbox" id="activeFilter" checked>
                            <span>활성 플러그인만 표시</span>
                        </label>
                    </div>
                </div>
                
                <!-- 필터 상태 표시 -->
                <div class="filter-status">
                    <div class="active-filters" id="activeFilters"></div>
                    <div class="filter-count" id="filterCount">전체 플러그인</div>
                </div>
                
                <!-- 필터링된 플러그인 목록 -->
                <div class="filtered-plugins" id="filteredPlugins">
                    <div class="loading">플러그인을 불러오는 중...</div>
                </div>
            </div>
        `;

        this.addStyles();
    }

    addStyles() {
        const styleId = 'plugin-filter-styles';
        if (document.getElementById(styleId)) return;

        const styles = `
            <style id="${styleId}">
                .plugin-filter-system {
                    background: #ffffff;
                    border-radius: 12px;
                    padding: 24px;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                }

                .filter-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 24px;
                    padding-bottom: 16px;
                    border-bottom: 2px solid #f0f0f0;
                }

                .filter-header h3 {
                    margin: 0;
                    color: #333;
                    font-size: 20px;
                    font-weight: 600;
                }

                .filter-reset-btn {
                    background: #f0f0f0;
                    border: none;
                    padding: 8px 16px;
                    border-radius: 6px;
                    cursor: pointer;
                    font-size: 14px;
                    color: #666;
                    display: flex;
                    align-items: center;
                    gap: 4px;
                    transition: all 0.3s ease;
                }

                .filter-reset-btn:hover {
                    background: #e0e0e0;
                    color: #333;
                }

                .filter-controls {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                    gap: 16px;
                    margin-bottom: 20px;
                }

                .filter-group {
                    display: flex;
                    flex-direction: column;
                    gap: 8px;
                }

                .filter-label {
                    font-size: 13px;
                    font-weight: 500;
                    color: #666;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }

                .filter-select,
                .filter-input {
                    padding: 10px 12px;
                    border: 1px solid #e0e0e0;
                    border-radius: 8px;
                    font-size: 14px;
                    background: #fafafa;
                    transition: all 0.3s ease;
                }

                .filter-select:hover:not(:disabled),
                .filter-input:hover {
                    border-color: #007bff;
                    background: #ffffff;
                }

                .filter-select:focus,
                .filter-input:focus {
                    outline: none;
                    border-color: #007bff;
                    background: #ffffff;
                    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
                }

                .filter-select:disabled {
                    opacity: 0.6;
                    cursor: not-allowed;
                    background: #f5f5f5;
                }

                .filter-search {
                    grid-column: span 2;
                }

                .search-input-wrapper {
                    position: relative;
                }

                .search-icon {
                    position: absolute;
                    right: 12px;
                    top: 50%;
                    transform: translateY(-50%);
                    opacity: 0.5;
                    pointer-events: none;
                }

                .filter-checkbox {
                    display: flex;
                    align-items: center;
                }

                .checkbox-label {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    cursor: pointer;
                    font-size: 14px;
                    color: #333;
                    user-select: none;
                }

                .checkbox-label input[type="checkbox"] {
                    width: 18px;
                    height: 18px;
                    cursor: pointer;
                }

                .filter-status {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 12px 16px;
                    background: #f8f9fa;
                    border-radius: 8px;
                    margin-bottom: 20px;
                }

                .active-filters {
                    display: flex;
                    gap: 8px;
                    flex-wrap: wrap;
                }

                .filter-tag {
                    background: #007bff;
                    color: white;
                    padding: 4px 12px;
                    border-radius: 20px;
                    font-size: 12px;
                    display: flex;
                    align-items: center;
                    gap: 6px;
                }

                .filter-tag .remove {
                    cursor: pointer;
                    opacity: 0.8;
                    transition: opacity 0.2s;
                }

                .filter-tag .remove:hover {
                    opacity: 1;
                }

                .filter-count {
                    font-size: 14px;
                    color: #666;
                    font-weight: 500;
                }

                .filtered-plugins {
                    min-height: 200px;
                    max-height: 600px;
                    overflow-y: auto;
                    padding: 4px;
                }

                .plugin-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                    gap: 16px;
                }

                .plugin-card {
                    background: #fafafa;
                    border: 1px solid #e0e0e0;
                    border-radius: 12px;
                    padding: 16px;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    position: relative;
                    overflow: hidden;
                }

                .plugin-card:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                    border-color: #007bff;
                }

                .plugin-card.inactive {
                    opacity: 0.6;
                }

                .plugin-type-badge {
                    position: absolute;
                    top: 12px;
                    right: 12px;
                    background: #007bff;
                    color: white;
                    padding: 4px 8px;
                    border-radius: 4px;
                    font-size: 11px;
                    font-weight: 500;
                    text-transform: uppercase;
                }

                .plugin-card h4 {
                    margin: 0 0 8px 0;
                    font-size: 16px;
                    color: #333;
                    font-weight: 600;
                }

                .plugin-meta {
                    display: flex;
                    gap: 16px;
                    margin-bottom: 8px;
                    font-size: 12px;
                    color: #666;
                }

                .plugin-meta-item {
                    display: flex;
                    align-items: center;
                    gap: 4px;
                }

                .plugin-description {
                    font-size: 13px;
                    color: #666;
                    line-height: 1.5;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    display: -webkit-box;
                    -webkit-line-clamp: 2;
                    -webkit-box-orient: vertical;
                }

                .no-results {
                    text-align: center;
                    padding: 60px 20px;
                    color: #999;
                }

                .no-results h4 {
                    margin: 0 0 8px 0;
                    font-size: 18px;
                    color: #666;
                }

                .loading {
                    text-align: center;
                    padding: 60px 20px;
                    color: #666;
                }

                .loading::after {
                    content: '';
                    display: inline-block;
                    width: 20px;
                    height: 20px;
                    margin-left: 10px;
                    border: 2px solid #f3f3f3;
                    border-top: 2px solid #007bff;
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                }

                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            </style>
        `;

        document.head.insertAdjacentHTML('beforeend', styles);
    }

    attachEventListeners() {
        // 카테고리 변경
        document.getElementById('categoryFilter').addEventListener('change', (e) => {
            this.filters.category = e.target.value || null;
            this.updateTabOptions();
            this.applyFilters();
        });

        // 탭 변경
        document.getElementById('tabFilter').addEventListener('change', (e) => {
            this.filters.tab = e.target.value || null;
            this.applyFilters();
        });

        // 플러그인 타입 변경
        document.getElementById('pluginTypeFilter').addEventListener('change', (e) => {
            this.filters.pluginType = e.target.value || null;
            this.applyFilters();
        });

        // 검색어 입력
        let searchTimeout;
        document.getElementById('searchFilter').addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.filters.searchQuery = e.target.value.toLowerCase();
                this.applyFilters();
            }, 300);
        });

        // 활성 상태 변경
        document.getElementById('activeFilter').addEventListener('change', (e) => {
            this.filters.isActive = e.target.checked;
            this.applyFilters();
        });
    }

    updateTabOptions() {
        const tabSelect = document.getElementById('tabFilter');
        const category = this.filters.category;

        // 탭 옵션 초기화
        tabSelect.innerHTML = '<option value="">탭을 선택하세요</option>';
        this.filters.tab = null;

        if (!category) {
            tabSelect.disabled = true;
            return;
        }

        tabSelect.disabled = false;

        // 카테고리별 탭 정의
        const tabsByCategory = {
            menu_tab: ['맞춤형 학습', '평가시스템', 'AI 도우미', '학습 분석'],
            student_management: ['상담관리', '건강관리', '진로진학', '특별활동'],
            class_management: ['학급조직', '학급회의', '보상시스템', '학급활동'],
            administration: ['학적업무', '공문서류', '일정관리', '예산관리'],
            communication: ['학생일지', '가정통신', '교사협업', '학생소통'],
            viral: ['블로그', '유튜브', '인스타']
        };

        const tabs = tabsByCategory[category] || [];
        tabs.forEach(tab => {
            const option = document.createElement('option');
            option.value = tab;
            option.textContent = tab;
            tabSelect.appendChild(option);
        });
    }

    async applyFilters() {
        this.updateActiveFilters();
        this.saveFilterState();
        
        // 필터링된 플러그인 로드
        await this.loadFilteredPlugins();
        
        // 콜백 실행
        if (this.callbacks.onFilterChange) {
            this.callbacks.onFilterChange(this.filters);
        }
    }

    updateActiveFilters() {
        const activeFiltersDiv = document.getElementById('activeFilters');
        const tags = [];

        if (this.filters.category) {
            tags.push({
                label: `카테고리: ${this.getCategoryLabel(this.filters.category)}`,
                key: 'category'
            });
        }

        if (this.filters.tab) {
            tags.push({
                label: `탭: ${this.filters.tab}`,
                key: 'tab'
            });
        }

        if (this.filters.pluginType) {
            tags.push({
                label: `타입: ${this.getPluginTypeLabel(this.filters.pluginType)}`,
                key: 'pluginType'
            });
        }

        if (this.filters.searchQuery) {
            tags.push({
                label: `검색: "${this.filters.searchQuery}"`,
                key: 'searchQuery'
            });
        }

        activeFiltersDiv.innerHTML = tags.map(tag => `
            <div class="filter-tag">
                ${tag.label}
                <span class="remove" onclick="pluginFilter.removeFilter('${tag.key}')">✕</span>
            </div>
        `).join('');
    }

    removeFilter(key) {
        if (key === 'category') {
            this.filters.category = null;
            this.filters.tab = null;
            document.getElementById('categoryFilter').value = '';
            this.updateTabOptions();
        } else if (key === 'tab') {
            this.filters.tab = null;
            document.getElementById('tabFilter').value = '';
        } else if (key === 'pluginType') {
            this.filters.pluginType = null;
            document.getElementById('pluginTypeFilter').value = '';
        } else if (key === 'searchQuery') {
            this.filters.searchQuery = '';
            document.getElementById('searchFilter').value = '';
        }
        
        this.applyFilters();
    }

    resetFilters() {
        this.filters = {
            category: null,
            tab: null,
            pluginType: null,
            searchQuery: '',
            isActive: true
        };
        
        document.getElementById('categoryFilter').value = '';
        document.getElementById('tabFilter').value = '';
        document.getElementById('pluginTypeFilter').value = '';
        document.getElementById('searchFilter').value = '';
        document.getElementById('activeFilter').checked = true;
        
        this.updateTabOptions();
        this.applyFilters();
    }

    async loadFilteredPlugins() {
        const pluginsDiv = document.getElementById('filteredPlugins');
        pluginsDiv.innerHTML = '<div class="loading">플러그인을 불러오는 중...</div>';

        try {
            // 플러그인 데이터 로드
            let plugins = await this.fetchPlugins();
            
            // 필터 적용
            plugins = this.filterPlugins(plugins);
            
            // 결과 표시
            this.displayPlugins(plugins);
            
            // 카운트 업데이트
            document.getElementById('filterCount').textContent = `${plugins.length}개의 플러그인`;
            
        } catch (error) {
            console.error('플러그인 로드 실패:', error);
            pluginsDiv.innerHTML = '<div class="no-results"><h4>오류 발생</h4><p>플러그인을 불러올 수 없습니다.</p></div>';
        }
    }

    async fetchPlugins() {
        // KTM 플러그인 클라이언트 사용
        if (!window.ktmPluginClient) {
            throw new Error('플러그인 시스템이 초기화되지 않았습니다.');
        }

        // 전체 플러그인 로드
        const response = await fetch('plugin_settings_api_real.php?action=getCardSettings&user_id=1');
        const result = await response.json();
        
        if (result.success) {
            return result.data || [];
        } else {
            throw new Error(result.error || '플러그인 로드 실패');
        }
    }

    filterPlugins(plugins) {
        return plugins.filter(plugin => {
            // 카테고리 필터
            if (this.filters.category && plugin.category !== this.filters.category) {
                return false;
            }
            
            // 탭 필터
            if (this.filters.tab && plugin.card_title !== this.filters.tab) {
                return false;
            }
            
            // 플러그인 타입 필터
            if (this.filters.pluginType && plugin.plugin_id !== this.filters.pluginType) {
                return false;
            }
            
            // 활성 상태 필터
            if (this.filters.isActive && !plugin.is_active) {
                return false;
            }
            
            // 검색어 필터
            if (this.filters.searchQuery) {
                const pluginName = (plugin.plugin_config?.plugin_name || '').toLowerCase();
                const description = (plugin.plugin_config?.card_description || '').toLowerCase();
                
                if (!pluginName.includes(this.filters.searchQuery) && 
                    !description.includes(this.filters.searchQuery)) {
                    return false;
                }
            }
            
            return true;
        });
    }

    displayPlugins(plugins) {
        const pluginsDiv = document.getElementById('filteredPlugins');
        
        if (plugins.length === 0) {
            pluginsDiv.innerHTML = `
                <div class="no-results">
                    <h4>검색 결과가 없습니다</h4>
                    <p>필터 조건을 변경해보세요.</p>
                </div>
            `;
            return;
        }
        
        const pluginCards = plugins.map(plugin => {
            const config = plugin.plugin_config || {};
            const name = config.plugin_name || '이름 없음';
            const description = config.card_description || config.description || '';
            const isActive = plugin.is_active ? '' : 'inactive';
            
            return `
                <div class="plugin-card ${isActive}" onclick="pluginFilter.selectPlugin(${plugin.id})">
                    <div class="plugin-type-badge">${this.getPluginTypeLabel(plugin.plugin_id)}</div>
                    <h4>${name}</h4>
                    <div class="plugin-meta">
                        <div class="plugin-meta-item">
                            <span>📁</span>
                            <span>${this.getCategoryLabel(plugin.category)}</span>
                        </div>
                        <div class="plugin-meta-item">
                            <span>📑</span>
                            <span>${plugin.card_title}</span>
                        </div>
                    </div>
                    <p class="plugin-description">${description}</p>
                </div>
            `;
        }).join('');
        
        pluginsDiv.innerHTML = `<div class="plugin-grid">${pluginCards}</div>`;
    }

    selectPlugin(pluginId) {
        if (this.callbacks.onPluginSelect) {
            this.callbacks.onPluginSelect(pluginId);
        }
    }

    getCategoryLabel(category) {
        const labels = {
            menu_tab: '수학교실',
            student_management: '학생관리',
            class_management: '학급운영',
            administration: '행정업무',
            communication: '소통채널',
            viral: '바이럴 마케팅'
        };
        return labels[category] || category;
    }

    getPluginTypeLabel(type) {
        const labels = {
            internal_link: '내부 링크',
            external_link: '외부 링크',
            send_message: '메시지',
            agent: '에이전트',
            default_card: '기본 카드'
        };
        return labels[type] || type;
    }

    saveFilterState() {
        localStorage.setItem('pluginFilters', JSON.stringify(this.filters));
    }

    loadFilterState() {
        const saved = localStorage.getItem('pluginFilters');
        if (saved) {
            try {
                const filters = JSON.parse(saved);
                this.filters = { ...this.filters, ...filters };
                
                // UI 복원
                if (this.filters.category) {
                    document.getElementById('categoryFilter').value = this.filters.category;
                    this.updateTabOptions();
                }
                if (this.filters.tab) {
                    document.getElementById('tabFilter').value = this.filters.tab;
                }
                if (this.filters.pluginType) {
                    document.getElementById('pluginTypeFilter').value = this.filters.pluginType;
                }
                if (this.filters.searchQuery) {
                    document.getElementById('searchFilter').value = this.filters.searchQuery;
                }
                document.getElementById('activeFilter').checked = this.filters.isActive;
                
                this.applyFilters();
            } catch (e) {
                console.error('필터 상태 복원 실패:', e);
            }
        }
    }

    // 콜백 설정
    onFilterChange(callback) {
        this.callbacks.onFilterChange = callback;
    }

    onPluginSelect(callback) {
        this.callbacks.onPluginSelect = callback;
    }
}

// 전역 인스턴스 생성 (페이지에서 사용)
window.PluginFilterSystem = PluginFilterSystem;