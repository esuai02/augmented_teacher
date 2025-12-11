/**
 * Step-by-Step TTS Player JavaScript
 * File: step_player.js
 * Location: /mnt/c/1 Project/augmented_teacher/alt42/teachingsupport/js/step_player.js
 *
 * Dependencies:
 * - step_player_modal.php (HTML structure)
 * - step_player_modal.css (styles)
 * - get_section_data.php (backend API)
 *
 * Features:
 * - AJAX section data loading
 * - Audio playback control with speed adjustment
 * - Circular navigation with visual indicators
 * - Keyboard shortcuts (Space, Escape, Arrow keys)
 * - Focus trap for accessibility
 * - Auto-play between sections
 * - WCAG 2.1 AA compliant
 */

var StepPlayer = (function() {
    'use strict';

    // ========================================
    // 1. PRIVATE STATE MANAGEMENT
    // ========================================
    var state = {
        sections: [],           // Audio section data
        textSections: [],       // Text content for each section
        currentIndex: 0,        // Current section index (0-based)
        isPlaying: false,       // Playback state
        autoPlay: false,        // Auto-advance enabled
        playbackRate: 1.0,      // Playback speed
        totalSections: 0,       // Total number of sections
        contentsId: null,       // Current content ID
        lastFocusedElement: null // Element to return focus on close
    };

    // ========================================
    // 2. DOM ELEMENT CACHING
    // ========================================
    var elements = {
        modal: null,
        audioElement: null,
        playPauseBtn: null,
        speedControl: null,
        autoPlayCheckbox: null,
        circleNavContainer: null,
        prevBtn: null,
        nextBtn: null,
        textContent: null,
        progressFill: null,
        timeCurrent: null,
        timeTotal: null,
        srAnnouncements: null,
        closeBtn: null,
        focusableElements: []
    };

    // ========================================
    // 3. INITIALIZATION
    // ========================================
    function init() {
        cacheElements();

        if (!elements.modal) {
            console.error('[step_player.js:init] Modal element not found');
            return;
        }

        attachEventListeners();
        setupKeyboardShortcuts();
        console.log('[step_player.js:init] StepPlayer initialized successfully');
    }

    function cacheElements() {
        elements.modal = document.querySelector('[data-step-modal]');
        elements.audioElement = document.querySelector('[data-audio-element]');
        elements.playPauseBtn = document.querySelector('[data-play-pause]');
        elements.speedControl = document.querySelector('[data-speed-control]');
        elements.autoPlayCheckbox = document.querySelector('[data-auto-play]');
        elements.circleNavContainer = document.querySelector('[data-circle-nav]');
        elements.prevBtn = document.querySelector('[data-prev-step]');
        elements.nextBtn = document.querySelector('[data-next-step]');
        elements.textContent = document.querySelector('[data-text-content]');
        elements.progressFill = document.querySelector('[data-progress-fill]');
        elements.timeCurrent = document.querySelector('[data-time-current]');
        elements.timeTotal = document.querySelector('[data-time-total]');
        elements.srAnnouncements = document.querySelector('[data-sr-announcements]');
        elements.closeBtn = document.querySelector('[data-close-modal]');
    }

    // ========================================
    // 4. EVENT LISTENERS SETUP
    // ========================================
    function attachEventListeners() {
        // Play/Pause button
        if (elements.playPauseBtn) {
            elements.playPauseBtn.addEventListener('click', togglePlayPause);
        }

        // Navigation buttons
        if (elements.prevBtn) {
            elements.prevBtn.addEventListener('click', loadPreviousSection);
        }
        if (elements.nextBtn) {
            elements.nextBtn.addEventListener('click', loadNextSection);
        }

        // Speed control
        if (elements.speedControl) {
            elements.speedControl.addEventListener('change', handleSpeedChange);
        }

        // Auto-play checkbox
        if (elements.autoPlayCheckbox) {
            elements.autoPlayCheckbox.addEventListener('change', handleAutoPlayToggle);
        }

        // Close button
        if (elements.closeBtn) {
            elements.closeBtn.addEventListener('click', close);
        }

        // Audio events
        if (elements.audioElement) {
            elements.audioElement.addEventListener('play', handleAudioPlay);
            elements.audioElement.addEventListener('pause', handleAudioPause);
            elements.audioElement.addEventListener('ended', handleAudioEnded);
            elements.audioElement.addEventListener('timeupdate', handleTimeUpdate);
            elements.audioElement.addEventListener('error', handleAudioError);
            elements.audioElement.addEventListener('loadedmetadata', handleAudioLoaded);
        }

        // Modal backdrop click to close
        if (elements.modal) {
            elements.modal.addEventListener('click', function(e) {
                if (e.target === elements.modal) {
                    close();
                }
            });
        }
    }

    // ========================================
    // 5. API RESPONSE NORMALIZATION HELPERS
    // ========================================
    function resolveSectionPayload(response) {
        if (!response || typeof response !== 'object') {
            return null;
        }

        if (Array.isArray(response.sections) && Array.isArray(response.text_sections)) {
            return response;
        }

        if (response.data) {
            return resolveSectionPayload(response.data);
        }

        if (response.payload) {
            return resolveSectionPayload(response.payload);
        }

        return null;
    }

    function buildAbsoluteUrl(path) {
        if (!path || typeof path !== 'string') {
            return '';
        }

        var trimmed = path.trim();

        if (!trimmed) {
            return '';
        }

        if (/^https?:\/\//i.test(trimmed)) {
            return trimmed;
        }

        if (trimmed.startsWith('//')) {
            return window.location.protocol + trimmed;
        }

        try {
            return new URL(trimmed, window.location.href).href;
        } catch (error) {
            console.warn('[step_player.js:buildAbsoluteUrl:' + getLineNumber() + '] URL normalization failed for "' + trimmed + '":', error);
            return trimmed;
        }
    }

    function normalizeAudioSource(entry) {
        if (typeof entry === 'string') {
            return buildAbsoluteUrl(entry);
        }

        if (entry && typeof entry === 'object') {
            if (typeof entry.url === 'string') {
                return buildAbsoluteUrl(entry.url);
            }
            if (typeof entry.src === 'string') {
                return buildAbsoluteUrl(entry.src);
            }
            if (typeof entry.path === 'string') {
                return buildAbsoluteUrl(entry.path);
            }
        }

        return '';
    }

    // ========================================
    // 6. AJAX SECTION DATA FETCH
    // ========================================
    function fetchSectionData(contentsid) {
        // ktm_teaching_interactions 테이블에서 데이터 가져오기
        var url = '/moodle/local/augmented_teacher/alt42/teachingsupport/get_interaction_data.php?contentsid=' + encodeURIComponent(contentsid) + '&format=section';
        console.log('[step_player.js:fetchSectionData] Fetching from URL:', url);

        announceToScreenReader('데이터를 불러오는 중...');

        var xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);

        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    console.log('[step_player.js:fetchSectionData] Raw response:', response);
                    
                    var payload = resolveSectionPayload(response);
                    console.log('[step_player.js:fetchSectionData] Resolved payload:', payload);

                    if (payload && Array.isArray(payload.sections) && Array.isArray(payload.text_sections)) {
                        console.log('[step_player.js:fetchSectionData] Sections count:', payload.sections.length);
                        console.log('[step_player.js:fetchSectionData] First section:', payload.sections[0]);
                        
                        var normalizedSections = payload.sections.map(function(section, index) {
                            var normalized = normalizeAudioSource(section);
                            console.log('[step_player.js:fetchSectionData] Section ' + index + ':', section, '->', normalized);
                            return normalized;
                        });
                        
                        var hasInvalidSource = normalizedSections.some(function(src) {
                            return !src;
                        });

                        if (hasInvalidSource) {
                            console.error('[step_player.js:fetchSectionData] Invalid sources:', normalizedSections);
                            handleError('[step_player.js:fetchSectionData:' + getLineNumber() + '] Invalid audio source in payload', '오디오 데이터가 손상되었습니다.');
                            return;
                        }

                        if (normalizedSections.length !== payload.text_sections.length) {
                            console.error('[step_player.js:fetchSectionData] Length mismatch - sections:', normalizedSections.length, 'text_sections:', payload.text_sections.length);
                            handleError('[step_player.js:fetchSectionData:' + getLineNumber() + '] Section/text length mismatch', '오디오 데이터와 텍스트 데이터가 일치하지 않습니다.');
                            return;
                        }

                        state.sections = normalizedSections;
                        state.textSections = payload.text_sections;
                        state.totalSections = payload.total_sections || normalizedSections.length;
                        state.currentIndex = 0;

                        console.log('[step_player.js:fetchSectionData] Successfully loaded', state.totalSections, 'sections');
                        buildCircularNavigation();
                        loadSection(0);

                        announceToScreenReader('총 ' + state.totalSections + '개 섹션 로드 완료');
                    } else {
                        console.error('[step_player.js:fetchSectionData] Invalid payload structure:', payload);
                        handleError('[step_player.js:fetchSectionData:' + getLineNumber() + '] Invalid response structure', '잘못된 데이터 형식입니다.');
                    }
                } catch (error) {
                    console.error('[step_player.js:fetchSectionData] Parse error:', error, 'Response text:', xhr.responseText);
                    handleError('[step_player.js:fetchSectionData:' + getLineNumber() + '] JSON parse error: ' + error.message, '데이터 파싱 오류가 발생했습니다.');
                }
            } else {
                console.error('[step_player.js:fetchSectionData] HTTP error:', xhr.status, xhr.responseText);
                handleError('[step_player.js:fetchSectionData:' + getLineNumber() + '] HTTP error: ' + xhr.status, '데이터를 불러오지 못했습니다.');
            }
        };

        xhr.onerror = function() {
            handleError('[step_player.js:fetchSectionData:' + getLineNumber() + '] Network error', '네트워크 오류가 발생했습니다.');
        };

        xhr.send();
    }

    // ========================================
    // 6. CIRCULAR NAVIGATION BUILD
    // ========================================
    function buildCircularNavigation() {
        if (!elements.circleNavContainer) {
            return;
        }

        // Clear existing buttons
        elements.circleNavContainer.innerHTML = '';

        // Create button for each section
        for (var i = 0; i < state.totalSections; i++) {
            var button = document.createElement('button');
            button.className = 'step-modal__circle';
            button.type = 'button';
            button.setAttribute('role', 'tab');
            button.setAttribute('aria-label', '섹션 ' + (i + 1));
            button.setAttribute('data-section-index', i);

            if (i === 0) {
                button.classList.add('step-modal__circle--active');
                button.setAttribute('aria-selected', 'true');
            } else {
                button.setAttribute('aria-selected', 'false');
            }

            // Click handler
            button.addEventListener('click', function(index) {
                return function() {
                    loadSection(index);
                };
            }(i));

            elements.circleNavContainer.appendChild(button);
        }
    }

    function updateCircularNavigation() {
        if (!elements.circleNavContainer) {
            return;
        }

        var buttons = elements.circleNavContainer.querySelectorAll('.step-modal__circle');
        buttons.forEach(function(button, index) {
            if (index === state.currentIndex) {
                button.classList.add('step-modal__circle--active');
                button.setAttribute('aria-selected', 'true');
            } else {
                button.classList.remove('step-modal__circle--active');
                button.setAttribute('aria-selected', 'false');
            }
        });
    }

    // ========================================
    // 7. SECTION LOADING
    // ========================================
    function loadSection(index) {
        if (index < 0 || index >= state.totalSections) {
            console.warn('[step_player.js:loadSection:' + getLineNumber() + '] Invalid section index: ' + index);
            return;
        }

        state.currentIndex = index;

        // Update audio source
        if (elements.audioElement) {
            var audioUrl = state.sections[index];

            if (!audioUrl) {
                console.error('[step_player.js:loadSection] Missing audio URL for index', index, 'Available sections:', state.sections);
                handleError('[step_player.js:loadSection:' + getLineNumber() + '] Missing audio URL for index ' + index, '오디오 소스를 불러올 수 없습니다.');
                return;
            }

            console.log('[step_player.js:loadSection] Loading section', index, 'with URL:', audioUrl);
            
            // 기존 source 태그 제거
            var existingSources = elements.audioElement.querySelectorAll('source');
            existingSources.forEach(function(source) {
                source.remove();
            });
            
            // MP3 파일에 대한 source 태그 추가 (MIME 타입 명시)
            var sourceElement = document.createElement('source');
            sourceElement.src = audioUrl;
            sourceElement.type = 'audio/mpeg'; // MP3 MIME 타입 명시
            
            // 기존 src 속성 제거하고 source 태그 사용
            elements.audioElement.removeAttribute('src');
            elements.audioElement.appendChild(sourceElement);
            
            // 오디오 로드
            elements.audioElement.load();
            
            // 로드 완료 대기 후 재생 가능 여부 확인
            var loadTimeout = setTimeout(function() {
                if (elements.audioElement.readyState === 0) {
                    console.error('[step_player.js:loadSection] Audio load timeout for URL:', audioUrl);
                    handleError('[step_player.js:loadSection:' + getLineNumber() + '] 오디오 파일을 로드할 수 없습니다. 파일이 존재하는지 확인하세요.', '오디오 파일 로드 시간 초과: ' + audioUrl);
                }
            }, 10000); // 10초 타임아웃
            
            elements.audioElement.addEventListener('loadedmetadata', function onLoadedMetadata() {
                clearTimeout(loadTimeout);
                elements.audioElement.removeEventListener('loadedmetadata', onLoadedMetadata);
                console.log('[step_player.js:loadSection] Audio metadata loaded successfully for section', index);
            }, { once: true });
        }

        // Update text content
        if (elements.textContent && state.textSections[index]) {
            elements.textContent.textContent = state.textSections[index];
        }

        // Update navigation buttons
        updateNavigationButtons();

        // Update circular navigation
        updateCircularNavigation();

        // Reset progress
        updateProgress(0);

        // Announce to screen reader
        announceToScreenReader('섹션 ' + (index + 1) + '/' + state.totalSections + ' 로드됨');
    }

    // ========================================
    // 8. AUDIO PLAYBACK CONTROL
    // ========================================
    function togglePlayPause() {
        if (!elements.audioElement) {
            return;
        }

        if (state.isPlaying) {
            elements.audioElement.pause();
        } else {
            elements.audioElement.play().catch(function(error) {
                handleError('[step_player.js:togglePlayPause:' + getLineNumber() + '] Play error: ' + error.message, '재생 오류가 발생했습니다.');
            });
        }
    }

    function handleAudioPlay() {
        state.isPlaying = true;
        updatePlayPauseButton(true);
        announceToScreenReader('재생 중');
    }

    function handleAudioPause() {
        state.isPlaying = false;
        updatePlayPauseButton(false);
        announceToScreenReader('일시정지됨');
    }

    function handleAudioEnded() {
        state.isPlaying = false;
        updatePlayPauseButton(false);

        // Auto-advance if enabled and not last section
        if (state.autoPlay && state.currentIndex < state.totalSections - 1) {
            loadNextSection();
            // Auto-play next section
            setTimeout(function() {
                if (elements.audioElement) {
                    elements.audioElement.play().catch(function(error) {
                        console.warn('[step_player.js:handleAudioEnded:' + getLineNumber() + '] Auto-play failed: ' + error.message);
                    });
                }
            }, 500);
        } else {
            announceToScreenReader('섹션 재생 완료');
        }
    }

    function handleAudioError(e) {
        var errorMessage = '오디오 로드 오류';
        var errorCode = null;
        var audioSrc = '';
        var sourceSrc = '';
        
        if (e.target && e.target.error) {
            errorCode = e.target.error.code;
            audioSrc = e.target.src || '';
            
            // source 태그에서 URL 가져오기
            var sourceElement = e.target.querySelector('source');
            if (sourceElement) {
                sourceSrc = sourceElement.src || '';
            }
            
            var actualUrl = sourceSrc || audioSrc;
            
            switch(errorCode) {
                case e.target.error.MEDIA_ERR_ABORTED:
                    errorMessage = '오디오 로드가 중단되었습니다';
                    break;
                case e.target.error.MEDIA_ERR_NETWORK:
                    errorMessage = '네트워크 오류로 오디오를 로드할 수 없습니다. 파일 URL을 확인하세요.';
                    break;
                case e.target.error.MEDIA_ERR_DECODE:
                    errorMessage = '오디오 디코딩 오류가 발생했습니다. 파일이 손상되었거나 지원되지 않는 형식일 수 있습니다.';
                    break;
                case e.target.error.MEDIA_ERR_SRC_NOT_SUPPORTED:
                    errorMessage = '지원되지 않는 오디오 형식입니다. MP3 파일이 올바르게 생성되었는지 확인하세요.';
                    break;
            }
            
            // 추가 진단 정보
            errorMessage += '\n\n진단 정보:';
            errorMessage += '\n- 오디오 URL: ' + actualUrl;
            errorMessage += '\n- 현재 섹션: ' + (state.currentIndex + 1) + '/' + state.totalSections;
            errorMessage += '\n- 오류 코드: ' + errorCode;
            errorMessage += '\n- 파일이 서버에 존재하는지 확인하세요.';
        }
        
        console.error('[step_player.js:handleAudioError] Error code:', errorCode, 'Source:', audioSrc, 'Source element:', sourceSrc, 'Current index:', state.currentIndex);
        console.error('[step_player.js:handleAudioError] All sections:', state.sections);
        console.error('[step_player.js:handleAudioError] Audio element readyState:', e.target ? e.target.readyState : 'N/A');
        console.error('[step_player.js:handleAudioError] Audio element networkState:', e.target ? e.target.networkState : 'N/A');
        
        handleError('[step_player.js:handleAudioError:' + getLineNumber() + '] Audio error', errorMessage);
    }

    function handleAudioLoaded() {
        if (elements.audioElement && elements.timeTotal) {
            var duration = elements.audioElement.duration;
            elements.timeTotal.textContent = formatTime(duration);
        }
    }

    function handleTimeUpdate() {
        if (!elements.audioElement) {
            return;
        }

        var currentTime = elements.audioElement.currentTime;
        var duration = elements.audioElement.duration;

        // Update current time display
        if (elements.timeCurrent) {
            elements.timeCurrent.textContent = formatTime(currentTime);
        }

        // Update progress bar
        if (duration > 0) {
            var progress = (currentTime / duration) * 100;
            updateProgress(progress);
        }
    }

    function updatePlayPauseButton(isPlaying) {
        if (!elements.playPauseBtn) {
            return;
        }

        var icon = elements.playPauseBtn.querySelector('.step-modal__btn-icon');
        var text = elements.playPauseBtn.querySelector('.step-modal__btn-text');

        if (isPlaying) {
            elements.playPauseBtn.classList.add('step-modal__btn--playing');
            elements.playPauseBtn.setAttribute('aria-label', '일시정지');
            if (icon) {
                icon.textContent = '⏸';
            }
            if (text) {
                text.textContent = '일시정지';
            }
        } else {
            elements.playPauseBtn.classList.remove('step-modal__btn--playing');
            elements.playPauseBtn.setAttribute('aria-label', '재생');
            if (icon) {
                icon.textContent = '▶';
            }
            if (text) {
                text.textContent = '재생';
            }
        }
    }

    function updateProgress(percentage) {
        if (elements.progressFill) {
            elements.progressFill.style.width = Math.min(100, Math.max(0, percentage)) + '%';
        }
    }

    function formatTime(seconds) {
        if (isNaN(seconds) || !isFinite(seconds)) {
            return '00:00';
        }

        var minutes = Math.floor(seconds / 60);
        var secs = Math.floor(seconds % 60);

        return (minutes < 10 ? '0' : '') + minutes + ':' + (secs < 10 ? '0' : '') + secs;
    }

    // ========================================
    // 9. SPEED CONTROL
    // ========================================
    function handleSpeedChange() {
        if (!elements.speedControl || !elements.audioElement) {
            return;
        }

        var speed = parseFloat(elements.speedControl.value);
        if (!isNaN(speed)) {
            state.playbackRate = speed;
            elements.audioElement.playbackRate = speed;
            announceToScreenReader('재생 속도 ' + speed + '배로 변경됨');
        }
    }

    // ========================================
    // 10. AUTO-PLAY CONTROL
    // ========================================
    function handleAutoPlayToggle() {
        if (!elements.autoPlayCheckbox) {
            return;
        }

        state.autoPlay = elements.autoPlayCheckbox.checked;
        announceToScreenReader('자동 재생 ' + (state.autoPlay ? '활성화됨' : '비활성화됨'));
    }

    // ========================================
    // 11. STEP NAVIGATION
    // ========================================
    function loadPreviousSection() {
        if (state.currentIndex > 0) {
            loadSection(state.currentIndex - 1);
        }
    }

    function loadNextSection() {
        if (state.currentIndex < state.totalSections - 1) {
            loadSection(state.currentIndex + 1);
        }
    }

    function updateNavigationButtons() {
        // Update previous button
        if (elements.prevBtn) {
            elements.prevBtn.disabled = state.currentIndex === 0;
        }

        // Update next button
        if (elements.nextBtn) {
            elements.nextBtn.disabled = state.currentIndex >= state.totalSections - 1;
        }
    }

    // ========================================
    // 12. KEYBOARD SHORTCUTS
    // ========================================
    function setupKeyboardShortcuts() {
        document.addEventListener('keydown', handleKeyDown);
    }

    function handleKeyDown(e) {
        // Only handle keyboard events when modal is active
        if (!elements.modal || !elements.modal.classList.contains('active')) {
            return;
        }

        switch(e.key) {
            case ' ': // Space - play/pause
            case 'Spacebar': // IE/Edge
                e.preventDefault();
                togglePlayPause();
                break;

            case 'Escape':
            case 'Esc': // IE/Edge
                e.preventDefault();
                close();
                break;

            case 'ArrowLeft':
            case 'Left': // IE/Edge
                e.preventDefault();
                loadPreviousSection();
                break;

            case 'ArrowRight':
            case 'Right': // IE/Edge
                e.preventDefault();
                loadNextSection();
                break;
        }
    }

    // ========================================
    // 13. FOCUS TRAP
    // ========================================
    function setupFocusTrap() {
        if (!elements.modal) {
            return;
        }

        // Get all focusable elements within modal
        var focusableSelectors = 'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';
        elements.focusableElements = Array.from(elements.modal.querySelectorAll(focusableSelectors));

        if (elements.focusableElements.length === 0) {
            return;
        }

        // Focus first element
        elements.focusableElements[0].focus();

        // Add tab trap listener
        elements.modal.addEventListener('keydown', handleFocusTrap);
    }

    function handleFocusTrap(e) {
        if (e.key !== 'Tab' && e.key !== 'tab') {
            return;
        }

        if (elements.focusableElements.length === 0) {
            return;
        }

        var firstElement = elements.focusableElements[0];
        var lastElement = elements.focusableElements[elements.focusableElements.length - 1];

        // Shift + Tab (backwards)
        if (e.shiftKey) {
            if (document.activeElement === firstElement) {
                e.preventDefault();
                lastElement.focus();
            }
        } else {
            // Tab (forwards)
            if (document.activeElement === lastElement) {
                e.preventDefault();
                firstElement.focus();
            }
        }
    }

    function removeFocusTrap() {
        if (elements.modal) {
            elements.modal.removeEventListener('keydown', handleFocusTrap);
        }
    }

    // ========================================
    // 14. SCREEN READER SUPPORT
    // ========================================
    function announceToScreenReader(message) {
        if (!elements.srAnnouncements) {
            return;
        }

        // Clear previous announcement
        elements.srAnnouncements.textContent = '';

        // Trigger reflow to ensure announcement is read
        setTimeout(function() {
            elements.srAnnouncements.textContent = message;
        }, 100);
    }

    // ========================================
    // 15. ERROR HANDLING
    // ========================================
    function handleError(logMessage, userMessage) {
        console.error(logMessage);
        announceToScreenReader('오류: ' + userMessage);
        alert('오류: ' + userMessage);
        close();
    }

    function getLineNumber() {
        // Helper to simulate line numbers for error context
        var stack = new Error().stack;
        if (stack) {
            var match = stack.match(/step_player\.js:(\d+)/);
            if (match && match[1]) {
                return match[1];
            }
        }
        return 'unknown';
    }

    // ========================================
    // 16. MODAL STATE MANAGEMENT
    // ========================================
    function open(contentsid) {
        if (!contentsid) {
            handleError('[step_player.js:open:' + getLineNumber() + '] No contentsid provided', '콘텐츠 ID가 제공되지 않았습니다.');
            return;
        }

        if (!elements.modal) {
            handleError('[step_player.js:open:' + getLineNumber() + '] Modal element not found', '모달 요소를 찾을 수 없습니다.');
            return;
        }

        // Store last focused element
        state.lastFocusedElement = document.activeElement;

        // Store contents ID
        state.contentsId = contentsid;

        // Show modal - 인라인 스타일 제거하고 active 클래스 추가
        if (elements.modal) {
            elements.modal.style.display = ''; // 인라인 display 스타일 제거
        elements.modal.classList.add('active');
        elements.modal.setAttribute('aria-hidden', 'false');
            console.log('[step_player.js:open] Modal displayed, active class added');
        } else {
            console.error('[step_player.js:open] Modal element not found');
            return;
        }

        // Fetch section data
        fetchSectionData(contentsid);

        // Setup focus trap
        setupFocusTrap();

        // Prevent body scroll
        document.body.style.overflow = 'hidden';
    }

    function close() {
        if (!elements.modal) {
            return;
        }

        // Stop audio
        if (elements.audioElement) {
            elements.audioElement.pause();
            // source 태그 제거
            var existingSources = elements.audioElement.querySelectorAll('source');
            existingSources.forEach(function(source) {
                source.remove();
            });
            elements.audioElement.removeAttribute('src');
        }

        // Reset state
        state.isPlaying = false;
        state.currentIndex = 0;
        state.sections = [];
        state.textSections = [];
        state.totalSections = 0;
        state.contentsId = null;

        // Clear UI
        if (elements.textContent) {
            elements.textContent.textContent = '';
        }
        if (elements.circleNavContainer) {
            elements.circleNavContainer.innerHTML = '';
        }
        updateProgress(0);
        if (elements.timeCurrent) {
            elements.timeCurrent.textContent = '00:00';
        }
        if (elements.timeTotal) {
            elements.timeTotal.textContent = '00:00';
        }

        // Hide modal - 인라인 스타일로 숨기고 active 클래스 제거
        elements.modal.style.display = 'none';
        elements.modal.classList.remove('active');
        elements.modal.setAttribute('aria-hidden', 'true');

        // Remove focus trap
        removeFocusTrap();

        // Restore body scroll
        document.body.style.overflow = '';

        // Return focus to trigger button
        if (state.lastFocusedElement) {
            state.lastFocusedElement.focus();
            state.lastFocusedElement = null;
        }

        announceToScreenReader('모달 닫힘');
    }

    // ========================================
    // 17. PUBLIC API
    // ========================================
    return {
        init: init,
        open: open,
        close: close
    };
})();

// ========================================
// GLOBAL INITIALIZATION
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    StepPlayer.init();
});
