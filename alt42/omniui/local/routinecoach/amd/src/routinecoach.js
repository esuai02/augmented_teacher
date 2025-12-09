// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * JavaScript module for Routine Coach plugin.
 *
 * @module     local_routinecoach/routinecoach
 * @copyright  2024 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification'], 
function($, Ajax, Notification) {
    
    'use strict';
    
    var widgetInjected = false;
    var apiBaseUrl = '/local/routinecoach/index.php';
    
    /**
     * Initialize the routine coach widget
     * @param {Object} params Configuration parameters
     */
    var init = function(params) {
        var userid = params.userid || getCurrentUserId();
        
        // Check if we should inject widget on specific pages
        if (shouldInjectWidget()) {
            injectWidget(userid);
        }
        
        // Set up global event handlers
        setupEventHandlers();
    };
    
    /**
     * Check if widget should be injected based on current URL
     * @return {Boolean} True if widget should be injected
     */
    var shouldInjectWidget = function() {
        var currentPath = window.location.pathname;
        
        // Check for target pages
        return currentPath.includes('/augmented_teacher/students/today42.php') ||
               currentPath.includes('/alt42/studenthome/index.php') ||
               currentPath.includes('/local/augmented_teacher/students/today42.php') ||
               currentPath.includes('/moodle/local/augmented_teacher/students/today42.php');
    };
    
    /**
     * Get current user ID from page or session
     * @return {Number} User ID
     */
    var getCurrentUserId = function() {
        // Try to get from M.cfg if available
        if (typeof M !== 'undefined' && M.cfg && M.cfg.sesskey) {
            return M.cfg.userid || 0;
        }
        
        // Try to get from URL parameter
        var urlParams = new URLSearchParams(window.location.search);
        var userid = urlParams.get('id') || urlParams.get('userid');
        
        // Try to get from data attribute
        if (!userid) {
            userid = $('[data-userid]').first().data('userid');
        }
        
        return parseInt(userid) || 0;
    };
    
    /**
     * Inject widget container and load today's tasks
     * @param {Number} userid User ID
     */
    var injectWidget = function(userid) {
        if (widgetInjected) {
            return;
        }
        
        // Create widget container
        var widgetHtml = '<div id="routinecoach-widget" class="routine-coach-widget" style="' +
            'position: fixed; bottom: 20px; right: 20px; width: 320px; ' +
            'background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); ' +
            'z-index: 1000; transition: all 0.3s ease;">' +
            '<div class="widget-header" style="' +
                'background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); ' +
                'color: white; padding: 12px 16px; border-radius: 12px 12px 0 0; ' +
                'cursor: pointer; display: flex; justify-content: space-between; align-items: center;">' +
                '<span class="widget-title" style="font-weight: 600; font-size: 14px;">오늘의 루틴</span>' +
                '<span class="widget-toggle" style="font-size: 18px;">▼</span>' +
            '</div>' +
            '<div class="widget-body" style="max-height: 400px; overflow-y: auto;">' +
                '<div class="widget-loading" style="padding: 20px; text-align: center;">' +
                    '<div class="spinner" style="' +
                        'border: 3px solid #f3f3f3; border-top: 3px solid #667eea; ' +
                        'border-radius: 50%; width: 30px; height: 30px; ' +
                        'animation: spin 1s linear infinite; margin: 0 auto;"></div>' +
                    '<p style="margin-top: 10px; color: #666;">로딩 중...</p>' +
                '</div>' +
            '</div>' +
        '</div>';
        
        // Add CSS animation
        if (!$('#routinecoach-styles').length) {
            $('head').append(
                '<style id="routinecoach-styles">' +
                '@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }' +
                '.routine-coach-widget.minimized { height: 48px !important; }' +
                '.routine-coach-widget.minimized .widget-body { display: none; }' +
                '.routine-coach-widget.minimized .widget-toggle { transform: rotate(-90deg); }' +
                '.task-item { padding: 12px 16px; border-bottom: 1px solid #f0f0f0; }' +
                '.task-item:hover { background: #f8f9fa; }' +
                '.task-checkbox { margin-right: 10px; cursor: pointer; }' +
                '.task-checkbox:checked + .task-label { text-decoration: line-through; opacity: 0.6; }' +
                '.task-label { cursor: pointer; flex: 1; }' +
                '.task-meta { font-size: 12px; color: #666; margin-top: 4px; }' +
                '.badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 11px; margin-right: 4px; }' +
                '.badge-concept { background: #e3f2fd; color: #1976d2; }' +
                '.badge-review { background: #e8f5e9; color: #388e3c; }' +
                '.badge-wrongnote { background: #fff3e0; color: #f57c00; }' +
                '.badge-countdown { background: #ffebee; color: #c62828; font-weight: 600; }' +
                '.progress-bar { height: 4px; background: #e0e0e0; border-radius: 2px; margin: 12px 16px; }' +
                '.progress-fill { height: 100%; border-radius: 2px; transition: width 0.3s ease; }' +
                '.stats-row { display: flex; justify-content: space-around; padding: 12px; background: #fafafa; }' +
                '.stat-item { text-align: center; }' +
                '.stat-value { font-size: 18px; font-weight: 600; color: #333; }' +
                '.stat-label { font-size: 11px; color: #666; margin-top: 2px; }' +
                '</style>'
            );
        }
        
        // Inject widget
        $('body').append(widgetHtml);
        widgetInjected = true;
        
        // Load today's tasks
        loadTodayTasks(userid);
        
        // Set up widget toggle
        $('#routinecoach-widget .widget-header').on('click', function() {
            $('#routinecoach-widget').toggleClass('minimized');
        });
    };
    
    /**
     * Load today's tasks from API
     * @param {Number} userid User ID
     */
    var loadTodayTasks = function(userid) {
        $.ajax({
            url: apiBaseUrl,
            type: 'GET',
            data: {
                view: 'today',
                userid: userid,
                format: 'json'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    renderTodayWidget(response.data);
                } else {
                    showWidgetError(response.message || '데이터를 불러올 수 없습니다.');
                }
            },
            error: function(xhr, status, error) {
                console.error('RoutineCoach: Failed to load tasks', error);
                showWidgetError('서버 연결에 실패했습니다.');
            }
        });
    };
    
    /**
     * Render today's tasks in widget
     * @param {Object} data Task data
     */
    var renderTodayWidget = function(data) {
        var tasks = data.tasks || [];
        var stats = data.stats || {};
        
        var html = '';
        
        // Stats row
        if (stats.exam_label && stats.days_left !== undefined) {
            html += '<div class="stats-row">';
            html += '<div class="stat-item">';
            html += '<div class="stat-value">' + stats.exam_label + '</div>';
            html += '<div class="stat-label">다음 시험</div>';
            html += '</div>';
            html += '<div class="stat-item">';
            html += '<div class="stat-value">D-' + stats.days_left + '</div>';
            html += '<div class="stat-label">남은 일수</div>';
            html += '</div>';
            html += '<div class="stat-item">';
            html += '<div class="stat-value">' + (stats.ratio || '7:3') + '</div>';
            html += '<div class="stat-label">학습 비율</div>';
            html += '</div>';
            html += '</div>';
        }
        
        // Progress bar
        if (stats.completed_count !== undefined && stats.total_count) {
            var progress = Math.round((stats.completed_count / stats.total_count) * 100);
            html += '<div class="progress-bar">';
            html += '<div class="progress-fill" style="width: ' + progress + '%; background: linear-gradient(90deg, #667eea, #764ba2);"></div>';
            html += '</div>';
            html += '<div style="text-align: center; font-size: 12px; color: #666; margin-bottom: 8px;">';
            html += stats.completed_count + ' / ' + stats.total_count + ' 완료 (' + progress + '%)';
            html += '</div>';
        }
        
        // Task list
        if (tasks.length > 0) {
            html += '<div class="task-list">';
            
            tasks.forEach(function(task) {
                var typeClass = 'concept';
                var typeLabel = '선행';
                
                if (task.type === 'review') {
                    typeClass = 'review';
                    typeLabel = '복습';
                } else if (task.type === 'wrongnote') {
                    typeClass = 'wrongnote';
                    typeLabel = '오답';
                }
                
                html += '<div class="task-item" data-taskid="' + task.id + '">';
                html += '<div style="display: flex; align-items: flex-start;">';
                html += '<input type="checkbox" class="task-checkbox" id="task-' + task.id + '" ';
                html += task.completed ? 'checked' : '';
                html += ' data-taskid="' + task.id + '">';
                html += '<label class="task-label" for="task-' + task.id + '">';
                html += '<div>' + task.title + '</div>';
                html += '<div class="task-meta">';
                html += '<span class="badge badge-' + typeClass + '">' + typeLabel + '</span>';
                
                if (task.durationmin) {
                    html += '<span class="badge" style="background: #f5f5f5;">' + task.durationmin + '분</span>';
                }
                
                if (task.countdown_label) {
                    html += '<span class="badge badge-countdown">' + task.countdown_label + '</span>';
                }
                
                html += '</div>';
                html += '</label>';
                html += '</div>';
                html += '</div>';
            });
            
            html += '</div>';
        } else {
            html += '<div style="padding: 20px; text-align: center; color: #666;">';
            html += '<p>오늘 할 일이 없습니다! 🎉</p>';
            html += '</div>';
        }
        
        // Update widget body
        $('#routinecoach-widget .widget-body').html(html);
        
        // Bind task completion handlers
        bindTaskHandlers();
    };
    
    /**
     * Show error message in widget
     * @param {String} message Error message
     */
    var showWidgetError = function(message) {
        var html = '<div style="padding: 20px; text-align: center; color: #d32f2f;">';
        html += '<p>⚠️ ' + message + '</p>';
        html += '<button onclick="location.reload()" style="' +
            'margin-top: 10px; padding: 6px 12px; background: #667eea; color: white; ' +
            'border: none; border-radius: 4px; cursor: pointer;">다시 시도</button>';
        html += '</div>';
        
        $('#routinecoach-widget .widget-body').html(html);
    };
    
    /**
     * Bind task completion event handlers
     */
    var bindTaskHandlers = function() {
        $('.task-checkbox').off('change').on('change', function() {
            var $checkbox = $(this);
            var taskid = $checkbox.data('taskid');
            var completed = $checkbox.is(':checked');
            
            updateTaskStatus(taskid, completed);
        });
    };
    
    /**
     * Update task completion status
     * @param {Number} taskid Task ID
     * @param {Boolean} completed Completion status
     */
    var updateTaskStatus = function(taskid, completed) {
        var userid = getCurrentUserId();
        
        $.ajax({
            url: apiBaseUrl,
            type: 'POST',
            data: {
                action: 'complete',
                taskid: taskid,
                userid: userid,
                completed: completed ? 1 : 0,
                sesskey: M.cfg.sesskey || ''
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Update UI
                    var $item = $('.task-item[data-taskid="' + taskid + '"]');
                    if (completed) {
                        $item.fadeOut(300, function() {
                            // Reload tasks to update stats
                            loadTodayTasks(userid);
                        });
                    }
                } else {
                    // Revert checkbox
                    $('#task-' + taskid).prop('checked', !completed);
                    alert(response.message || '처리 중 오류가 발생했습니다.');
                }
            },
            error: function(xhr, status, error) {
                // Revert checkbox
                $('#task-' + taskid).prop('checked', !completed);
                console.error('RoutineCoach: Failed to update task', error);
            }
        });
    };
    
    /**
     * Set up global event handlers
     */
    var setupEventHandlers = function() {
        // Auto-refresh every 5 minutes
        setInterval(function() {
            if (widgetInjected) {
                var userid = getCurrentUserId();
                if (userid) {
                    loadTodayTasks(userid);
                }
            }
        }, 300000); // 5 minutes
    };
    
    return {
        init: init,
        injectWidget: injectWidget,
        loadTodayTasks: loadTodayTasks
    };
});