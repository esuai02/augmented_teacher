/**
 * Confidence Booster main JavaScript module
 *
 * @module     local_confidence_booster/confidence
 * @copyright  2024 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/ajax', 'core/notification', 'core/templates'], 
function($, Ajax, Notification, Templates) {
    
    /**
     * Initialize the confidence booster features
     */
    var init = function() {
        // Initialize summary card handler
        initSummaryCard();
        
        // Initialize error classifier
        initErrorClassifier();
        
        // Initialize challenge widget
        initChallengeWidget();
        
        // Initialize dashboard
        initDashboard();
    };
    
    /**
     * Initialize summary card functionality
     */
    var initSummaryCard = function() {
        // Listen for concept marking events
        $(document).on('conceptMarked', function(e, data) {
            showSummaryModal(data.concept);
        });
        
        // Handle summary save button
        $(document).on('click', '.save-summary-btn', function() {
            var conceptTitle = $(this).data('concept');
            var summaryText = $('#summary-text').val();
            var chapter = $('#chapter-name').val();
            
            if (summaryText.length < 20) {
                Notification.addNotification({
                    message: '요약을 20자 이상 작성해주세요.',
                    type: 'warning'
                });
                return;
            }
            
            saveSummary(conceptTitle, summaryText, chapter);
        });
    };
    
    /**
     * Show summary modal
     */
    var showSummaryModal = function(conceptTitle) {
        var context = {
            concept: conceptTitle,
            placeholder: '오늘 배운 핵심을 3줄로 정리해보세요...'
        };
        
        Templates.render('local_confidence_booster/summary_card', context)
            .then(function(html) {
                var modal = $(html);
                $('body').append(modal);
                modal.modal('show');
                
                // Auto-save draft every 30 seconds
                setInterval(function() {
                    saveDraft($('#summary-text').val());
                }, 30000);
            })
            .fail(Notification.exception);
    };
    
    /**
     * Save summary via AJAX
     */
    var saveSummary = function(conceptTitle, summaryText, chapter) {
        var requests = [{
            methodname: 'local_confidence_booster_save_summary',
            args: {
                concept_title: conceptTitle,
                summary_text: summaryText,
                chapter: chapter
            }
        }];
        
        Ajax.call(requests)[0]
            .done(function(response) {
                if (response.success) {
                    // Show AI feedback
                    showAIFeedback(response.feedback, response.score);
                    
                    // Update dashboard
                    updateDashboardSummaryCount();
                    
                    // Close modal
                    $('.summary-modal').modal('hide');
                    
                    Notification.addNotification({
                        message: '요약이 저장되었습니다!',
                        type: 'success'
                    });
                }
            })
            .fail(function(error) {
                Notification.exception(error);
            });
    };
    
    /**
     * Initialize error classifier
     */
    var initErrorClassifier = function() {
        // Add classifier to error items
        $('.error-item').each(function() {
            if (!$(this).find('.error-classifier').length) {
                var classifier = $('<div class="error-classifier">' +
                    '<label>오답 분류:</label>' +
                    '<select class="error-type-select">' +
                    '<option value="">선택하세요</option>' +
                    '<option value="concept">개념 이해 부족</option>' +
                    '<option value="calculation">계산 실수</option>' +
                    '<option value="mistake">단순 실수</option>' +
                    '<option value="application">응용력 부족</option>' +
                    '</select>' +
                    '<textarea class="error-memo" placeholder="왜 틀렸는지 메모..."></textarea>' +
                    '<button class="btn btn-sm btn-primary save-error-btn">저장</button>' +
                    '</div>');
                $(this).append(classifier);
            }
        });
        
        // Handle error save
        $(document).on('click', '.save-error-btn', function() {
            var container = $(this).closest('.error-item');
            var questionId = container.data('question-id');
            var errorType = container.find('.error-type-select').val();
            var errorMemo = container.find('.error-memo').val();
            
            if (!errorType) {
                Notification.addNotification({
                    message: '오답 분류를 선택해주세요.',
                    type: 'warning'
                });
                return;
            }
            
            logError(questionId, errorType, errorMemo);
        });
    };
    
    /**
     * Log error classification
     */
    var logError = function(questionId, errorType, errorMemo) {
        var requests = [{
            methodname: 'local_confidence_booster_log_error',
            args: {
                questionid: questionId,
                error_type: errorType,
                error_memo: errorMemo
            }
        }];
        
        Ajax.call(requests)[0]
            .done(function(response) {
                if (response.success) {
                    // Mark as classified
                    $('.error-item[data-question-id="' + questionId + '"]')
                        .addClass('classified')
                        .find('.error-classifier').slideUp();
                    
                    // Update dashboard
                    updateDashboardErrorCount();
                    
                    Notification.addNotification({
                        message: '오답 분류가 저장되었습니다.',
                        type: 'success'
                    });
                }
            })
            .fail(Notification.exception);
    };
    
    /**
     * Initialize challenge widget
     */
    var initChallengeWidget = function() {
        // Check if it's Monday
        var today = new Date();
        if (today.getDay() === 1) { // Monday
            showChallengeNotification();
        }
        
        // Handle challenge start button
        $(document).on('click', '.start-challenge-btn', function() {
            generateWeeklyChallenge();
        });
        
        // Handle challenge submission
        $(document).on('click', '.submit-challenge-btn', function() {
            submitChallenge();
        });
    };
    
    /**
     * Show challenge notification
     */
    var showChallengeNotification = function() {
        var notification = $('<div class="challenge-notification alert alert-info">' +
            '<h4>🎯 이번 주 도전 문제가 준비되었습니다!</h4>' +
            '<p>상위 난이도 문제에 도전해보세요.</p>' +
            '<button class="btn btn-primary start-challenge-btn">도전하기</button>' +
            '</div>');
        
        $('.dashboard-content').prepend(notification);
    };
    
    /**
     * Generate weekly challenge
     */
    var generateWeeklyChallenge = function() {
        var requests = [{
            methodname: 'local_confidence_booster_generate_challenge',
            args: {}
        }];
        
        Ajax.call(requests)[0]
            .done(function(response) {
                if (response.success) {
                    // Show challenge questions
                    showChallengeQuestions(response.challenge);
                }
            })
            .fail(Notification.exception);
    };
    
    /**
     * Show challenge questions
     */
    var showChallengeQuestions = function(challenge) {
        var context = {
            level: challenge.level,
            questions: challenge.questions,
            week: challenge.week
        };
        
        Templates.render('local_confidence_booster/challenge_widget', context)
            .then(function(html) {
                $('.challenge-container').html(html);
                
                // Start timer
                startChallengeTimer();
            })
            .fail(Notification.exception);
    };
    
    /**
     * Initialize dashboard
     */
    var initDashboard = function() {
        if ($('.confidence-dashboard').length) {
            loadDashboardData();
            
            // Refresh every 5 minutes
            setInterval(loadDashboardData, 300000);
        }
    };
    
    /**
     * Load dashboard data
     */
    var loadDashboardData = function() {
        var requests = [{
            methodname: 'local_confidence_booster_fetch_dashboard',
            args: {}
        }];
        
        Ajax.call(requests)[0]
            .done(function(response) {
                if (response.success) {
                    updateDashboard(response.data);
                }
            })
            .fail(Notification.exception);
    };
    
    /**
     * Update dashboard with new data
     */
    var updateDashboard = function(data) {
        // Update summary count
        $('.summary-count').text(data.summaries.length);
        
        // Update error statistics
        if (data.errors) {
            var errorChart = $('.error-chart');
            errorChart.empty();
            
            $.each(data.errors, function(type, count) {
                var percentage = (count / getTotalErrors(data.errors)) * 100;
                var bar = $('<div class="error-bar">' +
                    '<span class="error-type">' + getErrorTypeLabel(type) + '</span>' +
                    '<div class="progress">' +
                    '<div class="progress-bar" style="width: ' + percentage + '%">' +
                    count + '</div>' +
                    '</div>' +
                    '</div>');
                errorChart.append(bar);
            });
        }
        
        // Update challenge status
        if (data.challenges && data.challenges.length > 0) {
            var challenge = data.challenges[0];
            if (challenge.attempted) {
                $('.challenge-status').html(
                    '<span class="badge badge-success">완료 (' + challenge.success_rate + '%)</span>'
                );
            } else {
                $('.challenge-status').html(
                    '<span class="badge badge-warning">대기중</span>'
                );
            }
        }
        
        // Update metrics
        if (data.metrics) {
            $('.confidence-score').text(data.metrics.confidence || 0);
            $('.performance-score').text(data.metrics.performance || 0);
        }
        
        // Update unread feedback count
        if (data.unread_feedback > 0) {
            $('.feedback-badge').text(data.unread_feedback).show();
        } else {
            $('.feedback-badge').hide();
        }
    };
    
    /**
     * Helper functions
     */
    var getTotalErrors = function(errors) {
        var total = 0;
        $.each(errors, function(type, count) {
            total += count;
        });
        return total || 1;
    };
    
    var getErrorTypeLabel = function(type) {
        var labels = {
            'concept': '개념',
            'calculation': '계산',
            'mistake': '실수',
            'application': '응용'
        };
        return labels[type] || type;
    };
    
    var updateDashboardSummaryCount = function() {
        var current = parseInt($('.summary-count').text()) || 0;
        $('.summary-count').text(current + 1);
    };
    
    var updateDashboardErrorCount = function() {
        var current = parseInt($('.error-classified-count').text()) || 0;
        $('.error-classified-count').text(current + 1);
    };
    
    var saveDraft = function(text) {
        localStorage.setItem('confidence_summary_draft', text);
    };
    
    var startChallengeTimer = function() {
        var startTime = Date.now();
        var timerInterval = setInterval(function() {
            var elapsed = Math.floor((Date.now() - startTime) / 1000);
            var minutes = Math.floor(elapsed / 60);
            var seconds = elapsed % 60;
            $('.challenge-timer').text(minutes + ':' + (seconds < 10 ? '0' : '') + seconds);
        }, 1000);
        
        // Store interval ID for later cleanup
        $('.challenge-container').data('timer', timerInterval);
    };
    
    var submitChallenge = function() {
        // Clear timer
        var timer = $('.challenge-container').data('timer');
        if (timer) {
            clearInterval(timer);
        }
        
        // Collect answers and submit
        var answers = [];
        $('.challenge-answer').each(function() {
            answers.push($(this).val());
        });
        
        // Submit via AJAX
        var requests = [{
            methodname: 'local_confidence_booster_submit_challenge',
            args: {
                answers: answers
            }
        }];
        
        Ajax.call(requests)[0]
            .done(function(response) {
                if (response.success) {
                    showChallengeResults(response.results);
                }
            })
            .fail(Notification.exception);
    };
    
    var showChallengeResults = function(results) {
        var context = {
            success_rate: results.success_rate,
            badge: results.badge_earned,
            feedback: results.feedback
        };
        
        Templates.render('local_confidence_booster/challenge_results', context)
            .then(function(html) {
                $('.challenge-container').html(html);
            })
            .fail(Notification.exception);
    };
    
    var showAIFeedback = function(feedback, score) {
        var modal = $('<div class="modal fade">' +
            '<div class="modal-dialog">' +
            '<div class="modal-content">' +
            '<div class="modal-header">' +
            '<h5 class="modal-title">AI 피드백</h5>' +
            '<button type="button" class="close" data-dismiss="modal">&times;</button>' +
            '</div>' +
            '<div class="modal-body">' +
            '<div class="score-display">점수: ' + score + '/5</div>' +
            '<p>' + feedback + '</p>' +
            '</div>' +
            '<div class="modal-footer">' +
            '<button type="button" class="btn btn-primary" data-dismiss="modal">확인</button>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '</div>');
        
        $('body').append(modal);
        modal.modal('show');
        modal.on('hidden.bs.modal', function() {
            modal.remove();
        });
    };
    
    return {
        init: init
    };
});