/**
 * Chapter42 JavaScript Module
 * 모든 JavaScript 기능을 모듈 패턴으로 구성
 * 기존 함수 시그니처 100% 유지
 */

(function(window, document, $) {
    'use strict';
    
    /**
     * ChapterModule 네임스페이스
     * 모든 chapter42 관련 JavaScript 기능을 포함
     */
    const ChapterModule = {
        
        /**
         * 초기화 함수
         */
        init: function() {
            this.initEventListeners();
            this.initCollapsibles();
            this.initTooltips();
            this.initModalHandlers();
        },
        
        /**
         * 이벤트 리스너 초기화
         */
        initEventListeners: function() {
            // DOM Ready 이벤트
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => {
                    this.onDOMReady();
                });
            } else {
                this.onDOMReady();
            }
        },
        
        /**
         * DOM 준비 완료 핸들러
         */
        onDOMReady: function() {
            // 테이블 컨테이너 페이드인
            const tableContainer = document.getElementById('tableContainer');
            if (tableContainer) {
                setTimeout(() => {
                    tableContainer.classList.add('active');
                }, 100);
            }
            
            // 콜백 모니터링 시작
            this.checkMonitoringStatus();
        },
        
        /**
         * Collapsible 초기화
         */
        initCollapsibles: function() {
            const collapsibles = document.getElementsByClassName('collapsible');
            
            Array.from(collapsibles).forEach(collapsible => {
                collapsible.addEventListener('click', function() {
                    this.classList.toggle('active');
                    const content = this.nextElementSibling;
                    if (content.style.display === 'block') {
                        content.style.display = 'none';
                    } else {
                        content.style.display = 'block';
                    }
                });
            });
        },
        
        /**
         * Bootstrap 툴팁 초기화
         */
        initTooltips: function() {
            if (typeof bootstrap !== 'undefined') {
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            }
        },
        
        /**
         * 모달 핸들러 초기화
         */
        initModalHandlers: function() {
            // 콜백 모달 핸들러
            const callbackButton = document.getElementById('callbackButton');
            if (callbackButton) {
                callbackButton.addEventListener('click', () => {
                    this.openCallbackModal();
                });
            }
        },
        
        /**
         * Immersive Session 함수
         * 기존 시그니처 유지: ImmersiveSession(Eventid,Userid,Cid,Domainid,Chapterid,Topicid)
         */
        immersiveSession: function(Eventid, Userid, Cid, Domainid, Chapterid, Topicid) {
            if (!Userid) {
                Swal.fire('알림', '참가자를 선택해주세요!', 'warning');
                return;
            }
            
            const data = {
                eventid: Eventid,
                userid: Userid,
                cid: Cid,
                domainid: Domainid,
                chapterid: Chapterid,
                topicid: Topicid
            };
            
            $.ajax({
                type: 'POST',
                url: 'https://mathking.kr/moodle/local/augmented_teacher/teachers/immersivedata42.php',
                data: data,
                success: function(response) {
                    // 성공 처리
                    console.log('Immersive session saved:', response);
                },
                error: function(xhr, status, error) {
                    console.error('Immersive session error:', error);
                }
            });
            
            // 두 번째 AJAX 호출 (dragchatbox.php)
            $.ajax({
                type: 'POST',
                url: 'https://mathking.kr/moodle/local/augmented_teacher/books/dragchatbox.php',
                data: { cntid: Topicid },
                success: function(response) {
                    console.log('Dragchatbox response:', response);
                }
            });
        },
        
        /**
         * Change CheckBox 함수
         * 기존 시그니처 유지: ChangeCheckBox(Eventid,Userid,Contentsid,Wboardid,Noteurl)
         */
        changeCheckBox: function(Eventid, Userid, Contentsid, Wboardid, Noteurl) {
            const data = {
                eventid: Eventid,
                userid: Userid,
                contentsid: Contentsid,
                wboardid: Wboardid,
                noteurl: Noteurl
            };
            
            $.ajax({
                type: 'POST',
                url: 'https://mathking.kr/moodle/local/augmented_teacher/books/changecheckbox.php',
                data: data,
                success: function(response) {
                    console.log('Checkbox changed:', response);
                },
                error: function(xhr, status, error) {
                    console.error('Checkbox change error:', error);
                }
            });
        },
        
        /**
         * Open Persona Popup 함수
         * 기존 시그니처 유지: openPersonaPopup(cntid, studentid)
         */
        openPersonaPopup: function(cntid, studentid) {
            const url = 'https://mathking.kr/moodle/local/augmented_teacher/teachers/persona_popup.php?cntid=' + cntid + '&studentid=' + studentid;
            const features = 'width=800,height=600,resizable=yes,scrollbars=yes,status=yes';
            window.open(url, 'PersonaPopup', features);
        },
        
        /**
         * Set Goal 함수
         * 기존 시그니처 유지: setGoal(Inputtext)
         */
        setGoal: function(Inputtext) {
            let goalText = '';
            
            if (Inputtext === 'A') {
                goalText = '목표를 100%를 향해 전진해요!';
            } else if (Inputtext === 'B') {
                goalText = '목표를 75%를 향해 전진해요!';
            } else if (Inputtext === 'C') {
                goalText = '목표를 50%를 향해 전진해요!';
            } else if (Inputtext === 'D') {
                goalText = '목표를 25%를 향해 전진해요!';
            }
            
            Swal.fire({
                title: '학습 목표',
                text: goalText,
                icon: 'info',
                confirmButtonText: '확인'
            });
        },
        
        /**
         * Add Review 함수
         * 기존 시그니처 유지: addReview(Inputtext)
         */
        addReview: function(Inputtext) {
            $.ajax({
                type: 'POST',
                url: 'https://mathking.kr/moodle/local/augmented_teacher/books/addreview.php',
                data: { inputtext: Inputtext },
                success: function(response) {
                    console.log('Review added:', response);
                    Swal.fire('성공', '복습이 추가되었습니다.', 'success');
                },
                error: function(xhr, status, error) {
                    console.error('Add review error:', error);
                    Swal.fire('오류', '복습 추가 중 문제가 발생했습니다.', 'error');
                }
            });
        },
        
        /**
         * Drag Chatbox 함수
         * 기존 시그니처 유지: dragChatbox(Cntid)
         */
        dragChatbox: function(Cntid) {
            $.ajax({
                type: 'POST',
                url: 'https://mathking.kr/moodle/local/augmented_teacher/books/dragchatbox.php',
                data: { cntid: Cntid },
                success: function(response) {
                    console.log('Chatbox dragged:', response);
                },
                error: function(xhr, status, error) {
                    console.error('Drag chatbox error:', error);
                }
            });
        },
        
        /**
         * Check Progress 함수 (핵심 함수)
         * 기존 시그니처 유지: CheckProgress(Eventid,Userid,Itemid,Checkvalue)
         */
        checkProgress: function(Eventid, Userid, Itemid, Checkvalue) {
            const checkbox = document.getElementById('checkbox' + Eventid);
            const isChecked = checkbox ? checkbox.checked : false;
            
            const data = {
                eventid: Eventid,
                userid: Userid,
                itemid: Itemid,
                checkvalue: isChecked ? 1 : 0
            };
            
            $.ajax({
                type: 'POST',
                url: 'https://mathking.kr/moodle/local/augmented_teacher/books/checkprogress.php',
                data: data,
                success: function(response) {
                    console.log('Progress checked:', response);
                    
                    // 진행률 업데이트
                    if (window.updateProgressBar) {
                        window.updateProgressBar();
                    }
                    
                    // 체크박스 상태에 따른 카드 스타일 업데이트
                    const card = checkbox.closest('.topic-card');
                    if (card) {
                        if (isChecked) {
                            card.classList.add('topic-card--done');
                        } else {
                            card.classList.remove('topic-card--done');
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Check progress error:', error);
                    Swal.fire('오류', '진행 상황 저장 중 문제가 발생했습니다.', 'error');
                }
            });
        },
        
        /**
         * Check Monitoring Status 함수
         * 기존 시그니처 유지: checkMonitoringStatus()
         */
        checkMonitoringStatus: function() {
            const self = this;
            
            $.ajax({
                url: 'https://mathking.kr/moodle/local/augmented_teacher/books/check_monitoring.php',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.hasActiveCallback) {
                        // 활성 콜백이 있으면 버튼 표시
                        const callbackButton = document.getElementById('callbackButton');
                        if (callbackButton) {
                            callbackButton.style.display = 'block';
                            callbackButton.classList.add('pulse-animation');
                        }
                        
                        // 주기적으로 상태 체크 (30초마다)
                        setTimeout(() => {
                            self.checkMonitoringStatus();
                        }, 30000);
                    }
                },
                error: function() {
                    console.error('Failed to check monitoring status');
                }
            });
        },
        
        /**
         * Open Callback Modal 함수
         * 기존 시그니처 유지: openCallbackModal()
         */
        openCallbackModal: function() {
            const self = this;
            
            Swal.fire({
                title: '콜백 시간 설정',
                html: `
                    <div class="callback-modal-content">
                        <p>언제 다시 돌아오시겠습니까?</p>
                        <div class="time-buttons">
                            <button class="swal2-confirm swal2-styled" onclick="ChapterModule.saveCallbackGeneral(5, '5분 후 복귀')">5분</button>
                            <button class="swal2-confirm swal2-styled" onclick="ChapterModule.saveCallbackGeneral(10, '10분 후 복귀')">10분</button>
                            <button class="swal2-confirm swal2-styled" onclick="ChapterModule.saveCallbackGeneral(15, '15분 후 복귀')">15분</button>
                            <button class="swal2-confirm swal2-styled" onclick="ChapterModule.saveCallbackGeneral(30, '30분 후 복귀')">30분</button>
                            <button class="swal2-confirm swal2-styled" onclick="ChapterModule.saveCallbackGeneral(60, '1시간 후 복귀')">1시간</button>
                        </div>
                        <div class="custom-time-input mt-3">
                            <input type="number" id="customTime" class="swal2-input" placeholder="직접 입력 (분)" min="1" max="480">
                            <button class="swal2-confirm swal2-styled mt-2" onclick="ChapterModule.saveCallbackCustom()">설정</button>
                        </div>
                    </div>
                `,
                showConfirmButton: false,
                showCancelButton: true,
                cancelButtonText: '취소',
                customClass: {
                    container: 'callback-modal-container'
                }
            });
        },
        
        /**
         * Save Callback General 함수
         * 기존 시그니처 유지: saveCallbackGeneral(timeMinutes, content)
         */
        saveCallbackGeneral: function(timeMinutes, content) {
            const self = this;
            
            $.ajax({
                url: 'https://mathking.kr/moodle/local/augmented_teacher/books/save_callback.php',
                type: 'POST',
                data: {
                    time_minutes: timeMinutes,
                    content: content
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '콜백 설정 완료',
                            text: `${timeMinutes}분 후에 알림이 표시됩니다.`,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        
                        // 버튼 숨기기
                        const callbackButton = document.getElementById('callbackButton');
                        if (callbackButton) {
                            callbackButton.style.display = 'none';
                        }
                        
                        // 설정된 시간 후에 다시 체크
                        setTimeout(() => {
                            self.checkMonitoringStatus();
                        }, timeMinutes * 60 * 1000);
                    } else {
                        Swal.fire('오류', '콜백 설정 중 문제가 발생했습니다.', 'error');
                    }
                },
                error: function() {
                    Swal.fire('오류', '서버 연결 중 문제가 발생했습니다.', 'error');
                }
            });
        },
        
        /**
         * Save Callback Custom 함수
         * 사용자 정의 시간 설정
         */
        saveCallbackCustom: function() {
            const customTime = document.getElementById('customTime').value;
            if (customTime && customTime > 0) {
                this.saveCallbackGeneral(parseInt(customTime), `${customTime}분 후 복귀`);
            } else {
                Swal.fire('알림', '올바른 시간을 입력해주세요.', 'warning');
            }
        },
        
        /**
         * Complete Callback 함수
         * 기존 시그니처 유지: completeCallback(callbackId)
         */
        completeCallback: function(callbackId) {
            $.ajax({
                url: 'https://mathking.kr/moodle/local/augmented_teacher/books/complete_callback.php',
                type: 'POST',
                data: {
                    callback_id: callbackId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '완료',
                            text: '콜백이 완료되었습니다.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        
                        // 버튼 숨기기
                        const callbackButton = document.getElementById('callbackButton');
                        if (callbackButton) {
                            callbackButton.style.display = 'none';
                            callbackButton.classList.remove('pulse-animation');
                        }
                    }
                },
                error: function() {
                    console.error('Failed to complete callback');
                }
            });
        },
        
        /**
         * Extend Callback 함수
         * 기존 시그니처 유지: extendCallback(callbackId, additionalMinutes)
         */
        extendCallback: function(callbackId, additionalMinutes) {
            $.ajax({
                url: 'https://mathking.kr/moodle/local/augmented_teacher/books/extend_callback.php',
                type: 'POST',
                data: {
                    callback_id: callbackId,
                    additional_minutes: additionalMinutes
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '연장 완료',
                            text: `콜백이 ${additionalMinutes}분 연장되었습니다.`,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                },
                error: function() {
                    console.error('Failed to extend callback');
                }
            });
        }
    };
    
    // 모듈 초기화
    ChapterModule.init();
    
    // 전역 함수 별칭 유지 (기존 코드 호환성)
    window.ImmersiveSession = ChapterModule.immersiveSession.bind(ChapterModule);
    window.ChangeCheckBox = ChapterModule.changeCheckBox.bind(ChapterModule);
    window.openPersonaPopup = ChapterModule.openPersonaPopup.bind(ChapterModule);
    window.setGoal = ChapterModule.setGoal.bind(ChapterModule);
    window.addReview = ChapterModule.addReview.bind(ChapterModule);
    window.dragChatbox = ChapterModule.dragChatbox.bind(ChapterModule);
    window.CheckProgress = ChapterModule.checkProgress.bind(ChapterModule);
    window.checkMonitoringStatus = ChapterModule.checkMonitoringStatus.bind(ChapterModule);
    window.openCallbackModal = ChapterModule.openCallbackModal.bind(ChapterModule);
    window.saveCallbackGeneral = ChapterModule.saveCallbackGeneral.bind(ChapterModule);
    window.saveCallbackCustom = ChapterModule.saveCallbackCustom.bind(ChapterModule);
    window.completeCallback = ChapterModule.completeCallback.bind(ChapterModule);
    window.extendCallback = ChapterModule.extendCallback.bind(ChapterModule);
    
    // 모듈 내보내기 (디버깅 및 확장 목적)
    window.ChapterModule = ChapterModule;
    
})(window, document, jQuery);