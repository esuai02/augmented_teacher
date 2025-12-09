<?php
/**
 * Spiral Algorithm Rules Configuration
 * 
 * @package    OmniUI
 * @subpackage spiral
 * @copyright  2024 MathKing
 */

// 7:3 Spiral Learning Rules
$SPIRAL_RULES = [
    // Core Ratio Configuration
    'ratio' => [
        'preview' => 0.7,     // 선행학습 70%
        'review' => 0.3,      // 복습 30%
        'flexibility' => 0.1  // ±10% 유연성 허용
    ],
    
    // Timing Rules
    'timing' => [
        'preview_start_before_exam' => 14,  // 시험 D-14부터 선행
        'review_intensify_before_exam' => 7, // 시험 D-7부터 복습 강화
        'daily_switch_enabled' => true,      // 일일 내 선행→복습 전환
        'optimal_study_hours' => [
            'morning' => [6, 8],    // 06:00-08:00
            'afternoon' => [15, 17], // 15:00-17:00
            'evening' => [19, 21]    // 19:00-21:00
        ]
    ],
    
    // Priority Weights
    'priority' => [
        'core_units' => 1.5,         // 핵심 단원 가중치
        'weak_areas' => 1.3,         // 취약 영역 가중치
        'recent_errors' => 1.4,      // 최근 오답 영역
        'reviewed_discount' => 0.8,  // 이미 복습한 내용
        'mastered_discount' => 0.5   // 완전 숙달 내용
    ],
    
    // Cognitive Load Management
    'cognitive_load' => [
        'max_new_concepts_per_day' => 3,
        'max_difficulty_sum_per_session' => 12, // Sum of difficulty levels
        'min_break_between_hard_topics' => 30,  // Minutes
        'interleaving_enabled' => true,
        'spaced_repetition_intervals' => [1, 3, 7, 14] // Days
    ],
    
    // Session Distribution
    'session_distribution' => [
        'weekday' => [
            'sessions' => 2,
            'duration' => [30, 45],  // Min-Max minutes
            'preview_weight' => 0.8
        ],
        'weekend' => [
            'sessions' => 3,
            'duration' => [40, 60],
            'preview_weight' => 0.6
        ],
        'exam_week' => [
            'sessions' => 2,
            'duration' => [25, 40],
            'preview_weight' => 0.3  // More review
        ]
    ],
    
    // Unit Selection Algorithm
    'unit_selection' => [
        'method' => 'weighted_random',  // or 'sequential', 'adaptive'
        'factors' => [
            'exam_weight' => 0.3,
            'difficulty' => 0.2,
            'prerequisite' => 0.2,
            'student_performance' => 0.2,
            'time_remaining' => 0.1
        ]
    ],
    
    // Conflict Resolution Strategies
    'conflict_resolution' => [
        'strategies' => [
            'time_shift' => [
                'priority' => 1,
                'max_shift_minutes' => 30
            ],
            'duration_reduce' => [
                'priority' => 2,
                'min_duration_minutes' => 20
            ],
            'session_merge' => [
                'priority' => 3,
                'max_merge_duration' => 60
            ],
            'day_redistribution' => [
                'priority' => 4,
                'max_sessions_per_day' => 4
            ]
        ]
    ],
    
    // Performance Adaptation
    'performance_adaptation' => [
        'enabled' => true,
        'metrics' => [
            'completion_rate_weight' => 0.3,
            'accuracy_weight' => 0.4,
            'speed_weight' => 0.3
        ],
        'adjustments' => [
            'low_performance' => [
                'threshold' => 0.6,
                'duration_increase' => 1.2,
                'difficulty_decrease' => 0.8
            ],
            'high_performance' => [
                'threshold' => 0.85,
                'duration_decrease' => 0.9,
                'difficulty_increase' => 1.1
            ]
        ]
    ],
    
    // Special Cases
    'special_cases' => [
        'cramming_mode' => [
            'days_before_exam' => 3,
            'preview_ratio' => 0.2,
            'review_ratio' => 0.8,
            'session_duration_multiplier' => 1.3
        ],
        'recovery_mode' => [
            'missed_sessions_threshold' => 3,
            'catch_up_sessions_per_day' => 1,
            'priority_boost' => 1.5
        ]
    ]
];

// Algorithm Helper Functions
function calculate_unit_priority($unit, $student_data, $exam_date) {
    global $SPIRAL_RULES;
    
    $priority = 1.0;
    
    // Apply exam weight
    if (isset($unit['exam_weight'])) {
        $priority *= (1 + $unit['exam_weight'] * 0.5);
    }
    
    // Apply difficulty weight
    if (isset($unit['difficulty'])) {
        $priority *= $SPIRAL_RULES['priority']['core_units'] * 
                     ($unit['difficulty'] / 5);
    }
    
    // Apply student performance
    if (isset($student_data['weak_areas'][$unit['id']])) {
        $priority *= $SPIRAL_RULES['priority']['weak_areas'];
    }
    
    // Apply time factor
    $days_until_exam = (strtotime($exam_date) - time()) / 86400;
    if ($days_until_exam < 7) {
        $priority *= 1.3;
    }
    
    return $priority;
}

function distribute_time_by_ratio($total_minutes, $preview_ratio = 0.7) {
    return [
        'preview_minutes' => round($total_minutes * $preview_ratio),
        'review_minutes' => round($total_minutes * (1 - $preview_ratio))
    ];
}

function get_optimal_session_times($date, $student_preferences = []) {
    global $SPIRAL_RULES;
    
    $day_of_week = date('w', strtotime($date));
    $is_weekend = ($day_of_week == 0 || $day_of_week == 6);
    
    $config = $is_weekend ? 
              $SPIRAL_RULES['session_distribution']['weekend'] :
              $SPIRAL_RULES['session_distribution']['weekday'];
    
    $sessions = [];
    $time_slots = $SPIRAL_RULES['timing']['optimal_study_hours'];
    
    // Select optimal times based on preferences or defaults
    if (!empty($student_preferences['preferred_times'])) {
        $time_slots = $student_preferences['preferred_times'];
    }
    
    foreach ($time_slots as $period => $hours) {
        if (count($sessions) < $config['sessions']) {
            $sessions[] = [
                'period' => $period,
                'start_hour' => $hours[0],
                'end_hour' => $hours[1],
                'duration' => rand($config['duration'][0], $config['duration'][1])
            ];
        }
    }
    
    return $sessions;
}

function apply_spaced_repetition($unit_id, $last_study_date) {
    global $SPIRAL_RULES;
    
    $intervals = $SPIRAL_RULES['cognitive_load']['spaced_repetition_intervals'];
    $days_since = (time() - strtotime($last_study_date)) / 86400;
    
    foreach ($intervals as $interval) {
        if (abs($days_since - $interval) < 1) {
            return true; // Time for review
        }
    }
    
    return false;
}

// Export rules for use in other modules
return $SPIRAL_RULES;