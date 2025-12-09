<?php
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
 * MBTI-based personalization service
 *
 * @package    local_routinecoach
 * @copyright  2024 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_routinecoach\service;

defined('MOODLE_INTERNAL') || die();

/**
 * MBTI service for personality-based content generation
 */
class mbti_service {
    
    /**
     * MBTI type categorization
     */
    const TYPE_CATEGORIES = [
        'systematic' => ['ISTJ', 'ESTJ', 'INTJ', 'ENTJ'],  // Checklist lovers
        'enthusiastic' => ['ENFP', 'ESFP', 'ENFJ', 'ESFJ'], // Badge collectors
        'reflective' => ['INFP', 'INFJ', 'ISFP', 'ISFJ'],   // Reflection cards
        'analytical' => ['INTP', 'ENTP', 'ISTP', 'ESTP']    // Progress analytics
    ];
    
    /**
     * Morning message templates by MBTI category
     */
    const MORNING_MESSAGES = [
        'systematic' => [
            '오늘의 학습 체크리스트가 준비되었습니다. {name}님, 단계별로 완료해 나가세요!',
            '{name}님의 오늘 할 일이 정리되었습니다. 체계적으로 하나씩 해결해보세요.',
            '계획된 {count}개의 과제가 있습니다. 순서대로 진행하면 효율적입니다.',
            'D-{days} 학습 계획이 수립되었습니다. 차근차근 실행해보세요.'
        ],
        'enthusiastic' => [
            '🎯 {name}님! 오늘 {count}개의 미션이 기다리고 있어요! 모두 클리어하면 특별 배지를!',
            '✨ 새로운 도전이 시작됩니다! {name}님의 열정으로 오늘도 화이팅!',
            '🏆 오늘의 목표 달성시 경험치 {xp}를 획득할 수 있습니다!',
            '🌟 {name}님의 연속 학습 {streak}일째! 오늘도 이어가볼까요?'
        ],
        'reflective' => [
            '{name}님, 오늘은 어떤 새로운 깨달음을 얻게 될까요? {count}개의 학습이 준비되었습니다.',
            '학습은 자신과의 대화입니다. 오늘의 여정을 시작해보세요.',
            '{name}님의 성장 일기: D-{days}, 오늘도 한 걸음 더 나아가요.',
            '매일의 작은 노력이 큰 변화를 만듭니다. 오늘의 학습을 시작하세요.'
        ],
        'analytical' => [
            '📊 현재 진도율 {progress}%, 오늘 {count}개 완료시 {new_progress}% 달성 예상',
            '분석 결과: 오늘 집중 시간 {focus}분 투자시 최적 효율 달성 가능',
            'D-{days} 기준 필요 학습량: {required}. 오늘 목표: {count}개',
            '통계적으로 {time}에 학습 효율이 가장 높습니다. 지금 시작하세요!'
        ]
    ];
    
    /**
     * Achievement badge definitions
     */
    const BADGES = [
        'daily_warrior' => ['name' => '오늘의 전사', 'condition' => 'daily_complete'],
        'streak_master' => ['name' => '연속 학습왕', 'condition' => 'streak_7'],
        'perfect_week' => ['name' => '완벽한 일주일', 'condition' => 'week_100'],
        'early_bird' => ['name' => '아침형 인간', 'condition' => 'morning_study'],
        'night_owl' => ['name' => '올빼미 학습자', 'condition' => 'night_study'],
        'speed_runner' => ['name' => '스피드러너', 'condition' => 'fast_complete'],
        'deep_diver' => ['name' => '심화 학습자', 'condition' => 'long_study'],
        'comeback_kid' => ['name' => '컴백 히어로', 'condition' => 'recover_streak']
    ];
    
    /**
     * Reflection card prompts
     */
    const REFLECTION_PROMPTS = [
        'morning' => [
            '오늘 가장 집중하고 싶은 한 가지는 무엇인가요?',
            '어제 학습에서 가장 기억에 남는 것은?',
            '오늘의 학습 목표를 한 문장으로 표현한다면?',
            '지금 이 순간, 나의 마음가짐은?'
        ],
        'evening' => [
            '오늘 배운 것 중 가장 중요한 개념은?',
            '내일 더 잘하기 위해 개선할 점은?',
            '오늘의 나에게 점수를 준다면? 그 이유는?',
            '오늘 학습이 나의 목표에 어떻게 기여했나요?'
        ]
    ];
    
    /**
     * Get MBTI category for a type
     */
    public function get_category($mbti_type) {
        foreach (self::TYPE_CATEGORIES as $category => $types) {
            if (in_array(strtoupper($mbti_type), $types)) {
                return $category;
            }
        }
        return 'systematic'; // Default category
    }
    
    /**
     * Generate morning message based on MBTI type
     */
    public function generate_morning_message($mbti_type, $task_count, $username, $additional_data = []) {
        $category = $this->get_category($mbti_type);
        $messages = self::MORNING_MESSAGES[$category];
        $message = $messages[array_rand($messages)];
        
        // Replace placeholders
        $replacements = array_merge([
            '{name}' => $username,
            '{count}' => $task_count,
            '{days}' => $additional_data['days_until'] ?? 30,
            '{xp}' => $task_count * 100,
            '{streak}' => $additional_data['streak'] ?? 1,
            '{progress}' => $additional_data['progress'] ?? 0,
            '{new_progress}' => min(100, ($additional_data['progress'] ?? 0) + 10),
            '{required}' => $task_count + 5,
            '{focus}' => $task_count * 30,
            '{time}' => $this->get_optimal_time($mbti_type)
        ], $additional_data);
        
        foreach ($replacements as $key => $value) {
            $message = str_replace($key, $value, $message);
        }
        
        return $message;
    }
    
    /**
     * Generate checklist for systematic types
     */
    public function generate_checklist($tasks, $mbti_type) {
        $checklist = [
            'title' => '오늘의 학습 체크리스트',
            'type' => 'checklist',
            'mbti_customized' => true,
            'items' => []
        ];
        
        foreach ($tasks as $task) {
            $item = [
                'id' => $task->id,
                'text' => $this->format_task_for_checklist($task),
                'priority' => $this->calculate_priority($task),
                'estimated_time' => $task->durationmin,
                'completed' => $task->completed,
                'category' => $task->type
            ];
            
            // Add sub-steps for complex tasks
            if ($task->durationmin > 30) {
                $item['substeps'] = $this->generate_substeps($task);
            }
            
            $checklist['items'][] = $item;
        }
        
        // Sort by priority
        usort($checklist['items'], function($a, $b) {
            return $b['priority'] - $a['priority'];
        });
        
        return $checklist;
    }
    
    /**
     * Generate badges for enthusiastic types
     */
    public function generate_badges($userid, $progress_data) {
        $badges = [
            'earned' => [],
            'available' => [],
            'locked' => []
        ];
        
        foreach (self::BADGES as $badge_id => $badge) {
            $status = $this->check_badge_condition($badge['condition'], $userid, $progress_data);
            
            $badge_data = [
                'id' => $badge_id,
                'name' => $badge['name'],
                'icon' => $this->get_badge_icon($badge_id),
                'description' => $this->get_badge_description($badge_id),
                'progress' => $status['progress'],
                'earned_date' => $status['earned_date'] ?? null
            ];
            
            if ($status['earned']) {
                $badges['earned'][] = $badge_data;
            } elseif ($status['available']) {
                $badges['available'][] = $badge_data;
            } else {
                $badges['locked'][] = $badge_data;
            }
        }
        
        return $badges;
    }
    
    /**
     * Generate reflection cards for reflective types
     */
    public function generate_reflection_card($mbti_type, $time_of_day = 'morning') {
        $prompts = self::REFLECTION_PROMPTS[$time_of_day] ?? self::REFLECTION_PROMPTS['morning'];
        $prompt = $prompts[array_rand($prompts)];
        
        return [
            'type' => 'reflection',
            'prompt' => $prompt,
            'suggested_response_length' => $this->get_suggested_length($mbti_type),
            'previous_reflections' => $this->get_recent_reflections(),
            'mood_options' => ['motivated', 'focused', 'curious', 'challenged', 'accomplished'],
            'save_enabled' => true
        ];
    }
    
    /**
     * Generate analytics dashboard for analytical types
     */
    public function generate_analytics($userid, $days = 7) {
        global $DB;
        
        $analytics = [
            'type' => 'analytics',
            'period' => $days,
            'metrics' => []
        ];
        
        // Completion rate trend
        $analytics['metrics']['completion_trend'] = $this->calculate_completion_trend($userid, $days);
        
        // Study time distribution
        $analytics['metrics']['time_distribution'] = $this->calculate_time_distribution($userid, $days);
        
        // Performance by task type
        $analytics['metrics']['performance_by_type'] = $this->calculate_performance_by_type($userid, $days);
        
        // Optimal study times
        $analytics['metrics']['optimal_times'] = $this->calculate_optimal_times($userid);
        
        // Predicted exam readiness
        $analytics['metrics']['readiness_score'] = $this->calculate_readiness_score($userid);
        
        // Recommendations
        $analytics['recommendations'] = $this->generate_analytical_recommendations($analytics['metrics']);
        
        return $analytics;
    }
    
    /**
     * Get personalized content based on MBTI type
     */
    public function get_personalized_content($userid, $mbti_type, $context = 'dashboard') {
        global $DB;
        
        $category = $this->get_category($mbti_type);
        $content = [
            'mbti_type' => $mbti_type,
            'category' => $category,
            'timestamp' => time()
        ];
        
        // Get user's tasks and progress
        $tasks = $this->get_user_tasks($userid);
        $progress = $this->get_user_progress($userid);
        
        switch ($category) {
            case 'systematic':
                $content['primary_content'] = $this->generate_checklist($tasks, $mbti_type);
                $content['secondary_content'] = $this->generate_progress_summary($progress);
                break;
                
            case 'enthusiastic':
                $content['primary_content'] = $this->generate_badges($userid, $progress);
                $content['secondary_content'] = $this->generate_gamification_elements($tasks);
                break;
                
            case 'reflective':
                $content['primary_content'] = $this->generate_reflection_card($mbti_type);
                $content['secondary_content'] = $this->generate_growth_journal($userid);
                break;
                
            case 'analytical':
                $content['primary_content'] = $this->generate_analytics($userid);
                $content['secondary_content'] = $this->generate_predictions($progress);
                break;
        }
        
        // Add motivational quote matching personality
        $content['daily_quote'] = $this->get_personality_quote($mbti_type);
        
        return $content;
    }
    
    /**
     * Helper: Format task for checklist
     */
    private function format_task_for_checklist($task) {
        $prefix = $task->type == 'concept' ? '📚' : '📝';
        $subject = $task->subject ?? '수학';
        $duration = $task->durationmin . '분';
        
        return "{$prefix} [{$subject}] {$task->content} ({$duration})";
    }
    
    /**
     * Helper: Calculate task priority
     */
    private function calculate_priority($task) {
        $priority = 50; // Base priority
        
        // Wrong note tasks get higher priority
        if ($task->type == 'wrongnote') {
            $priority += 30;
        }
        
        // Tasks due earlier get higher priority
        $hours_until_due = ($task->duedate - time()) / 3600;
        if ($hours_until_due < 3) {
            $priority += 20;
        } elseif ($hours_until_due < 6) {
            $priority += 10;
        }
        
        return $priority;
    }
    
    /**
     * Helper: Generate substeps for complex tasks
     */
    private function generate_substeps($task) {
        $substeps = [];
        $duration = $task->durationmin;
        
        if ($duration >= 60) {
            $substeps[] = '준비 및 복습 (10분)';
            $substeps[] = '핵심 개념 학습 (20분)';
            $substeps[] = '문제 풀이 (20분)';
            $substeps[] = '정리 및 노트 작성 (10분)';
        } elseif ($duration >= 30) {
            $substeps[] = '개념 확인 (10분)';
            $substeps[] = '문제 풀이 (15분)';
            $substeps[] = '오답 정리 (5분)';
        }
        
        return $substeps;
    }
    
    /**
     * Helper: Check badge condition
     */
    private function check_badge_condition($condition, $userid, $progress_data) {
        $status = [
            'earned' => false,
            'available' => true,
            'progress' => 0
        ];
        
        switch ($condition) {
            case 'daily_complete':
                $status['progress'] = $progress_data['today_completion'] ?? 0;
                $status['earned'] = $status['progress'] >= 100;
                break;
                
            case 'streak_7':
                $status['progress'] = min(100, ($progress_data['streak'] ?? 0) / 7 * 100);
                $status['earned'] = ($progress_data['streak'] ?? 0) >= 7;
                break;
                
            case 'week_100':
                $status['progress'] = $progress_data['week_completion'] ?? 0;
                $status['earned'] = $status['progress'] >= 100;
                break;
                
            // Add more conditions as needed
        }
        
        return $status;
    }
    
    /**
     * Helper: Get optimal study time based on MBTI
     */
    private function get_optimal_time($mbti_type) {
        // Introverts tend to prefer quieter times
        if (strpos($mbti_type, 'I') === 0) {
            return '오전 6-8시';
        }
        // Extroverts might prefer more active times
        return '오후 7-9시';
    }
    
    /**
     * Helper: Get user tasks
     */
    private function get_user_tasks($userid) {
        global $DB;
        
        $today_start = strtotime(date('Y-m-d 00:00:00'));
        $today_end = strtotime(date('Y-m-d 23:59:59'));
        
        $sql = "SELECT t.*, r.examid, r.subjectid 
                FROM {routinecoach_task} t
                JOIN {routinecoach_routine} r ON t.routineid = r.id
                JOIN {routinecoach_exam} e ON r.examid = e.id
                WHERE e.userid = :userid
                  AND t.duedate BETWEEN :start AND :end
                ORDER BY t.duedate ASC";
        
        return $DB->get_records_sql($sql, [
            'userid' => $userid,
            'start' => $today_start,
            'end' => $today_end
        ]);
    }
    
    /**
     * Helper: Get user progress
     */
    private function get_user_progress($userid) {
        global $DB;
        
        // Calculate various progress metrics
        $progress = [];
        
        // Today's completion
        $today_sql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN t.completed = 1 THEN 1 ELSE 0 END) as completed
                      FROM {routinecoach_task} t
                      JOIN {routinecoach_routine} r ON t.routineid = r.id
                      JOIN {routinecoach_exam} e ON r.examid = e.id
                      WHERE e.userid = :userid
                        AND DATE(FROM_UNIXTIME(t.duedate)) = CURDATE()";
        
        $today = $DB->get_record_sql($today_sql, ['userid' => $userid]);
        $progress['today_completion'] = $today->total > 0 
            ? round(($today->completed / $today->total) * 100) 
            : 0;
        
        // Learning streak
        $progress['streak'] = $this->calculate_streak($userid);
        
        // Week completion
        $progress['week_completion'] = $this->calculate_week_completion($userid);
        
        return $progress;
    }
    
    /**
     * Helper: Calculate learning streak
     */
    private function calculate_streak($userid) {
        global $DB;
        
        $streak = 0;
        $date = time();
        
        while (true) {
            $day_start = strtotime(date('Y-m-d 00:00:00', $date));
            $day_end = strtotime(date('Y-m-d 23:59:59', $date));
            
            $sql = "SELECT COUNT(*) as completed
                    FROM {routinecoach_task} t
                    JOIN {routinecoach_routine} r ON t.routineid = r.id
                    JOIN {routinecoach_exam} e ON r.examid = e.id
                    WHERE e.userid = :userid
                      AND t.completed = 1
                      AND t.duedate BETWEEN :start AND :end";
            
            $result = $DB->get_record_sql($sql, [
                'userid' => $userid,
                'start' => $day_start,
                'end' => $day_end
            ]);
            
            if ($result->completed > 0) {
                $streak++;
                $date -= 86400; // Previous day
            } else {
                break;
            }
        }
        
        return $streak;
    }
    
    /**
     * Helper: Get personality-matched quote
     */
    private function get_personality_quote($mbti_type) {
        $quotes = [
            'systematic' => [
                '체계적인 계획이 성공의 절반입니다.',
                '꾸준한 실행이 완벽한 결과를 만듭니다.',
                '단계별 접근이 큰 목표를 달성하게 합니다.'
            ],
            'enthusiastic' => [
                '오늘도 새로운 도전, 설레지 않나요?',
                '당신의 열정이 불가능을 가능하게 만듭니다!',
                '즐기면서 하는 공부가 진짜 실력이 됩니다!'
            ],
            'reflective' => [
                '오늘의 작은 깨달음이 내일의 큰 지혜가 됩니다.',
                '자신과의 대화 속에서 진정한 성장이 시작됩니다.',
                '매일의 성찰이 더 나은 나를 만듭니다.'
            ],
            'analytical' => [
                '데이터가 보여주는 길을 따라가세요.',
                '분석적 사고가 최적의 해답을 찾아냅니다.',
                '측정할 수 없으면 개선할 수 없습니다.'
            ]
        ];
        
        $category = $this->get_category($mbti_type);
        $category_quotes = $quotes[$category] ?? $quotes['systematic'];
        
        return $category_quotes[array_rand($category_quotes)];
    }
    
    /**
     * Helper: Calculate week completion
     */
    private function calculate_week_completion($userid) {
        global $DB;
        
        $week_start = strtotime('monday this week');
        $week_end = strtotime('sunday this week 23:59:59');
        
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN t.completed = 1 THEN 1 ELSE 0 END) as completed
                FROM {routinecoach_task} t
                JOIN {routinecoach_routine} r ON t.routineid = r.id
                JOIN {routinecoach_exam} e ON r.examid = e.id
                WHERE e.userid = :userid
                  AND t.duedate BETWEEN :start AND :end";
        
        $result = $DB->get_record_sql($sql, [
            'userid' => $userid,
            'start' => $week_start,
            'end' => $week_end
        ]);
        
        return $result->total > 0 
            ? round(($result->completed / $result->total) * 100) 
            : 0;
    }
}