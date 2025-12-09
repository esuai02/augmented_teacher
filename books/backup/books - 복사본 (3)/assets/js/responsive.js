/**
 * Responsive Layout Handler
 * 모바일, 태블릿, 데스크톱 레이아웃 동적 제어
 */

(function(window, document) {
    'use strict';
    
    const ResponsiveHandler = {
        // 브레이크포인트 정의
        breakpoints: {
            mobile: 576,
            tablet: 768,
            desktop: 992,
            large: 1200,
            xlarge: 1920
        },
        
        // 현재 디바이스 타입
        currentDevice: null,
        
        // 사이드바 상태
        sidebarOpen: false,
        
        /**
         * 초기화
         */
        init: function() {
            this.detectDevice();
            this.initMobileMenu();
            this.initSwipeGestures();
            this.initResizeHandler();
            this.initOrientationHandler();
            this.initTouchOptimization();
        },
        
        /**
         * 디바이스 타입 감지
         */
        detectDevice: function() {
            const width = window.innerWidth;
            
            if (width < this.breakpoints.mobile) {
                this.currentDevice = 'mobile-small';
            } else if (width < this.breakpoints.tablet) {
                this.currentDevice = 'mobile';
            } else if (width < this.breakpoints.desktop) {
                this.currentDevice = 'tablet';
            } else if (width < this.breakpoints.large) {
                this.currentDevice = 'desktop';
            } else if (width < this.breakpoints.xlarge) {
                this.currentDevice = 'large';
            } else {
                this.currentDevice = 'xlarge';
            }
            
            // body에 클래스 추가
            document.body.className = document.body.className.replace(/device-\S+/g, '');
            document.body.classList.add('device-' + this.currentDevice);
            
            // 터치 디바이스 감지
            if ('ontouchstart' in window || navigator.maxTouchPoints > 0) {
                document.body.classList.add('touch-device');
            } else {
                document.body.classList.add('no-touch');
            }
            
            return this.currentDevice;
        },
        
        /**
         * 모바일 메뉴 초기화
         */
        initMobileMenu: function() {
            const self = this;
            
            // 모바일 메뉴 토글 버튼 생성
            let toggleBtn = document.querySelector('.mobile-menu-toggle');
            if (!toggleBtn) {
                toggleBtn = document.createElement('button');
                toggleBtn.className = 'mobile-menu-toggle';
                toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
                toggleBtn.setAttribute('aria-label', '메뉴 열기');
                toggleBtn.setAttribute('aria-expanded', 'false');
                document.body.appendChild(toggleBtn);
            }
            
            // 오버레이 생성
            let overlay = document.querySelector('.mobile-overlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.className = 'mobile-overlay';
                document.body.appendChild(overlay);
            }
            
            // 토글 버튼 클릭 이벤트
            toggleBtn.addEventListener('click', function() {
                self.toggleSidebar();
            });
            
            // 오버레이 클릭 이벤트
            overlay.addEventListener('click', function() {
                self.closeSidebar();
            });
            
            // ESC 키로 닫기
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && self.sidebarOpen) {
                    self.closeSidebar();
                }
            });
        },
        
        /**
         * 사이드바 토글
         */
        toggleSidebar: function() {
            if (this.sidebarOpen) {
                this.closeSidebar();
            } else {
                this.openSidebar();
            }
        },
        
        /**
         * 사이드바 열기
         */
        openSidebar: function() {
            const sidebar = document.querySelector('.chapter-sidebar');
            const overlay = document.querySelector('.mobile-overlay');
            const toggleBtn = document.querySelector('.mobile-menu-toggle');
            
            if (sidebar) {
                sidebar.classList.add('active');
                overlay.classList.add('active');
                toggleBtn.innerHTML = '<i class="fas fa-times"></i>';
                toggleBtn.setAttribute('aria-expanded', 'true');
                toggleBtn.setAttribute('aria-label', '메뉴 닫기');
                
                // 포커스 트랩
                this.trapFocus(sidebar);
                
                // 바디 스크롤 방지
                document.body.style.overflow = 'hidden';
                
                this.sidebarOpen = true;
            }
        },
        
        /**
         * 사이드바 닫기
         */
        closeSidebar: function() {
            const sidebar = document.querySelector('.chapter-sidebar');
            const overlay = document.querySelector('.mobile-overlay');
            const toggleBtn = document.querySelector('.mobile-menu-toggle');
            
            if (sidebar) {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
                toggleBtn.setAttribute('aria-expanded', 'false');
                toggleBtn.setAttribute('aria-label', '메뉴 열기');
                
                // 바디 스크롤 복원
                document.body.style.overflow = '';
                
                this.sidebarOpen = false;
            }
        },
        
        /**
         * 스와이프 제스처 초기화
         */
        initSwipeGestures: function() {
            const self = this;
            let touchStartX = 0;
            let touchEndX = 0;
            let touchStartY = 0;
            let touchEndY = 0;
            
            // 스와이프 영역 생성
            let swipeArea = document.querySelector('.swipe-area');
            if (!swipeArea && this.isMobile()) {
                swipeArea = document.createElement('div');
                swipeArea.className = 'swipe-area';
                document.body.appendChild(swipeArea);
            }
            
            // 터치 이벤트 리스너
            document.addEventListener('touchstart', function(e) {
                touchStartX = e.changedTouches[0].screenX;
                touchStartY = e.changedTouches[0].screenY;
            }, { passive: true });
            
            document.addEventListener('touchend', function(e) {
                touchEndX = e.changedTouches[0].screenX;
                touchEndY = e.changedTouches[0].screenY;
                
                self.handleSwipe(touchStartX, touchEndX, touchStartY, touchEndY);
            }, { passive: true });
        },
        
        /**
         * 스와이프 처리
         */
        handleSwipe: function(startX, endX, startY, endY) {
            const diffX = endX - startX;
            const diffY = endY - startY;
            const threshold = 50; // 최소 스와이프 거리
            
            // 수평 스와이프가 수직보다 큰 경우만 처리
            if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > threshold) {
                if (diffX > 0 && startX < 20) {
                    // 오른쪽 스와이프 (왼쪽 가장자리에서 시작)
                    this.openSidebar();
                } else if (diffX < 0 && this.sidebarOpen) {
                    // 왼쪽 스와이프 (사이드바 열린 상태)
                    this.closeSidebar();
                }
            }
        },
        
        /**
         * 리사이즈 핸들러 초기화
         */
        initResizeHandler: function() {
            const self = this;
            let resizeTimer;
            
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function() {
                    const oldDevice = self.currentDevice;
                    const newDevice = self.detectDevice();
                    
                    // 디바이스 타입이 변경된 경우
                    if (oldDevice !== newDevice) {
                        self.handleDeviceChange(oldDevice, newDevice);
                    }
                    
                    // 모바일이 아닌 경우 사이드바 닫기
                    if (!self.isMobile() && self.sidebarOpen) {
                        self.closeSidebar();
                    }
                }, 250);
            });
        },
        
        /**
         * 디바이스 변경 처리
         */
        handleDeviceChange: function(oldDevice, newDevice) {
            console.log('Device changed from', oldDevice, 'to', newDevice);
            
            // 레이아웃 재조정
            this.adjustLayout();
            
            // 이벤트 발생
            const event = new CustomEvent('devicechange', {
                detail: {
                    oldDevice: oldDevice,
                    newDevice: newDevice
                }
            });
            document.dispatchEvent(event);
        },
        
        /**
         * 화면 방향 변경 핸들러
         */
        initOrientationHandler: function() {
            const self = this;
            
            if ('orientation' in window) {
                window.addEventListener('orientationchange', function() {
                    setTimeout(function() {
                        self.adjustLayout();
                    }, 500);
                });
            }
        },
        
        /**
         * 레이아웃 조정
         */
        adjustLayout: function() {
            const isMobile = this.isMobile();
            const isTablet = this.isTablet();
            const isLandscape = window.innerHeight < window.innerWidth;
            
            // 가로 모드 클래스
            if (isLandscape) {
                document.body.classList.add('landscape');
            } else {
                document.body.classList.remove('landscape');
            }
            
            // 그리드 조정
            const container = document.querySelector('.chapter-container');
            if (container) {
                if (isMobile) {
                    container.style.gridTemplateColumns = '1fr';
                } else if (isTablet) {
                    container.style.gridTemplateColumns = '200px 1fr';
                } else {
                    container.style.gridTemplateColumns = '';
                }
            }
            
            // 카드 레이아웃 조정
            this.adjustCardLayout();
        },
        
        /**
         * 카드 레이아웃 조정
         */
        adjustCardLayout: function() {
            const cards = document.querySelectorAll('.topic-card-item');
            const containerWidth = window.innerWidth;
            
            cards.forEach(card => {
                if (containerWidth < this.breakpoints.mobile) {
                    card.style.flex = '1 1 100%';
                } else if (containerWidth < this.breakpoints.tablet) {
                    card.style.flex = '1 1 100%';
                } else if (containerWidth < this.breakpoints.desktop) {
                    card.style.flex = '1 1 calc(50% - 0.5rem)';
                } else if (containerWidth < this.breakpoints.large) {
                    card.style.flex = '1 1 calc(33.333% - 1rem)';
                } else {
                    card.style.flex = '1 1 calc(25% - 1.125rem)';
                }
            });
        },
        
        /**
         * 터치 최적화
         */
        initTouchOptimization: function() {
            if (!this.isTouchDevice()) return;
            
            // 패시브 터치 이벤트
            document.addEventListener('touchstart', function() {}, { passive: true });
            document.addEventListener('touchmove', function() {}, { passive: true });
            
            // 더블탭 줌 방지
            let lastTouchEnd = 0;
            document.addEventListener('touchend', function(e) {
                const now = Date.now();
                if (now - lastTouchEnd <= 300) {
                    e.preventDefault();
                }
                lastTouchEnd = now;
            }, false);
            
            // iOS 바운스 스크롤 방지
            if (this.isIOS()) {
                document.body.addEventListener('touchmove', function(e) {
                    if (e.target.closest('.chapter-sidebar, .chapter-main')) {
                        return;
                    }
                    e.preventDefault();
                }, { passive: false });
            }
        },
        
        /**
         * 포커스 트랩
         */
        trapFocus: function(element) {
            const focusableElements = element.querySelectorAll(
                'a[href], button, textarea, input[type="text"], input[type="radio"], input[type="checkbox"], select'
            );
            const firstFocusableElement = focusableElements[0];
            const lastFocusableElement = focusableElements[focusableElements.length - 1];
            
            element.addEventListener('keydown', function(e) {
                if (e.key === 'Tab') {
                    if (e.shiftKey) { // Shift + Tab
                        if (document.activeElement === firstFocusableElement) {
                            lastFocusableElement.focus();
                            e.preventDefault();
                        }
                    } else { // Tab
                        if (document.activeElement === lastFocusableElement) {
                            firstFocusableElement.focus();
                            e.preventDefault();
                        }
                    }
                }
            });
            
            firstFocusableElement.focus();
        },
        
        /**
         * 유틸리티 함수들
         */
        isMobile: function() {
            return window.innerWidth < this.breakpoints.tablet;
        },
        
        isTablet: function() {
            return window.innerWidth >= this.breakpoints.tablet && 
                   window.innerWidth < this.breakpoints.desktop;
        },
        
        isDesktop: function() {
            return window.innerWidth >= this.breakpoints.desktop;
        },
        
        isTouchDevice: function() {
            return 'ontouchstart' in window || navigator.maxTouchPoints > 0;
        },
        
        isIOS: function() {
            return /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
        },
        
        isAndroid: function() {
            return /Android/.test(navigator.userAgent);
        }
    };
    
    // 초기화
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            ResponsiveHandler.init();
        });
    } else {
        ResponsiveHandler.init();
    }
    
    // 전역 노출
    window.ResponsiveHandler = ResponsiveHandler;
    
})(window, document);