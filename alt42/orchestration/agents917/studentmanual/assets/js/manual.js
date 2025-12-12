/**
 * Student Manual System - JavaScript
 * File: alt42/orchestration/agents/studentmanual/assets/js/manual.js
 *
 * 검색, 필터링, 모달 표시 기능
 */

(function() {
    'use strict';

    // 전역 변수
    let allManualItems = window.manualData || [];
    let currentFilter = 'all';
    let currentSearch = '';

    // DOM 요소
    const searchInput = document.getElementById('search-input');
    const searchBtn = document.getElementById('search-btn');
    const filterBtns = document.querySelectorAll('.filter-btn');
    const manualGrid = document.getElementById('manual-grid');
    const noResults = document.getElementById('no-results');
    const detailModal = document.getElementById('detail-modal');
    const modalBody = document.getElementById('modal-body');
    const modalClose = document.querySelector('.modal-close');
    const imageModal = document.getElementById('image-modal');
    const modalImage = document.getElementById('modal-image');
    const imageModalClose = document.querySelector('.image-modal-close');

    // 초기화
    function init() {
        setupEventListeners();
        renderManualItems(allManualItems);
    }

    // 이벤트 리스너 설정
    function setupEventListeners() {
        // 검색
        if (searchInput) {
            searchInput.addEventListener('input', handleSearch);
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    handleSearch();
                }
            });
        }

        if (searchBtn) {
            searchBtn.addEventListener('click', handleSearch);
        }

        // 필터
        filterBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                // 모든 필터 버튼에서 active 제거
                filterBtns.forEach(b => b.classList.remove('active'));
                // 클릭한 버튼에 active 추가
                this.classList.add('active');
                currentFilter = this.getAttribute('data-agent');
                filterAndRender();
            });
        });

        // 모달 닫기
        if (modalClose) {
            modalClose.addEventListener('click', closeDetailModal);
        }

        if (imageModalClose) {
            imageModalClose.addEventListener('click', closeImageModal);
        }

        // 모달 외부 클릭 시 닫기
        if (detailModal) {
            detailModal.addEventListener('click', function(e) {
                if (e.target === detailModal) {
                    closeDetailModal();
                }
            });
        }

        if (imageModal) {
            imageModal.addEventListener('click', function(e) {
                if (e.target === imageModal) {
                    closeImageModal();
                }
            });
        }

        // 상세 보기 버튼
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('view-detail-btn')) {
                const itemId = parseInt(e.target.getAttribute('data-item-id'));
                showDetailModal(itemId);
            }

            // 이미지 클릭 시 확대
            if (e.target.tagName === 'IMG' && e.target.closest('#modal-body')) {
                showImageModal(e.target.src);
            }
        });
    }

    // 검색 처리
    function handleSearch() {
        currentSearch = searchInput.value.trim().toLowerCase();
        filterAndRender();
    }

    // 필터 및 검색 적용 후 렌더링
    function filterAndRender() {
        let filtered = allManualItems;

        // 필터 적용
        if (currentFilter !== 'all') {
            filtered = filtered.filter(item => item.agent_id === currentFilter);
        }

        // 검색 적용
        if (currentSearch) {
            filtered = filtered.filter(item => {
                const titleMatch = item.title.toLowerCase().includes(currentSearch);
                const descMatch = item.description.toLowerCase().includes(currentSearch);
                return titleMatch || descMatch;
            });
        }

        renderManualItems(filtered);

        // 검색 결과 없음 메시지
        if (filtered.length === 0) {
            noResults.classList.remove('hidden');
        } else {
            noResults.classList.add('hidden');
        }
    }

    // 메뉴얼 항목 렌더링
    function renderManualItems(items) {
        if (!manualGrid) return;

        if (items.length === 0) {
            manualGrid.innerHTML = '<div class="empty-state"><p>검색 결과가 없습니다.</p></div>';
            return;
        }

        manualGrid.innerHTML = items.map(item => createManualCard(item)).join('');
    }

    // 메뉴얼 카드 HTML 생성
    function createManualCard(item) {
        const agentName = window.agents[item.agent_id] || item.agent_id;
        const description = item.description.length > 100 
            ? item.description.substring(0, 100) + '...' 
            : item.description;

        // 컨텐츠 타입 배지 생성
        let contentBadges = '';
        if (item.contents && item.contents.length > 0) {
            const uniqueTypes = [...new Set(item.contents.map(c => c.content_type))];
            const typeNames = {
                'image': '🖼️ 이미지',
                'video': '🎥 동영상',
                'audio': '🎵 음성',
                'link': '🔗 링크'
            };
            contentBadges = uniqueTypes.map(type => 
                `<span class="content-type-badge" data-type="${type}">${typeNames[type] || type}</span>`
            ).join('');
        }

        return `
            <div class="manual-card" 
                 data-agent="${item.agent_id}" 
                 data-title="${item.title.toLowerCase()}"
                 data-description="${item.description.toLowerCase()}">
                <div class="card-header">
                    <span class="agent-badge">${agentName}</span>
                    <h3 class="card-title">${escapeHtml(item.title)}</h3>
                </div>
                <div class="card-body">
                    <p class="card-description">${escapeHtml(description)}</p>
                    ${contentBadges ? `<div class="card-contents-preview">${contentBadges}</div>` : ''}
                </div>
                <div class="card-footer">
                    <button class="view-detail-btn" data-item-id="${item.id}">자세히 보기</button>
                </div>
            </div>
        `;
    }

    // 상세 모달 표시
    function showDetailModal(itemId) {
        const item = allManualItems.find(i => i.id === itemId);
        if (!item) return;

        const agentName = window.agents[item.agent_id] || item.agent_id;
        
        let contentsHtml = '';
        if (item.contents && item.contents.length > 0) {
            contentsHtml = '<div class="contents-section"><h3>관련 컨텐츠</h3>';
            item.contents.forEach(content => {
                contentsHtml += createContentHtml(content);
            });
            contentsHtml += '</div>';
        }

        modalBody.innerHTML = `
            <span class="agent-badge">${agentName}</span>
            <h2>${escapeHtml(item.title)}</h2>
            <div class="description">${escapeHtml(item.description).replace(/\n/g, '<br>')}</div>
            ${contentsHtml}
        `;

        detailModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    // 컨텐츠 HTML 생성
    function createContentHtml(content) {
        let html = '<div class="content-item">';

        switch (content.content_type) {
            case 'image':
                if (content.file_path) {
                    const imageUrl = getContentUrl(content);
                    html += `<img src="${escapeHtml(imageUrl)}" alt="이미지" style="max-width: 100%; cursor: pointer;">`;
                } else if (content.external_url) {
                    html += `<a href="${escapeHtml(content.external_url)}" target="_blank" class="external-link">이미지 보기</a>`;
                }
                break;

            case 'video':
                if (content.file_path) {
                    const videoUrl = getContentUrl(content);
                    html += `<video controls style="width: 100%;"><source src="${escapeHtml(videoUrl)}" type="${escapeHtml(content.mime_type || 'video/mp4')}"></video>`;
                } else if (content.external_url) {
                    // YouTube 또는 Vimeo 링크 처리
                    const embedUrl = convertToEmbedUrl(content.external_url);
                    if (embedUrl) {
                        html += `<iframe width="100%" height="400" src="${escapeHtml(embedUrl)}" frameborder="0" allowfullscreen></iframe>`;
                    } else {
                        html += `<a href="${escapeHtml(content.external_url)}" target="_blank" class="external-link">동영상 보기</a>`;
                    }
                }
                break;

            case 'audio':
                if (content.file_path) {
                    const audioUrl = getContentUrl(content);
                    html += `<audio controls style="width: 100%;"><source src="${escapeHtml(audioUrl)}" type="${escapeHtml(content.mime_type || 'audio/mpeg')}"></audio>`;
                } else if (content.external_url) {
                    html += `<a href="${escapeHtml(content.external_url)}" target="_blank" class="external-link">음성 듣기</a>`;
                }
                break;

            case 'link':
                if (content.external_url) {
                    html += `<a href="${escapeHtml(content.external_url)}" target="_blank" class="external-link">링크 열기: ${escapeHtml(content.external_url)}</a>`;
                }
                break;
        }

        html += '</div>';
        return html;
    }

    // 컨텐츠 URL 생성
    function getContentUrl(content) {
        if (content.external_url) {
            return content.external_url;
        }
        if (content.file_path) {
            // 상대 경로를 절대 경로로 변환
            if (content.file_path.startsWith('http')) {
                return content.file_path;
            }
            // uploads 디렉토리 기준
            const baseUrl = window.location.origin + '/moodle/local/augmented_teacher/alt42/orchestration/agents/studentmanual/uploads/';
            return baseUrl + content.file_path;
        }
        return '';
    }

    // YouTube/Vimeo URL을 embed URL로 변환
    function convertToEmbedUrl(url) {
        if (!url) return null;

        // YouTube
        const youtubeRegex = /(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\n?#]+)/;
        const youtubeMatch = url.match(youtubeRegex);
        if (youtubeMatch) {
            return `https://www.youtube.com/embed/${youtubeMatch[1]}`;
        }

        // Vimeo
        const vimeoRegex = /vimeo\.com\/(\d+)/;
        const vimeoMatch = url.match(vimeoRegex);
        if (vimeoMatch) {
            return `https://player.vimeo.com/video/${vimeoMatch[1]}`;
        }

        return null;
    }

    // 이미지 모달 표시
    function showImageModal(imageSrc) {
        modalImage.src = imageSrc;
        imageModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    // 상세 모달 닫기
    function closeDetailModal() {
        detailModal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    // 이미지 모달 닫기
    function closeImageModal() {
        imageModal.classList.add('hidden');
        document.body.style.overflow = '';
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

