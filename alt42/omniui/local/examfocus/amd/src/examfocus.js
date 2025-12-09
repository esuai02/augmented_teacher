/**
 * ExamFocus AMD 모듈 - 추천 배너 및 자동 모드 전환
 * 
 * @module     local_examfocus/examfocus
 * @copyright  2025 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/ajax', 'core/str', 'core/notification', 'core/templates'], 
function($, Ajax, Str, Notification, Templates) {
    
    return {
        /**
         * 초기화
         * @param {int} userid 사용자 ID
         * @param {string} mountSelector 마운트할 선택자
         */
        init: function(userid, mountSelector) {
            var self = this;
            
            // 이미 표시했는지 확인 (세션 스토리지)
            var sessionKey = 'examfocus_shown_' + userid;
            var lastShown = sessionStorage.getItem(sessionKey);
            var now = new Date().getTime();
            
            // 24시간 쿨다운
            if (lastShown && (now - parseInt(lastShown)) < 24 * 60 * 60 * 1000) {
                console.log('ExamFocus: In cooldown period');
                return;
            }
            
            // 추천 정보 조회
            Ajax.call([{
                methodname: 'local_examfocus_get_recommendation',
                args: {userid: userid}
            }])[0].done(function(response) {
                if (response.has_recommendation) {
                    self.showRecommendation(response, mountSelector, userid);
                    sessionStorage.setItem(sessionKey, now);
                }
            }).fail(Notification.exception);
        },
        
        /**
         * 추천 배너 표시
         */
        showRecommendation: function(data, mountSelector, userid) {
            var self = this;
            
            // 템플릿 컨텍스트 준비
            var context = {
                mode: data.mode,
                message: data.message,
                exam_date: data.exam_date,
                days_until: data.days_until,
                priority: data.priority,
                urgent: data.priority === 'high',
                important: data.priority === 'medium'
            };
            
            // 문자열 로드
            Str.get_strings([
                {key: 'apply_recommendation', component: 'local_examfocus'},
                {key: 'dismiss', component: 'local_examfocus'},
                {key: 'recommendation_title', component: 'local_examfocus'}
            ]).done(function(strings) {
                context.apply_label = strings[0];
                context.dismiss_label = strings[1];
                context.title = strings[2];
                
                // 템플릿 렌더링
                Templates.render('local_examfocus/recommendation_banner', context)
                .done(function(html) {
                    var $mount = $(mountSelector);
                    
                    if ($mount.length === 0) {
                        // 대체 위치 찾기
                        $mount = $('#region-main');
                        if ($mount.length === 0) {
                            $mount = $('.content');
                        }
                    }
                    
                    // 배너 삽입
                    var $banner = $(html);
                    $mount.prepend($banner);
                    
                    // 애니메이션
                    $banner.hide().slideDown(300);
                    
                    // 이벤트 바인딩
                    self.bindEvents($banner, data, userid);
                    
                    // 학습 모드 버튼 강조
                    self.highlightModeButton(data.mode);
                })
                .fail(function(error) {
                    console.error('Template rendering failed:', error);
                });
            });
        },
        
        /**
         * 이벤트 바인딩
         */
        bindEvents: function($banner, data, userid) {
            var self = this;
            
            // 적용 버튼
            $banner.on('click', '.examfocus-apply', function(e) {
                e.preventDefault();
                
                var $btn = $(this);
                $btn.prop('disabled', true);
                
                // 추천 수락 API 호출
                Ajax.call([{
                    methodname: 'local_examfocus_accept_recommendation',
                    args: {
                        userid: userid,
                        mode: data.mode
                    }
                }])[0].done(function(response) {
                    if (response.success) {
                        // 성공 메시지
                        Notification.addNotification({
                            message: response.message,
                            type: 'success'
                        });
                        
                        // 배너 제거
                        $banner.slideUp(300, function() {
                            $banner.remove();
                        });
                        
                        // 모드 자동 전환 (페이지가 지원하는 경우)
                        self.autoSwitchMode(data.mode);
                    }
                }).fail(function(error) {
                    $btn.prop('disabled', false);
                    Notification.exception(error);
                });
            });
            
            // 닫기/무시 버튼
            $banner.on('click', '.examfocus-dismiss', function(e) {
                e.preventDefault();
                
                // 무시 API 호출
                Ajax.call([{
                    methodname: 'local_examfocus_dismiss_recommendation',
                    args: {userid: userid}
                }])[0].done(function() {
                    $banner.slideUp(300, function() {
                        $banner.remove();
                    });
                });
            });
        },
        
        /**
         * 학습 모드 버튼 강조
         */
        highlightModeButton: function(mode) {
            var self = this;
            
            // 모드에 해당하는 카드/버튼 찾기 (selectmode.php와 다른 페이지 모두 지원)
            var modeSelectors = [
                '[data-mode="' + mode + '"]',
                '.mode-' + mode.replace('_', '-'),
                '.mode-card[data-mode="' + mode + '"]'
            ];
            
            var $target = null;
            
            // 선택자들을 순서대로 시도
            for (var i = 0; i < modeSelectors.length; i++) {
                $target = $(modeSelectors[i]);
                if ($target.length > 0) {
                    break;
                }
            }
            
            if ($target && $target.length > 0) {
                console.log('ExamFocus: Highlighting mode element for:', mode);
                
                // 기존 강조 효과 제거
                $('.mode-card, .mode-button').removeClass('examfocus-highlight examfocus-pulse recommended active');
                
                // 새로운 강조 효과 적용
                $target.addClass('examfocus-highlight recommended active');
                
                // 펄스 애니메이션 (2초간)
                $target.addClass('examfocus-pulse');
                setTimeout(function() {
                    $target.removeClass('examfocus-pulse');
                }, 2000);
                
                // 스크롤 이동 (부드럽게)
                if ($target.offset()) {
                    $('html, body').animate({
                        scrollTop: $target.offset().top - 100
                    }, 500);
                }
                
                // selectmode.php의 경우 추가 처리
                if (window.location.pathname.includes('selectmode.php')) {
                    self.enhanceSelectModeCard($target, mode);
                }
            } else {
                console.warn('ExamFocus: Could not find element to highlight for mode:', mode);
            }
        },
        
        /**
         * selectmode.php의 모드 카드 추가 강화
         */
        enhanceSelectModeCard: function($card, mode) {
            // 추천 배지가 없으면 추가
            if (!$card.hasClass('recommended')) {
                $card.addClass('recommended');
            }
            
            // 버튼 텍스트를 "추천됨"으로 변경
            var $button = $card.find('.select-mode-btn');
            if ($button.length) {
                $button.html('<i class="fa fa-star"></i> 추천 모드 선택');
                $button.removeClass('btn-primary').addClass('btn-warning');
            }
            
            // 추천 이유 메시지 추가
            var $existingMessage = $card.find('.recommendation-message');
            if ($existingMessage.length === 0) {
                var message = this.getRecommendationMessage(mode);
                $card.find('.mode-features').after(
                    '<div class="recommendation-message alert alert-info mt-2 mb-2">' +
                    '<small><i class="fa fa-lightbulb"></i> ' + message + '</small>' +
                    '</div>'
                );
            }
        },
        
        /**
         * 모드별 추천 메시지 생성
         */
        getRecommendationMessage: function(mode) {
            var messages = {
                'concept_summary': '시험이 임박했습니다. 핵심 개념 정리에 집중하세요.',
                'review_errors': '체계적인 오답 복습으로 실력을 향상시킬 시기입니다.',
                'practice': '실전 감각을 기르기 위한 연습이 필요합니다.',
                'exam_day': '시험 당일입니다. 마음을 다스리고 최종 점검하세요.',
                'study': '꾸준한 학습으로 기초를 다질 시간입니다.'
            };
            
            return messages[mode] || '이 모드가 현재 상황에 적합합니다.';
        },
        
        /**
         * 자동 모드 전환
         */
        autoSwitchMode: function(mode) {
            var self = this;
            
            // 현재 페이지가 모드 선택을 지원하는지 확인
            if (typeof window.selectStudyMode === 'function') {
                console.log('ExamFocus: Using selectStudyMode function');
                window.selectStudyMode(mode);
            } else {
                // selectmode.php로 리다이렉트하여 모드 선택
                console.log('ExamFocus: Redirecting to selectmode.php');
                var selectModeUrl = M.cfg.wwwroot + '/local/examfocus/selectmode.php?recommended=' + mode;
                
                // 1초 후 리다이렉트 (사용자가 성공 메시지를 볼 수 있도록)
                setTimeout(function() {
                    window.location.href = selectModeUrl;
                }, 1000);
            }
        },
        
        /**
         * 추천 상태 체크 및 페이지 초기화
         */
        initPageRecommendation: function() {
            var urlParams = new URLSearchParams(window.location.search);
            var recommendedMode = urlParams.get('recommended');
            
            if (recommendedMode) {
                console.log('ExamFocus: Auto-highlighting recommended mode:', recommendedMode);
                
                // 추천 모드 자동 강조
                setTimeout(() => {
                    this.highlightModeButton(recommendedMode);
                }, 500);
                
                // URL에서 recommended 파라미터 제거 (깨끗한 URL 유지)
                var newUrl = window.location.pathname;
                window.history.replaceState({}, document.title, newUrl);
            }
        },
        
        /**
         * 페이지별 맞춤 초기화
         */
        initForCurrentPage: function(userid, mountSelector) {
            var currentPath = window.location.pathname;
            
            if (currentPath.includes('selectmode.php')) {
                // selectmode.php 페이지에서는 추천 상태 체크
                this.initPageRecommendation();
            } else {
                // 다른 페이지에서는 일반적인 추천 배너 초기화
                this.init(userid, mountSelector);
            }
        }
    };
});