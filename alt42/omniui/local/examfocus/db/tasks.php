<?php
/**
 * ExamFocus 플러그인 스케줄 태스크 정의
 * 
 * @package    local_examfocus
 * @copyright  2025 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => 'local_examfocus\task\scan_exams',
        'blocking' => 0,
        'minute' => '5',
        'hour' => '0',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
        'disabled' => 0
    ],
    [
        'classname' => 'local_examfocus\task\send_reminders',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '9',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
        'disabled' => 0
    ]
];