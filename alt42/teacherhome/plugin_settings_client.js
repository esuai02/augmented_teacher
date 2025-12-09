/**
 * KTM 코파일럿 플러그인 설정 클라이언트 라이브러리
 * 작성일: 2024-12-31
 * 설명: teacherhome/index.html에서 사용하는 플러그인 설정 관리 JavaScript 라이브러리
 */

class KTMPluginSettingsClient {
    constructor(apiEndpoint = 'plugin_settings_api_real.php') {
        this.apiEndpoint = apiEndpoint;
        this.currentUserId = null;
        this.pluginTypes = [];
        this.userSettings = {};
        this.cardSettings = {};
        this.events = {};
        
        this.init();
    }
    
    /**
     * 초기화
     */
    async init() {
        try {
            // 현재 사용자 ID 설정 (실제 구현에서는 세션에서 가져오기)
            this.currentUserId = this.getCurrentUserId();
            
            // 플러그인 타입 로드
            await this.loadPluginTypes();
            
            // 사용자 설정 로드
            await this.loadUserSettings();
            
            console.log('KTM 플러그인 설정 클라이언트 초기화 완료');
        } catch (error) {
            console.error('플러그인 설정 클라이언트 초기화 실패:', error);
        }
    }
    
    /**
     * 현재 사용자 ID 획득 (실제 구현에서는 세션에서 가져와야 함)
     */
    getCurrentUserId() {
        // URL 파라미터에서 userid 확인
        const urlParams = new URLSearchParams(window.location.search);
        const urlUserId = urlParams.get('userid');
        
        if (urlUserId) {
            // URL에 userid가 있으면 우선 사용
            const userId = parseInt(urlUserId);
            localStorage.setItem('ktm_user_id', userId);
            console.log('Using user ID from URL:', userId);
            return userId;
        }
        
        // URL에 없으면 localStorage에서 확인
        let userId = localStorage.getItem('ktm_user_id');
        if (!userId) {
            // 둘 다 없으면 고정 기본값 사용 (테스트용)
            userId = 1; // 고정된 user_id 사용하여 일관성 보장
            localStorage.setItem('ktm_user_id', userId);
            console.log('Using default user ID:', userId);
        } else {
            console.log('Using existing user ID from localStorage:', userId);
        }
        return parseInt(userId); // 숫자로 변환
    }
    
    /**
     * 사용자 ID 설정
     */
    setUserId(userId) {
        this.currentUserId = userId;
        localStorage.setItem('ktm_user_id', userId);
    }
    
    /**
     * API 호출 헬퍼
     */
    async apiCall(action, data = {}) {
        try {
            const formData = new FormData();
            formData.append('action', action);
            formData.append('user_id', this.currentUserId);
            
            // 데이터를 FormData로 변환
            for (const [key, value] of Object.entries(data)) {
                if (value !== null && value !== undefined) {
                    if (typeof value === 'object' && !(value instanceof File)) {
                        formData.append(key, JSON.stringify(value));
                    } else {
                        formData.append(key, value);
                    }
                }
            }
            
            console.log('API Call:', action, 'to', this.apiEndpoint);
            
            const response = await fetch(this.apiEndpoint, {
                method: 'POST',
                body: formData
            });
            
            console.log('HTTP Response status:', response.status, response.statusText);
            
            if (!response.ok) {
                const errorText = await response.text();
                console.error('HTTP Error response:', errorText);
                throw new Error(`HTTP error! status: ${response.status} - ${errorText}`);
            }
            
            const responseText = await response.text();
            console.log('Raw response:', responseText);
            
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (parseError) {
                console.error('JSON Parse Error:', parseError);
                console.error('Response was:', responseText);
                throw new Error('Invalid JSON response from server');
            }
            
            console.log('API Response:', result);
            
            if (!result.success) {
                throw new Error(result.error || '알 수 없는 오류');
            }
            
            return result;
        } catch (error) {
            console.error('API 호출 실패:', error);
            throw error;
        }
    }
    
    /**
     * 플러그인 타입 로드
     */
    async loadPluginTypes() {
        try {
            console.log('Loading plugin types...');
            const result = await this.apiCall('get_plugin_types');
            
            if (!result.success) {
                throw new Error(result.error || '플러그인 타입 로드 실패');
            }
            
            this.pluginTypes = result.data || [];
            console.log('Plugin types loaded:', this.pluginTypes.length, 'types');
            
            if (this.pluginTypes.length === 0) {
                console.warn('No plugin types found - this might cause issues');
            }
            
            return this.pluginTypes;
        } catch (error) {
            console.error('플러그인 타입 로드 실패:', error);
            this.pluginTypes = [];
            throw error;
        }
    }
    
    /**
     * 사용자 설정 로드
     */
    async loadUserSettings(category = null) {
        try {
            console.log('Loading user settings for category:', category);
            
            const result = await this.apiCall('getUserSettings', {
                category: category
            });
            
            const settings = result.data || [];
            console.log('Loaded settings:', settings);
            
            // 카테고리별로 설정 정리
            if (category) {
                this.userSettings[category] = settings;
                console.log('Set userSettings for category', category, ':', settings);
            } else {
                this.userSettings = {};
                settings.forEach(setting => {
                    const cat = setting.category || 'global';
                    if (!this.userSettings[cat]) {
                        this.userSettings[cat] = [];
                    }
                    this.userSettings[cat].push(setting);
                });
                console.log('Set all userSettings:', this.userSettings);
            }
            
            return this.userSettings;
        } catch (error) {
            console.error('사용자 설정 로드 실패:', error);
            throw error;
        }
    }
    
    /**
     * 카드 설정 로드
     */
    async loadCardSettings(category = null, cardTitle = null) {
        try {
            const params = {};
            if (category) params.category = category;
            if (cardTitle) params.card_title = cardTitle;
            
            const result = await this.apiCall('getCardSettings', params);
            
            const settings = result.data || [];
            
            console.log('loadCardSettings - params:', params);
            console.log('loadCardSettings - result:', settings);
            
            // 카테고리별로 설정 정리
            if (category) {
                if (!this.cardSettings[category]) {
                    this.cardSettings[category] = {};
                }
                
                if (cardTitle) {
                    // 특정 탭의 카드만 저장
                    this.cardSettings[category][cardTitle] = settings;
                } else {
                    // 카테고리의 모든 카드를 탭별로 정리
                    this.cardSettings[category] = {};
                    
                    settings.forEach(setting => {
                        const title = setting.card_title;
                        if (!this.cardSettings[category][title]) {
                            this.cardSettings[category][title] = [];
                        }
                        this.cardSettings[category][title].push(setting);
                    });
                }
            } else {
                // 전체 카드를 카테고리와 탭별로 정리
                this.cardSettings = {};
                settings.forEach(setting => {
                    const cat = setting.category;
                    const title = setting.card_title;
                    
                    if (!this.cardSettings[cat]) {
                        this.cardSettings[cat] = {};
                    }
                    if (!this.cardSettings[cat][title]) {
                        this.cardSettings[cat][title] = [];
                    }
                    this.cardSettings[cat][title].push(setting);
                });
            }
            
            console.log('loadCardSettings - organized settings:', this.cardSettings);
            
            return this.cardSettings;
        } catch (error) {
            console.error('카드 설정 로드 실패:', error);
            throw error;
        }
    }
    
    /**
     * 사용자 설정 저장
     */
    async saveUserSetting(pluginId, settingName, settingValue, category = null) {
        try {
            const result = await this.apiCall('saveUserSetting', {
                plugin_id: pluginId,
                setting_name: settingName,
                setting_value: settingValue,
                category: category
            });
            
            // 로컬 캐시 업데이트
            await this.loadUserSettings(category);
            
            // 변경 이벤트 발생
            this.dispatchEvent('userSettingChanged', {
                pluginId, 
                settingName, 
                settingValue, 
                category,
                isNew: result.is_new || false
            });
            
            return result;
        } catch (error) {
            console.error('사용자 설정 저장 실패:', error);
            throw error;
        }
    }
    
    /**
     * 카드 설정 저장
     */
    async saveCardSetting(category, cardTitle, cardIndex, pluginId, pluginConfig, displayOrder = 0) {
        try {
            const result = await this.apiCall('saveCardSetting', {
                category: category,
                card_title: cardTitle,
                card_index: cardIndex,
                plugin_id: pluginId,
                config: pluginConfig,
                display_order: displayOrder
            });
            
            // 로컬 캐시 업데이트
            await this.loadCardSettings(category, cardTitle);
            
            // 변경 이벤트 발생
            this.dispatchEvent('cardSettingChanged', {
                category, 
                cardTitle, 
                cardIndex, 
                pluginId, 
                pluginConfig,
                isNew: result.is_new || false
            });
            
            return result;
        } catch (error) {
            console.error('카드 설정 저장 실패:', error);
            throw error;
        }
    }
    
    /**
     * 사용자 설정 삭제
     */
    async deleteUserSetting(pluginId, settingName, category = null) {
        try {
            const result = await this.apiCall('deleteUserSetting', {
                plugin_id: pluginId,
                setting_name: settingName,
                category: category
            });
            
            // 로컬 캐시 업데이트
            await this.loadUserSettings(category);
            
            // 삭제 이벤트 발생
            this.dispatchEvent('userSettingDeleted', {
                pluginId, settingName, category
            });
            
            return result;
        } catch (error) {
            console.error('사용자 설정 삭제 실패:', error);
            throw error;
        }
    }
    
    /**
     * 카드 설정 삭제
     */
    async deleteCardSetting(category, cardTitle, pluginId) {
        try {
            const result = await this.apiCall('deleteCardSetting', {
                category: category,
                card_title: cardTitle,
                plugin_id: pluginId
            });
            
            // 로컬 캐시 업데이트
            await this.loadCardSettings(category, cardTitle);
            
            // 삭제 이벤트 발생
            this.dispatchEvent('cardSettingDeleted', {
                category, cardTitle, pluginId
            });
            
            return result;
        } catch (error) {
            console.error('카드 설정 삭제 실패:', error);
            throw error;
        }
    }
    
    /**
     * 카드 설정 삭제 (ID 기반)
     */
    async deleteCardSettingById(category, cardTitle, cardId, cardIndex) {
        try {
            const result = await this.apiCall('deleteCardSetting', {
                category: category,
                card_title: cardTitle,
                card_id: cardId,
                card_index: cardIndex
            });
            
            // 로컬 캐시 업데이트
            await this.loadCardSettings(category, cardTitle);
            
            // 삭제 이벤트 발생
            this.dispatchEvent('cardSettingDeleted', {
                category, cardTitle, cardId, cardIndex
            });
            
            return result;
        } catch (error) {
            console.error('카드 설정 삭제 실패:', error);
            throw error;
        }
    }
    
    /**
     * 플러그인 타입 반환
     */
    getPluginTypes() {
        return this.pluginTypes;
    }
    
    /**
     * 특정 플러그인 타입 반환
     */
    getPluginType(pluginId) {
        return this.pluginTypes.find(plugin => plugin.plugin_id === pluginId);
    }
    
    /**
     * 사용자 설정 반환
     */
    getUserSettings(category = null) {
        if (category) {
            return this.userSettings[category] || [];
        }
        return this.userSettings;
    }
    
    /**
     * 카드 설정 반환
     */
    getCardSettings(category = null, cardTitle = null) {
        if (category && cardTitle) {
            // 특정 카테고리와 탭의 카드만 반환
            return this.cardSettings[category] && this.cardSettings[category][cardTitle] || [];
        }
        if (category) {
            // 카테고리의 모든 카드를 배열로 반환 (탭별 구분 없이)
            const categorySettings = this.cardSettings[category] || {};
            let allCards = [];
            Object.values(categorySettings).forEach(tabCards => {
                allCards = allCards.concat(tabCards);
            });
            return allCards;
        }
        return this.cardSettings;
    }
    
    /**
     * 플러그인 설정 UI 생성
     */
    async createPluginSettingsUI(container, category, cardTitle = null, existingPluginId = null) {
        const uiContainer = document.createElement('div');
        uiContainer.className = 'plugin-settings-ui';
        
        // 기존 설정 로드
        await this.loadUserSettings(category);
        if (cardTitle) {
            await this.loadCardSettings(category, cardTitle);
        }
        
        // 기존 플러그인이 있으면 바로 설정 폼 표시
        if (existingPluginId) {
            const settingsForm = document.createElement('div');
            settingsForm.className = 'plugin-settings-form';
            this.renderPluginSettingsForm(settingsForm, existingPluginId, category, cardTitle, true);
            uiContainer.appendChild(settingsForm);
        } else {
            // 플러그인 타입 선택
            const pluginSelector = document.createElement('select');
            pluginSelector.className = 'plugin-type-selector';
            pluginSelector.innerHTML = '<option value="">플러그인 선택...</option>';
            
            this.pluginTypes.forEach(plugin => {
                const option = document.createElement('option');
                option.value = plugin.plugin_id;
                option.textContent = `${plugin.plugin_icon} ${plugin.plugin_title}`;
                pluginSelector.appendChild(option);
            });
            
            // 설정 폼 컨테이너
            const settingsForm = document.createElement('div');
            settingsForm.className = 'plugin-settings-form';
            settingsForm.style.display = 'none';
            
            // 플러그인 선택 이벤트
            pluginSelector.addEventListener('change', (e) => {
                const pluginId = e.target.value;
                if (pluginId) {
                    this.renderPluginSettingsForm(settingsForm, pluginId, category, cardTitle, false);
                    settingsForm.style.display = 'block';
                } else {
                    settingsForm.style.display = 'none';
                }
            });
            
            uiContainer.appendChild(pluginSelector);
            uiContainer.appendChild(settingsForm);
        }
        
        // 기존 설정 표시
        this.displayExistingSettings(uiContainer, category, cardTitle);
        
        if (container) {
            container.appendChild(uiContainer);
        }
        
        return uiContainer;
    }
    
    /**
     * 기존 설정 표시
     */
    async displayExistingSettings(container, category, cardTitle = null) {
        try {
            console.log('Displaying existing settings for category:', category, 'cardTitle:', cardTitle);
            
            let settings = [];
            
            if (cardTitle) {
                // 카드 설정 로드 (강제 새로고침)
                await this.loadCardSettings(category, cardTitle);
                const cardSettings = this.getCardSettings(category, cardTitle);
                settings = cardSettings || [];
                console.log('Card settings loaded:', settings);
            } else {
                // 사용자 설정 로드 (강제 새로고침)
                await this.loadUserSettings(category);
                const userSettings = this.getUserSettings(category);
                settings = userSettings || [];
                console.log('User settings loaded for category', category, ':', settings);
            }
            
            if (settings.length > 0) {
                const listContainer = document.createElement('div');
                listContainer.className = 'existing-settings-list';
                listContainer.innerHTML = '<h4>현재 설정된 플러그인</h4>';
                
                const list = document.createElement('div');
                list.className = 'plugin-list';
                
                settings.forEach(setting => {
                    const item = this.createPluginListItem(setting, category, cardTitle);
                    list.appendChild(item);
                });
                
                listContainer.appendChild(list);
                container.appendChild(listContainer);
            }
        } catch (error) {
            console.error('기존 설정 표시 실패:', error);
        }
    }
    
    /**
     * 플러그인 목록 아이템 생성
     */
    createPluginListItem(setting, category, cardTitle) {
        const item = document.createElement('div');
        item.className = 'plugin-list-item';
        
        const pluginInfo = setting.plugin_title ? 
            `${setting.plugin_icon || ''} ${setting.plugin_title}` : 
            setting.plugin_id;
            
        const settingName = setting.setting_name || 'default_config';
        const displayName = setting.plugin_name || settingName;
        
        item.innerHTML = `
            <div class="plugin-item-header">
                <span class="plugin-item-title">${pluginInfo} - ${displayName}</span>
                <div class="plugin-item-actions">
                    <button class="action-btn edit-btn" data-setting-id="${setting.id}">수정</button>
                    <button class="action-btn delete-btn" data-setting-id="${setting.id}">삭제</button>
                </div>
            </div>
        `;
        
        // 수정 버튼 이벤트
        item.querySelector('.edit-btn').addEventListener('click', () => {
            this.editPluginSetting(setting, category, cardTitle);
        });
        
        // 삭제 버튼 이벤트
        item.querySelector('.delete-btn').addEventListener('click', () => {
            this.deletePluginSettingUI(setting, category, cardTitle);
        });
        
        return item;
    }
    
    /**
     * 플러그인 설정 편집
     */
    editPluginSetting(setting, category, cardTitle) {
        // 편집 모드로 폼 다시 생성
        const container = document.querySelector('.plugin-settings-ui');
        if (container) {
            container.innerHTML = '';
            
            // 편집 모드 헤더 추가
            const editHeader = document.createElement('div');
            editHeader.className = 'edit-mode-header';
            editHeader.style.background = '#e3f2fd';
            editHeader.style.padding = '10px';
            editHeader.style.borderRadius = '4px';
            editHeader.style.marginBottom = '15px';
            editHeader.style.border = '1px solid #2196F3';
            
            const settingValue = setting.setting_value || setting.plugin_config || {};
            const pluginName = settingValue.plugin_name || setting.setting_name || '이름 없음';
            editHeader.innerHTML = `
                <strong style="color: #1976D2;">📝 플러그인 설정 수정</strong><br>
                <small style="color: #666;">현재 편집 중: ${pluginName}</small>
            `;
            
            const settingsForm = document.createElement('div');
            settingsForm.className = 'plugin-settings-form';
            
            // 기존 설정값으로 폼 렌더링
            this.renderPluginSettingsForm(
                settingsForm, 
                setting.plugin_id, 
                category, 
                cardTitle, 
                true,
                setting
            );
            
            container.appendChild(editHeader);
            container.appendChild(settingsForm);
        }
    }
    
    /**
     * 플러그인 설정 삭제 UI
     */
    async deletePluginSettingUI(setting, category, cardTitle) {
        if (confirm('이 플러그인 설정을 삭제하시겠습니까?')) {
            try {
                if (cardTitle) {
                    await this.deleteCardSetting(category, cardTitle, setting.plugin_id);
                } else {
                    await this.deleteUserSetting(setting.plugin_id, setting.setting_name, category);
                }
                
                // UI 새로고침
                const container = document.querySelector('.plugin-settings-ui').parentElement;
                container.innerHTML = '';
                this.createPluginSettingsUI(container, category, cardTitle);
                
                alert('플러그인 설정이 삭제되었습니다.');
            } catch (error) {
                alert('삭제 실패: ' + error.message);
            }
        }
    }
    
    /**
     * 플러그인 설정 폼 렌더링
     */
    renderPluginSettingsForm(container, pluginId, category, cardTitle = null, isExisting = false, existingSetting = null) {
        const plugin = this.getPluginType(pluginId);
        if (!plugin) return;
        
        container.innerHTML = '';
        
        const form = document.createElement('form');
        form.className = 'plugin-config-form';
        
        const title = document.createElement('h3');
        title.textContent = `${plugin.plugin_icon} ${plugin.plugin_title} 설정`;
        form.appendChild(title);
        
        // 플러그인 이름 필드 추가 - 제목 바로 아래에 위치
        const nameField = document.createElement('div');
        nameField.className = 'form-field';
        nameField.style.marginBottom = '20px';
        nameField.style.padding = '15px';
        nameField.style.backgroundColor = '#f5f5f5';
        nameField.style.borderRadius = '4px';
        nameField.style.border = '1px solid #e0e0e0';
        
        const nameLabel = document.createElement('label');
        nameLabel.innerHTML = '카드 이름: <span style="color: red;">*</span>';
        nameLabel.style.fontWeight = 'bold';
        nameLabel.style.display = 'block';
        nameLabel.style.marginBottom = '8px';
        nameLabel.style.fontSize = '14px';
        
        const nameInput = document.createElement('input');
        nameInput.type = 'text';
        nameInput.name = 'plugin_name';
        nameInput.placeholder = '이 카드를 구분할 이름을 입력하세요 (필수)';
        nameInput.className = 'form-input';
        nameInput.required = true;
        nameInput.style.borderColor = '#ff6b6b';
        nameInput.style.width = '100%';
        nameInput.style.padding = '8px 12px';
        nameInput.style.fontSize = '14px';
        nameInput.style.borderRadius = '4px';
        nameInput.style.border = '2px solid #ff6b6b';
        nameInput.style.boxSizing = 'border-box';
        
        // 기존 설정이 있으면 값 설정
        if (existingSetting) {
            const settingValue = existingSetting.setting_value || existingSetting.plugin_config || {};
            nameInput.value = settingValue.plugin_name || existingSetting.setting_name || '';
            // 기존 값이 있으면 테두리 색상 정상으로 변경
            if (nameInput.value) {
                nameInput.style.borderColor = '#4CAF50';
            }
            
            // 수정 모드임을 표시
            if (isExisting) {
                nameInput.setAttribute('data-original-name', nameInput.value);
                nameLabel.innerHTML = '플러그인 이름 (수정 가능): <span style="color: red;">*</span>';
            }
        }
        
        // 플러그인 이름 입력 시 테두리 색상 변경
        nameInput.addEventListener('input', function() {
            if (this.value.trim()) {
                this.style.borderColor = '#4CAF50';
            } else {
                this.style.borderColor = '#ff6b6b';
            }
        });
        
        // 도움말 텍스트 추가
        const helpText = document.createElement('small');
        if (isExisting) {
            helpText.textContent = '카드 이름을 변경할 수 있습니다. 같은 카테고리 내에서 중복되지 않도록 입력하세요.';
        } else {
            helpText.textContent = '이 카드를 다른 카드와 구분하기 위한 고유한 이름을 입력하세요.';
        }
        helpText.style.color = '#666';
        helpText.style.fontSize = '12px';
        helpText.style.marginTop = '5px';
        helpText.style.display = 'block';
        helpText.style.fontStyle = 'italic';
        
        nameField.appendChild(nameLabel);
        nameField.appendChild(nameInput);
        nameField.appendChild(helpText);
        form.appendChild(nameField);
        
        console.log('플러그인 이름 필드 추가됨:', {
            nameField: nameField,
            nameInput: nameInput,
            value: nameInput.value,
            isExisting: isExisting,
            existingSetting: existingSetting
        });
        
        // 플러그인 설명 추가
        const description = document.createElement('p');
        description.textContent = plugin.plugin_description;
        description.className = 'plugin-description';
        description.style.marginBottom = '20px';
        description.style.color = '#666';
        description.style.fontSize = '14px';
        form.appendChild(description);
        
        // 구분선 추가
        const divider = document.createElement('hr');
        divider.style.margin = '20px 0';
        divider.style.border = 'none';
        divider.style.borderTop = '1px solid #e0e0e0';
        form.appendChild(divider);
        
        // 플러그인 타입별 설정 필드
        if (pluginId === 'internal_link') {
            this.createInternalLinkFields(form, category, cardTitle, existingSetting);
        } else if (pluginId === 'external_link') {
            this.createExternalLinkFields(form, category, cardTitle, existingSetting);
        } else if (pluginId === 'send_message') {
            this.createMessageFields(form, category, cardTitle, existingSetting);
        } else if (pluginId === 'agent') {
            this.createAgentFields(form, category, cardTitle, existingSetting);
        }
        
        // 저장 버튼
        const saveButton = document.createElement('button');
        saveButton.type = 'button';
        saveButton.textContent = isExisting ? '수정' : '저장';
        saveButton.className = 'save-button';
        saveButton.addEventListener('click', (e) => {
            e.preventDefault();
            this.savePluginSettings(form, pluginId, category, cardTitle, existingSetting);
        });
        
        // 취소 버튼 (편집 모드일 때)
        if (isExisting) {
            const cancelButton = document.createElement('button');
            cancelButton.type = 'button';
            cancelButton.textContent = '취소';
            cancelButton.className = 'cancel-button';
            cancelButton.style.marginLeft = '10px';
            cancelButton.addEventListener('click', () => {
                const container = document.querySelector('.plugin-settings-ui').parentElement;
                container.innerHTML = '';
                this.createPluginSettingsUI(container, category, cardTitle);
            });
            form.appendChild(cancelButton);
        }
        
        form.appendChild(saveButton);
        container.appendChild(form);
        
        // 플러그인 이름 필드에 포커스
        setTimeout(() => {
            const nameInput = form.querySelector('input[name="plugin_name"]');
            if (nameInput) {
                nameInput.focus();
            }
        }, 100);
    }
    
    /**
     * 내부 링크 설정 필드 생성
     */
    createInternalLinkFields(form, category, cardTitle, existingSetting = null) {
        const linkField = document.createElement('div');
        linkField.className = 'form-field';
        
        const label = document.createElement('label');
        label.textContent = '내부 링크 URL:';
        
        const input = document.createElement('input');
        input.type = 'text';
        input.name = 'internal_url';
        input.placeholder = '/path/to/internal/page';
        input.className = 'form-input';
        
        // 기존 설정값 적용
        if (existingSetting) {
            const config = existingSetting.setting_value || existingSetting.plugin_config || {};
            input.value = config.internal_url || '';
        }
        
        linkField.appendChild(label);
        linkField.appendChild(input);
        form.appendChild(linkField);
        
        // 새 탭 열기 옵션
        const newTabField = document.createElement('div');
        newTabField.className = 'form-field';
        
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.name = 'open_new_tab';
        checkbox.id = 'open_new_tab';
        
        // 기존 설정값 적용
        if (existingSetting) {
            const config = existingSetting.setting_value || existingSetting.plugin_config || {};
            checkbox.checked = config.open_new_tab || false;
        }
        
        const checkboxLabel = document.createElement('label');
        checkboxLabel.setAttribute('for', 'open_new_tab');
        checkboxLabel.textContent = '새 탭에서 열기';
        
        newTabField.appendChild(checkbox);
        newTabField.appendChild(checkboxLabel);
        form.appendChild(newTabField);
    }
    
    /**
     * 외부 링크 설정 필드 생성
     */
    createExternalLinkFields(form, category, cardTitle, existingSetting = null) {
        const linkField = document.createElement('div');
        linkField.className = 'form-field';
        
        const label = document.createElement('label');
        label.textContent = '외부 링크 URL:';
        
        const input = document.createElement('input');
        input.type = 'url';
        input.name = 'external_url';
        input.placeholder = 'https://example.com';
        input.className = 'form-input';
        
        // 기존 설정값 적용
        if (existingSetting) {
            const config = existingSetting.setting_value || existingSetting.plugin_config || {};
            input.value = config.external_url || '';
        }
        
        linkField.appendChild(label);
        linkField.appendChild(input);
        form.appendChild(linkField);
        
        // 새 탭 열기 옵션
        const newTabField = document.createElement('div');
        newTabField.className = 'form-field';
        
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.name = 'open_new_tab';
        checkbox.id = 'open_new_tab_external';
        checkbox.checked = true;
        
        // 기존 설정값 적용
        if (existingSetting) {
            const config = existingSetting.setting_value || existingSetting.plugin_config || {};
            checkbox.checked = config.open_new_tab !== undefined ? config.open_new_tab : true;
        }
        
        const checkboxLabel = document.createElement('label');
        checkboxLabel.setAttribute('for', 'open_new_tab_external');
        checkboxLabel.textContent = '새 탭에서 열기';
        
        newTabField.appendChild(checkbox);
        newTabField.appendChild(checkboxLabel);
        form.appendChild(newTabField);
    }
    
    /**
     * 메시지 발송 설정 필드 생성
     */
    createMessageFields(form, category, cardTitle, existingSetting = null) {
        const messageField = document.createElement('div');
        messageField.className = 'form-field';
        
        const label = document.createElement('label');
        label.textContent = '메시지 내용:';
        
        const textarea = document.createElement('textarea');
        textarea.name = 'message_content';
        textarea.placeholder = '사용자에게 발송할 메시지를 입력하세요...';
        textarea.className = 'form-textarea';
        textarea.rows = 4;
        
        // 기존 설정값 적용
        if (existingSetting) {
            const config = existingSetting.setting_value || existingSetting.plugin_config || {};
            textarea.value = config.message_content || '';
        }
        
        messageField.appendChild(label);
        messageField.appendChild(textarea);
        form.appendChild(messageField);
        
        // 메시지 타입 선택
        const typeField = document.createElement('div');
        typeField.className = 'form-field';
        
        const typeLabel = document.createElement('label');
        typeLabel.textContent = '메시지 타입:';
        
        const typeSelect = document.createElement('select');
        typeSelect.name = 'message_type';
        typeSelect.className = 'form-select';
        
        const options = [
            { value: 'info', text: '정보' },
            { value: 'warning', text: '경고' },
            { value: 'success', text: '성공' },
            { value: 'error', text: '오류' }
        ];
        
        options.forEach(option => {
            const optionElement = document.createElement('option');
            optionElement.value = option.value;
            optionElement.textContent = option.text;
            
            // 기존 설정값 적용
            if (existingSetting) {
                const config = existingSetting.setting_value || existingSetting.plugin_config || {};
                if (config.message_type === option.value) {
                    optionElement.selected = true;
                }
            }
            
            typeSelect.appendChild(optionElement);
        });
        
        typeField.appendChild(typeLabel);
        typeField.appendChild(typeSelect);
        form.appendChild(typeField);
    }
    
    /**
     * 에이전트 설정 필드 생성
     */
    createAgentFields(form, category, cardTitle, existingSetting = null) {
        const config = existingSetting ? (existingSetting.setting_value || existingSetting.plugin_config || {}) : {};
        
        // 에이전트 타입 선택
        const typeField = document.createElement('div');
        typeField.className = 'form-field';
        
        const typeLabel = document.createElement('label');
        typeLabel.textContent = '에이전트 타입:';
        
        const typeSelect = document.createElement('select');
        typeSelect.name = 'agent_type';
        typeSelect.className = 'form-select';
        typeSelect.required = true;
        
        const agentTypes = [
            { value: 'php', text: 'PHP 에이전트' },
            { value: 'javascript', text: 'JavaScript 에이전트' },
            { value: 'api', text: 'API 에이전트' },
            { value: 'custom', text: '사용자 정의' }
        ];
        
        agentTypes.forEach(type => {
            const option = document.createElement('option');
            option.value = type.value;
            option.textContent = type.text;
            if (config.agent_type === type.value) {
                option.selected = true;
            }
            typeSelect.appendChild(option);
        });
        
        typeField.appendChild(typeLabel);
        typeField.appendChild(typeSelect);
        form.appendChild(typeField);
        
        // 에이전트 설명
        const descField = document.createElement('div');
        descField.className = 'form-field';
        
        const descLabel = document.createElement('label');
        descLabel.textContent = '에이전트 설명:';
        
        const descInput = document.createElement('input');
        descInput.type = 'text';
        descInput.name = 'agent_description';
        descInput.placeholder = '이 에이전트가 하는 일을 설명하세요';
        descInput.className = 'form-input';
        descInput.value = config.agent_description || '';
        
        descField.appendChild(descLabel);
        descField.appendChild(descInput);
        form.appendChild(descField);
        
        // 에이전트 코드
        const codeField = document.createElement('div');
        codeField.className = 'form-field';
        
        const codeLabel = document.createElement('label');
        codeLabel.textContent = '에이전트 코드:';
        
        const codeTextarea = document.createElement('textarea');
        codeTextarea.name = 'agent_code';
        codeTextarea.placeholder = '에이전트 실행 코드를 입력하세요';
        codeTextarea.className = 'form-textarea';
        codeTextarea.rows = 10;
        codeTextarea.style.fontFamily = 'monospace';
        codeTextarea.value = config.agent_code || '';
        
        codeField.appendChild(codeLabel);
        codeField.appendChild(codeTextarea);
        form.appendChild(codeField);
        
        // 에이전트 URL (API 타입인 경우)
        const urlField = document.createElement('div');
        urlField.className = 'form-field';
        urlField.style.display = typeSelect.value === 'api' ? 'block' : 'none';
        
        const urlLabel = document.createElement('label');
        urlLabel.textContent = 'API URL:';
        
        const urlInput = document.createElement('input');
        urlInput.type = 'url';
        urlInput.name = 'agent_url';
        urlInput.placeholder = 'https://api.example.com/endpoint';
        urlInput.className = 'form-input';
        urlInput.value = config.agent_url || '';
        
        urlField.appendChild(urlLabel);
        urlField.appendChild(urlInput);
        form.appendChild(urlField);
        
        // 에이전트 프롬프트
        const promptField = document.createElement('div');
        promptField.className = 'form-field';
        
        const promptLabel = document.createElement('label');
        promptLabel.textContent = '프롬프트 템플릿:';
        
        const promptTextarea = document.createElement('textarea');
        promptTextarea.name = 'agent_prompt';
        promptTextarea.placeholder = '에이전트에 전달할 프롬프트 템플릿 (선택사항)';
        promptTextarea.className = 'form-textarea';
        promptTextarea.rows = 4;
        promptTextarea.value = config.agent_prompt || '';
        
        promptField.appendChild(promptLabel);
        promptField.appendChild(promptTextarea);
        form.appendChild(promptField);
        
        // 에이전트 설정 섹션
        const configSection = document.createElement('fieldset');
        configSection.className = 'agent-config-section';
        configSection.style.marginTop = '20px';
        configSection.style.padding = '15px';
        configSection.style.border = '1px solid #ddd';
        configSection.style.borderRadius = '4px';
        
        const configLegend = document.createElement('legend');
        configLegend.textContent = '에이전트 UI 설정';
        configLegend.style.fontWeight = 'bold';
        configSection.appendChild(configLegend);
        
        // 에이전트 설정 - 제목
        const configTitleField = document.createElement('div');
        configTitleField.className = 'form-field';
        
        const configTitleLabel = document.createElement('label');
        configTitleLabel.textContent = '카드 제목:';
        
        const configTitleInput = document.createElement('input');
        configTitleInput.type = 'text';
        configTitleInput.name = 'agent_config_title';
        configTitleInput.placeholder = '카드에 표시될 제목';
        configTitleInput.className = 'form-input';
        configTitleInput.value = (config.agent_config && config.agent_config.title) || '';
        
        configTitleField.appendChild(configTitleLabel);
        configTitleField.appendChild(configTitleInput);
        configSection.appendChild(configTitleField);
        
        // 에이전트 설정 - 설명
        const configDescField = document.createElement('div');
        configDescField.className = 'form-field';
        
        const configDescLabel = document.createElement('label');
        configDescLabel.textContent = '카드 설명:';
        
        const configDescInput = document.createElement('input');
        configDescInput.type = 'text';
        configDescInput.name = 'agent_config_description';
        configDescInput.placeholder = '카드에 표시될 간단한 설명';
        configDescInput.className = 'form-input';
        configDescInput.value = (config.agent_config && config.agent_config.description) || '';
        
        configDescField.appendChild(configDescLabel);
        configDescField.appendChild(configDescInput);
        configSection.appendChild(configDescField);
        
        // 에이전트 설정 - 액션
        const configActionField = document.createElement('div');
        configActionField.className = 'form-field';
        
        const configActionLabel = document.createElement('label');
        configActionLabel.textContent = '실행 액션:';
        
        const configActionInput = document.createElement('input');
        configActionInput.type = 'text';
        configActionInput.name = 'agent_config_action';
        configActionInput.placeholder = '에이전트 실행 시 호출할 액션 이름';
        configActionInput.className = 'form-input';
        configActionInput.value = (config.agent_config && config.agent_config.action) || '';
        
        configActionField.appendChild(configActionLabel);
        configActionField.appendChild(configActionInput);
        configSection.appendChild(configActionField);
        
        // agent_config_details는 사용하지 않음
        
        form.appendChild(configSection);
        
        // 타입 변경 시 URL 필드 표시/숨김
        typeSelect.addEventListener('change', function() {
            urlField.style.display = this.value === 'api' ? 'block' : 'none';
        });
    }
    
    /**
     * 플러그인 설정 저장
     */
    async savePluginSettings(form, pluginId, category, cardTitle = null, existingSetting = null) {
        try {
            const formData = new FormData(form);
            const config = {};
            const isExisting = existingSetting !== null;
            
            // 폼 데이터를 설정 객체로 변환
            for (let [key, value] of formData.entries()) {
                if (form.elements[key].type === 'checkbox') {
                    config[key] = form.elements[key].checked;
                } else {
                    config[key] = value;
                }
            }
            
            // agent 타입인 경우 agent_config 객체 생성
            if (pluginId === 'agent' && 
                (config.agent_config_title || config.agent_config_description || config.agent_config_action)) {
                config.agent_config = {
                    title: config.agent_config_title || '',
                    description: config.agent_config_description || '',
                    action: config.agent_config_action || ''
                };
                // 개별 필드는 제거 (중복 방지)
                delete config.agent_config_title;
                delete config.agent_config_description;
                delete config.agent_config_action;
            }
            
            // 플러그인 이름 검증
            if (!config.plugin_name || config.plugin_name.trim() === '') {
                alert('플러그인 이름을 입력해주세요.');
                const nameInput = form.querySelector('input[name="plugin_name"]');
                if (nameInput) {
                    nameInput.focus();
                    nameInput.style.borderColor = '#ff6b6b';
                }
                return;
            }
            
            // 플러그인 이름 길이 검증
            if (config.plugin_name.trim().length < 2) {
                alert('플러그인 이름은 2글자 이상 입력해주세요.');
                const nameInput = form.querySelector('input[name="plugin_name"]');
                if (nameInput) {
                    nameInput.focus();
                    nameInput.style.borderColor = '#ff6b6b';
                }
                return;
            }
            
            // 플러그인 추가/수정 전에 최신 데이터로 갱신
            await this.loadUserSettings(category);
            if (cardTitle) {
                await this.loadCardSettings(category, cardTitle);
            }
            
            // 중복 이름 확인
            console.log('Checking for duplicate names in category:', category);
            
            const existingSettings = cardTitle ? 
                this.getCardSettings(category, cardTitle) : 
                this.getUserSettings(category);
            
            console.log('Existing settings for duplicate check:', existingSettings);
            
            const nameInput = form.querySelector('input[name="plugin_name"]');
            const originalName = nameInput ? nameInput.getAttribute('data-original-name') : null;
            
            const duplicateName = existingSettings.find(setting => {
                const settingValue = setting.setting_value || setting.plugin_config || {};
                const existingName = settingValue.plugin_name || setting.setting_name;
                console.log('Comparing:', existingName, 'vs', config.plugin_name.trim());
                
                // 수정 모드에서는 자기 자신의 원래 이름과 비교해서 같으면 중복이 아님
                if (isExisting && originalName === existingName) {
                    return false;
                }
                
                return existingName === config.plugin_name.trim();
            });
            
            if (duplicateName) {
                alert('이미 같은 이름의 플러그인이 있습니다. 다른 이름을 입력해주세요.');
                if (nameInput) {
                    nameInput.focus();
                    nameInput.style.borderColor = '#ff6b6b';
                }
                return;
            }
            
            let result;
            if (cardTitle) {
                // 카드별 설정 저장
                // saveCardSetting 파라미터 순서 수정
                // 올바른 순서: category, cardTitle, cardIndex, pluginId, pluginConfig, displayOrder
                console.log('Saving card setting with correct parameters:', {
                    category: category,
                    cardTitle: cardTitle,
                    cardIndex: 0,
                    pluginId: pluginId,
                    pluginConfig: config
                });
                result = await this.saveCardSetting(category, cardTitle, 0, pluginId, config);
            } else {
                // 사용자 설정 저장 - 플러그인 이름을 setting_name으로 사용
                const settingName = config.plugin_name.replace(/\s+/g, '_').toLowerCase();
                result = await this.saveUserSetting(pluginId, settingName, config, category);
            }
            
            if (result.success) {
                // UI 새로고침
                const container = document.querySelector('.plugin-settings-ui').parentElement;
                container.innerHTML = '';
                this.createPluginSettingsUI(container, category, cardTitle);
                
                const message = isExisting ? 
                    `플러그인 "${config.plugin_name}" 설정이 수정되었습니다.` : 
                    `플러그인 "${config.plugin_name}"이 추가되었습니다.`;
                alert(message);
            } else {
                alert('설정 저장에 실패했습니다: ' + result.error);
            }
        } catch (error) {
            console.error('설정 저장 실패:', error);
            alert('설정 저장 중 오류가 발생했습니다.');
        }
    }
    
    /**
     * 플러그인 실행
     */
    async executePlugin(pluginId, config, context = {}) {
        try {
            const plugin = this.getPluginType(pluginId);
            if (!plugin) {
                throw new Error('플러그인을 찾을 수 없습니다.');
            }
            
            // 실행 전 이벤트
            this.dispatchEvent('beforePluginExecute', {
                pluginId, config, context
            });
            
            switch (pluginId) {
                case 'internal_link':
                    this.executeInternalLink(config);
                    break;
                case 'external_link':
                    this.executeExternalLink(config);
                    break;
                case 'send_message':
                    this.executeSendMessage(config, context);
                    break;
                case 'agent':
                    await this.executeAgent(config, context);
                    break;
                default:
                    throw new Error('지원하지 않는 플러그인입니다.');
            }
            
            // 사용 통계 업데이트
            await this.updateUsageStats(
                pluginId,
                context.category || null,
                context.cardTitle || null,
                { config, timestamp: Date.now() }
            );
            
            // 실행 후 이벤트
            this.dispatchEvent('afterPluginExecute', {
                pluginId, config, context, success: true
            });
            
        } catch (error) {
            console.error('플러그인 실행 실패:', error);
            
            // 실행 실패 이벤트
            this.dispatchEvent('afterPluginExecute', {
                pluginId, config, context, success: false, error: error.message
            });
            
            throw error;
        }
    }
    
    /**
     * 내부 링크 실행
     */
    executeInternalLink(config) {
        const url = config.internal_url;
        const openNewTab = config.open_new_tab;
        
        if (openNewTab) {
            window.open(url, '_blank');
        } else {
            window.location.href = url;
        }
    }
    
    /**
     * 외부 링크 실행
     */
    executeExternalLink(config) {
        const url = config.external_url;
        const openNewTab = config.open_new_tab;
        
        if (openNewTab) {
            window.open(url, '_blank');
        } else {
            window.location.href = url;
        }
    }
    
    /**
     * 메시지 발송 실행
     */
    executeSendMessage(config, context) {
        const messageContent = config.message_content;
        const messageType = config.message_type || 'info';
        
        // 메시지 표시 (실제 구현에서는 UI에 맞게 수정)
        this.showMessage(messageContent, messageType);
    }
    
    /**
     * 에이전트 실행
     */
    async executeAgent(config, context) {
        const agentType = config.agent_type;
        const agentCode = config.agent_code;
        const agentUrl = config.agent_url;
        const agentPrompt = config.agent_prompt;
        const agentConfig = config.agent_config || {};
        
        try {
            switch (agentType) {
                case 'php':
                    // PHP 에이전트 실행 (서버 API 호출)
                    const phpResult = await this.apiCall('executeAgent', {
                        type: 'php',
                        code: agentCode,
                        prompt: agentPrompt,
                        config: agentConfig,
                        context: context
                    });
                    
                    if (phpResult.output) {
                        this.showMessage(phpResult.output, 'success');
                    }
                    break;
                    
                case 'javascript':
                    // JavaScript 에이전트 실행 (클라이언트 사이드)
                    try {
                        const agentFunction = new Function('context', 'config', agentCode);
                        const result = await agentFunction(context, agentConfig);
                        
                        if (result && typeof result === 'string') {
                            this.showMessage(result, 'success');
                        }
                    } catch (jsError) {
                        console.error('JavaScript 에이전트 실행 오류:', jsError);
                        this.showMessage('JavaScript 에이전트 실행 중 오류가 발생했습니다.', 'error');
                    }
                    break;
                    
                case 'api':
                    // API 에이전트 실행
                    if (!agentUrl) {
                        throw new Error('API URL이 설정되지 않았습니다.');
                    }
                    
                    const apiResponse = await fetch(agentUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            prompt: agentPrompt,
                            config: agentConfig,
                            context: context
                        })
                    });
                    
                    if (!apiResponse.ok) {
                        throw new Error(`API 호출 실패: ${apiResponse.status}`);
                    }
                    
                    const apiResult = await apiResponse.json();
                    if (apiResult.message) {
                        this.showMessage(apiResult.message, 'success');
                    }
                    break;
                    
                case 'custom':
                    // 사용자 정의 에이전트
                    this.dispatchEvent('executeCustomAgent', {
                        config: config,
                        context: context
                    });
                    break;
                    
                default:
                    throw new Error(`지원하지 않는 에이전트 타입: ${agentType}`);
            }
        } catch (error) {
            console.error('에이전트 실행 오류:', error);
            this.showMessage(`에이전트 실행 실패: ${error.message}`, 'error');
            throw error;
        }
    }
    
    /**
     * 메시지 표시
     */
    showMessage(message, type = 'info') {
        // 간단한 알림 구현 (실제 구현에서는 UI에 맞게 수정)
        const messageContainer = document.createElement('div');
        messageContainer.className = `message-notification message-${type}`;
        messageContainer.textContent = message;
        
        // 스타일 적용
        messageContainer.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            border-radius: 5px;
            z-index: 9999;
            animation: slideIn 0.3s ease;
        `;
        
        // 타입별 색상
        const colors = {
            info: '#007bff',
            warning: '#ffc107',
            success: '#28a745',
            error: '#dc3545'
        };
        
        messageContainer.style.backgroundColor = colors[type] || colors.info;
        messageContainer.style.color = 'white';
        
        document.body.appendChild(messageContainer);
        
        // 3초 후 자동 제거
        setTimeout(() => {
            messageContainer.remove();
        }, 3000);
    }
    
    /**
     * 이벤트 리스너 등록
     */
    addEventListener(eventName, callback) {
        if (!this.events[eventName]) {
            this.events[eventName] = [];
        }
        this.events[eventName].push(callback);
    }
    
    /**
     * 이벤트 리스너 제거
     */
    removeEventListener(eventName, callback) {
        if (this.events[eventName]) {
            this.events[eventName] = this.events[eventName].filter(cb => cb !== callback);
        }
    }
    
    /**
     * 이벤트 발생
     */
    dispatchEvent(eventName, data) {
        if (this.events[eventName]) {
            this.events[eventName].forEach(callback => {
                try {
                    callback(data);
                } catch (error) {
                    console.error(`Error in event listener for ${eventName}:`, error);
                }
            });
        }
    }
    
    /**
     * 플러그인 변경 히스토리 조회
     */
    async getSettingHistory(pluginId = null, limit = 50) {
        try {
            const result = await this.apiCall('get_setting_history', {
                plugin_id: pluginId,
                limit: limit
            });
            
            return result.data || [];
        } catch (error) {
            console.error('설정 히스토리 조회 실패:', error);
            throw error;
        }
    }
    
    /**
     * 플러그인 통계 업데이트
     */
    async updateUsageStats(pluginId, category = null, cardTitle = null, executionData = null) {
        try {
            const result = await this.apiCall('update_usage_stats', {
                plugin_id: pluginId,
                category: category,
                card_title: cardTitle,
                execution_data: executionData
            });
            
            return result;
        } catch (error) {
            console.error('사용 통계 업데이트 실패:', error);
            throw error;
        }
    }
}

// 전역 인스턴스 생성
window.ktmPluginSettings = new KTMPluginSettingsClient();

// 사용 예시
/*
// 플러그인 설정 UI 생성
const container = document.getElementById('plugin-settings-container');
window.ktmPluginSettings.createPluginSettingsUI(container, 'weekly');

// 플러그인 실행
window.ktmPluginSettings.executePlugin('internal_link', {
    internal_url: '/path/to/page',
    open_new_tab: true
});
*/ 