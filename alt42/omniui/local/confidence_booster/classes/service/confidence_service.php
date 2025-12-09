<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_confidence_booster\service;

defined('MOODLE_INTERNAL') || die();

/**
 * Main service class for Confidence Booster functionality
 *
 * @package    local_confidence_booster
 * @copyright  2024 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class confidence_service {
    
    /**
     * Save a concept summary
     *
     * @param int $userid User ID
     * @param string $concept_title Title of the concept
     * @param string $summary_text Student's summary
     * @param string $chapter Chapter/section name
     * @param int $courseid Course ID (optional)
     * @return int ID of the saved summary
     */
    public static function save_summary($userid, $concept_title, $summary_text, $chapter = '', $courseid = 0) {
        global $DB;
        
        $record = new \stdClass();
        $record->userid = $userid;
        $record->concept_title = $concept_title;
        $record->summary_text = $summary_text;
        $record->chapter = $chapter;
        $record->courseid = $courseid;
        $record->timecreated = time();
        
        // Generate AI feedback (placeholder - will be implemented with actual AI service)
        $record->ai_feedback = self::generate_ai_feedback($summary_text);
        $record->quality_score = self::calculate_quality_score($summary_text);
        
        $id = $DB->insert_record('confidence_notes', $record);
        
        // Update daily metrics
        self::update_metrics($userid, 'summary');
        
        return $id;
    }
    
    /**
     * Log an error classification
     *
     * @param int $userid User ID
     * @param int $questionid Question/problem ID
     * @param string $error_type Type of error (concept/calculation/mistake/application)
     * @param string $error_memo Student's notes about the error
     * @param string $difficulty_level Difficulty level (easy/medium/hard)
     * @return int ID of the logged error
     */
    public static function log_error($userid, $questionid, $error_type, $error_memo = '', $difficulty_level = 'medium') {
        global $DB;
        
        $record = new \stdClass();
        $record->userid = $userid;
        $record->questionid = $questionid;
        $record->error_type = $error_type;
        $record->error_memo = $error_memo;
        $record->difficulty_level = $difficulty_level;
        $record->resolved = 0;
        $record->retry_count = 0;
        $record->timecreated = time();
        
        $id = $DB->insert_record('confidence_errors', $record);
        
        // Update daily metrics
        self::update_metrics($userid, 'error');
        
        return $id;
    }
    
    /**
     * Generate weekly challenge for a student
     *
     * @param int $userid User ID
     * @return array Challenge information
     */
    public static function generate_weekly_challenge($userid) {
        global $DB;
        
        // Get current week number and year
        $week_number = date('W');
        $year = date('Y');
        
        // Check if challenge already exists for this week
        $existing = $DB->get_record('confidence_challenges', [
            'userid' => $userid,
            'week_number' => $week_number,
            'year' => $year
        ]);
        
        if ($existing) {
            return self::format_challenge($existing);
        }
        
        // Determine challenge level based on past performance
        $challenge_level = self::determine_challenge_level($userid);
        
        // Select appropriate questions (placeholder - actual implementation would query question bank)
        $questions = self::select_challenge_questions($userid, $challenge_level);
        
        $record = new \stdClass();
        $record->userid = $userid;
        $record->week_number = $week_number;
        $record->year = $year;
        $record->challenge_level = $challenge_level;
        $record->questions_json = json_encode($questions);
        $record->attempted = 0;
        $record->timecreated = time();
        
        $id = $DB->insert_record('confidence_challenges', $record);
        $record->id = $id;
        
        return self::format_challenge($record);
    }
    
    /**
     * Fetch dashboard data for a student
     *
     * @param int $userid User ID
     * @return array Dashboard data
     */
    public static function fetch_dashboard($userid) {
        global $DB;
        
        $dashboard = [
            'summaries' => [],
            'errors' => [],
            'challenges' => [],
            'metrics' => [],
            'unread_feedback' => 0
        ];
        
        // Get recent summaries (last 7 days)
        $since = time() - (7 * 24 * 60 * 60);
        $summaries = $DB->get_records_select(
            'confidence_notes',
            'userid = ? AND timecreated > ?',
            [$userid, $since],
            'timecreated DESC',
            '*',
            0, 10
        );
        
        foreach ($summaries as $summary) {
            $dashboard['summaries'][] = [
                'id' => $summary->id,
                'title' => $summary->concept_title,
                'text' => substr($summary->summary_text, 0, 100) . '...',
                'score' => $summary->quality_score,
                'feedback' => $summary->ai_feedback,
                'date' => date('Y-m-d', $summary->timecreated)
            ];
        }
        
        // Get error statistics
        $error_stats = $DB->get_records_sql("
            SELECT error_type, COUNT(*) as count 
            FROM {confidence_errors} 
            WHERE userid = ? AND resolved = 0
            GROUP BY error_type
        ", [$userid]);
        
        foreach ($error_stats as $stat) {
            $dashboard['errors'][$stat->error_type] = $stat->count;
        }
        
        // Get current week's challenge
        $week_number = date('W');
        $year = date('Y');
        $challenge = $DB->get_record('confidence_challenges', [
            'userid' => $userid,
            'week_number' => $week_number,
            'year' => $year
        ]);
        
        if ($challenge) {
            $dashboard['challenges'][] = self::format_challenge($challenge);
        }
        
        // Get today's metrics
        $today = date('Y-m-d');
        $metric = $DB->get_record('confidence_metrics', [
            'userid' => $userid,
            'metric_date' => $today
        ]);
        
        if ($metric) {
            $dashboard['metrics'] = [
                'confidence' => $metric->self_confidence,
                'performance' => $metric->actual_performance,
                'summaries_today' => $metric->summary_count,
                'errors_classified' => $metric->error_classified_count
            ];
        }
        
        // Count unread feedback
        $dashboard['unread_feedback'] = $DB->count_records('confidence_feedback', [
            'studentid' => $userid,
            'is_read' => 0
        ]);
        
        return $dashboard;
    }
    
    /**
     * Update daily metrics
     *
     * @param int $userid User ID
     * @param string $action Action type (summary/error/challenge)
     */
    private static function update_metrics($userid, $action) {
        global $DB;
        
        $today = date('Y-m-d');
        
        $metric = $DB->get_record('confidence_metrics', [
            'userid' => $userid,
            'metric_date' => $today
        ]);
        
        if (!$metric) {
            $metric = new \stdClass();
            $metric->userid = $userid;
            $metric->metric_date = $today;
            $metric->summary_count = 0;
            $metric->error_classified_count = 0;
            $metric->challenge_attempted = 0;
            $metric->timecreated = time();
        }
        
        switch ($action) {
            case 'summary':
                $metric->summary_count++;
                break;
            case 'error':
                $metric->error_classified_count++;
                break;
            case 'challenge':
                $metric->challenge_attempted = 1;
                break;
        }
        
        if (isset($metric->id)) {
            $DB->update_record('confidence_metrics', $metric);
        } else {
            $DB->insert_record('confidence_metrics', $metric);
        }
    }
    
    /**
     * Generate AI feedback for a summary (placeholder)
     *
     * @param string $summary_text Summary text
     * @return string AI feedback
     */
    private static function generate_ai_feedback($summary_text) {
        // Placeholder implementation
        $word_count = str_word_count($summary_text);
        
        if ($word_count < 20) {
            return "요약이 너무 짧습니다. 핵심 개념을 더 자세히 설명해보세요.";
        } elseif ($word_count > 100) {
            return "좋은 요약입니다! 다음에는 더 간결하게 핵심만 정리해보세요.";
        } else {
            return "잘 정리했습니다! 핵심 개념을 잘 파악하고 있네요.";
        }
    }
    
    /**
     * Calculate quality score for a summary
     *
     * @param string $summary_text Summary text
     * @return float Quality score (0-5)
     */
    private static function calculate_quality_score($summary_text) {
        // Simple scoring based on length and content
        $word_count = str_word_count($summary_text);
        $has_keywords = preg_match('/(\b정의\b|\b공식\b|\b예시\b|\b중요\b)/u', $summary_text);
        
        $score = min(5, ($word_count / 20) + ($has_keywords ? 1 : 0));
        
        return round($score, 2);
    }
    
    /**
     * Determine challenge level based on past performance
     *
     * @param int $userid User ID
     * @return string Challenge level
     */
    private static function determine_challenge_level($userid) {
        global $DB;
        
        // Get average success rate from past challenges
        $avg_success = $DB->get_field_sql("
            SELECT AVG(success_rate) 
            FROM {confidence_challenges} 
            WHERE userid = ? AND attempted = 1
        ", [$userid]);
        
        if ($avg_success === false || $avg_success < 40) {
            return 'medium';
        } elseif ($avg_success < 70) {
            return 'hard';
        } else {
            return 'extreme';
        }
    }
    
    /**
     * Select questions for challenge (placeholder)
     *
     * @param int $userid User ID
     * @param string $level Challenge level
     * @return array Question IDs
     */
    private static function select_challenge_questions($userid, $level) {
        // Placeholder - would integrate with actual question bank
        switch ($level) {
            case 'extreme':
                return [301, 302, 303];
            case 'hard':
                return [201, 202, 203];
            default:
                return [101, 102, 103];
        }
    }
    
    /**
     * Format challenge data for output
     *
     * @param object $challenge Challenge record
     * @return array Formatted challenge
     */
    private static function format_challenge($challenge) {
        return [
            'id' => $challenge->id,
            'week' => $challenge->week_number,
            'level' => $challenge->challenge_level,
            'questions' => json_decode($challenge->questions_json),
            'attempted' => $challenge->attempted,
            'success_rate' => $challenge->success_rate,
            'badge' => $challenge->badge_earned,
            'created' => date('Y-m-d', $challenge->timecreated)
        ];
    }
}