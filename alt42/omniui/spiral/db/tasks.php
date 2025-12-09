<?php
/**
 * Scheduled tasks definition for Spiral Scheduler
 * 
 * @package    local_spiral
 * @copyright  2024 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => '\local_spiral\task\recompute_plans',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '2',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
        'disabled' => 0
    ],
    [
        'classname' => '\local_spiral\task\cleanup_conflicts',
        'blocking' => 0,
        'minute' => '15',
        'hour' => '3',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
        'disabled' => 0
    ],
    [
        'classname' => '\local_spiral\task\notify_teachers',
        'blocking' => 0,
        'minute' => '30',
        'hour' => '8',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '1-5', // Weekdays only
        'disabled' => 0
    ]
];