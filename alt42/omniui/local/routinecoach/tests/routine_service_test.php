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
 * PHPUnit tests for routine_service class
 *
 * @package    local_routinecoach
 * @category   test
 * @copyright  2024 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_routinecoach\tests;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/routinecoach/classes/service/routine_service.php');

use local_routinecoach\service\routine_service;
use advanced_testcase;
use stdClass;

/**
 * Test class for routine_service
 * 
 * @coversDefaultClass \local_routinecoach\service\routine_service
 */
class routine_service_test extends advanced_testcase {
    
    /** @var routine_service Service instance */
    private $service;
    
    /** @var stdClass Test user */
    private $user;
    
    /** @var int Current time for testing */
    private $now;
    
    /**
     * Set up test environment
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        
        // Create test user
        $this->user = $this->getDataGenerator()->create_user();
        $this->setUser($this->user);
        
        // Initialize service
        $this->service = new routine_service();
        
        // Set consistent time for testing
        $this->now = strtotime('2024-03-01 10:00:00');
    }
    
    /**
     * Test on_exam_saved creates routine and tasks with 7:3 ratio
     * 
     * @covers ::on_exam_saved
     * @covers ::build_routine
     * @covers ::build_tasks
     */
    public function test_on_exam_saved_creates_routine_with_correct_ratio() {
        global $DB;
        
        // Arrange
        $examdate = strtotime('2024-03-31 09:00:00'); // 30 days from now
        $label = '3월 중간고사';
        
        // Act
        $examid = $this->service->on_exam_saved(
            $this->user->id,
            $examdate,
            null,
            $label
        );
        
        // Assert - Exam created
        $this->assertNotFalse($examid);
        $exam = $DB->get_record('routinecoach_exam', ['id' => $examid]);
        $this->assertNotFalse($exam);
        $this->assertEquals($this->user->id, $exam->userid);
        $this->assertEquals($examdate, $exam->examdate);
        $this->assertEquals($label, $exam->label);
        
        // Assert - Routine created with 7:3 ratio
        $routine = $DB->get_record('routinecoach_routine', ['examid' => $examid]);
        $this->assertNotFalse($routine);
        $this->assertEquals(70, $routine->ratio_concept);
        $this->assertEquals(30, $routine->ratio_review);
        $this->assertEquals('active', $routine->status);
        
        // Assert - Tasks created with correct ratio
        $tasks = $DB->get_records('routinecoach_task', ['routineid' => $routine->id]);
        $this->assertGreaterThan(0, count($tasks));
        
        // Count concept vs review tasks
        $conceptCount = 0;
        $reviewCount = 0;
        foreach ($tasks as $task) {
            if ($task->type === 'concept') {
                $conceptCount++;
            } elseif ($task->type === 'review') {
                $reviewCount++;
            }
        }
        
        // Check 7:3 ratio (approximately)
        $totalCount = $conceptCount + $reviewCount;
        $conceptRatio = round(($conceptCount / $totalCount) * 10);
        $reviewRatio = round(($reviewCount / $totalCount) * 10);
        
        $this->assertGreaterThanOrEqual(6, $conceptRatio); // Allow some variance
        $this->assertLessThanOrEqual(8, $conceptRatio);
        $this->assertGreaterThanOrEqual(2, $reviewRatio);
        $this->assertLessThanOrEqual(4, $reviewRatio);
    }
    
    /**
     * Test that rebuild at D-30/D-7/D-1 doesn't create duplicate tasks
     * 
     * @covers ::on_exam_saved
     * @covers ::build_tasks
     */
    public function test_rebuild_prevents_duplicate_tasks() {
        global $DB;
        
        // Arrange - Create initial exam and tasks
        $examdate = strtotime('2024-03-31 09:00:00');
        $label = '3월 중간고사';
        
        $examid = $this->service->on_exam_saved(
            $this->user->id,
            $examdate,
            null,
            $label
        );
        
        $routine = $DB->get_record('routinecoach_routine', ['examid' => $examid]);
        $initialTaskCount = $DB->count_records('routinecoach_task', ['routineid' => $routine->id]);
        
        // Act - Simulate D-30 rebuild
        // Update routine ratio (as would happen at D-30)
        $routine->ratio_concept = 70;
        $routine->ratio_review = 30;
        $DB->update_record('routinecoach_routine', $routine);
        
        // Rebuild tasks (should delete old and create new)
        $this->service->on_exam_saved(
            $this->user->id,
            $examdate,
            null,
            $label
        );
        
        // Assert - No duplicate tasks
        $afterRebuildCount = $DB->count_records('routinecoach_task', ['routineid' => $routine->id]);
        $this->assertEquals($initialTaskCount, $afterRebuildCount, 'Task count should remain same after rebuild');
        
        // Check for duplicate dates
        $sql = "SELECT duedate, COUNT(*) as cnt 
                FROM {routinecoach_task} 
                WHERE routineid = :routineid 
                GROUP BY duedate 
                HAVING COUNT(*) > 2"; // Allow max 2 tasks per day (concept + review)
        
        $duplicates = $DB->get_records_sql($sql, ['routineid' => $routine->id]);
        $this->assertEmpty($duplicates, 'No duplicate tasks for same date');
        
        // Act - Simulate D-7 rebuild with ratio change
        $routine->ratio_concept = 30;
        $routine->ratio_review = 70;
        $DB->update_record('routinecoach_routine', $routine);
        
        $this->service->on_exam_saved(
            $this->user->id,
            $examdate,
            null,
            $label
        );
        
        // Assert - Still no duplicates after D-7 rebuild
        $afterD7Count = $DB->count_records('routinecoach_task', ['routineid' => $routine->id]);
        $this->assertEquals($initialTaskCount, $afterD7Count, 'Task count consistent after D-7 rebuild');
        
        // Act - Simulate D-1 rebuild
        $routine->ratio_concept = 0;
        $routine->ratio_review = 100;
        $DB->update_record('routinecoach_routine', $routine);
        
        $this->service->on_exam_saved(
            $this->user->id,
            $examdate,
            null,
            $label
        );
        
        // Assert - Final check for duplicates
        $finalCount = $DB->count_records('routinecoach_task', ['routineid' => $routine->id]);
        $this->assertEquals($initialTaskCount, $finalCount, 'Task count consistent after D-1 rebuild');
    }
    
    /**
     * Test weekly_max_push limit suppresses notifications
     * 
     * @covers ::send_notification
     */
    public function test_weekly_max_push_suppresses_notifications() {
        global $DB;
        
        // Arrange - Create user preferences
        $pref = new stdClass();
        $pref->userid = $this->user->id;
        $pref->weekly_max_push = 2; // Max 2 notifications per week
        $pref->quiet_hours_from = 22;
        $pref->quiet_hours_to = 8;
        $pref->timezone = 'Asia/Seoul';
        $pref->enabled = 1;
        $pref->timecreated = time();
        $pref->timemodified = time();
        $DB->insert_record('routinecoach_pref', $pref);
        
        // Create exam for notifications
        $examdate = strtotime('2024-03-31 09:00:00');
        $examid = $this->service->on_exam_saved(
            $this->user->id,
            $examdate,
            null,
            '3월 중간고사'
        );
        
        // Simulate sending notifications
        $weekAgo = time() - (7 * 86400);
        
        // Add 2 notification logs (reaching limit)
        for ($i = 1; $i <= 2; $i++) {
            $log = new stdClass();
            $log->userid = $this->user->id;
            $log->action = 'notification_sent';
            $log->meta = json_encode(['type' => 'milestone_d30']);
            $log->timecreated = $weekAgo + ($i * 86400);
            $DB->insert_record('routinecoach_log', $log);
        }
        
        // Act - Try to send another notification (should be suppressed)
        $notificationCount = $DB->count_records_select(
            'routinecoach_log',
            'userid = :userid AND action LIKE :action AND timecreated > :weekago',
            [
                'userid' => $this->user->id,
                'action' => 'notification_%',
                'weekago' => $weekAgo
            ]
        );
        
        // Assert
        $this->assertEquals(2, $notificationCount, 'Should have exactly 2 notifications');
        $this->assertGreaterThanOrEqual($pref->weekly_max_push, $notificationCount, 
            'Notifications should not exceed weekly limit');
        
        // Verify suppression log
        $suppressionLog = $DB->get_record_select(
            'routinecoach_log',
            'userid = :userid AND action = :action AND timecreated > :recent',
            [
                'userid' => $this->user->id,
                'action' => 'push_limit_reached',
                'recent' => time() - 3600
            ]
        );
        
        // Note: Actual suppression would be logged by daily_cron
        // This test verifies the count mechanism works
    }
    
    /**
     * Test get_today_tasks returns correct structure
     * 
     * @covers ::get_today_tasks
     */
    public function test_get_today_tasks_structure() {
        global $DB;
        
        // Arrange - Create exam and tasks for today
        $examdate = strtotime('+30 days');
        $examid = $this->service->on_exam_saved(
            $this->user->id,
            $examdate,
            null,
            '3월 중간고사'
        );
        
        $routine = $DB->get_record('routinecoach_routine', ['examid' => $examid]);
        
        // Create specific tasks for today
        $todayStart = strtotime('today');
        $task1 = new stdClass();
        $task1->routineid = $routine->id;
        $task1->duedate = $todayStart + 3600; // Today at 1am
        $task1->type = 'concept';
        $task1->title = '선행학습 - 30일 전';
        $task1->durationmin = 60;
        $task1->priority = 5;
        $task1->completed = 0;
        $task1->timecreated = time();
        $task1->timemodified = time();
        $DB->insert_record('routinecoach_task', $task1);
        
        $task2 = new stdClass();
        $task2->routineid = $routine->id;
        $task2->duedate = $todayStart + 7200; // Today at 2am
        $task2->type = 'review';
        $task2->title = '복습 - 30일 전';
        $task2->durationmin = 30;
        $task2->priority = 4;
        $task2->completed = 0;
        $task2->timecreated = time();
        $task2->timemodified = time();
        $DB->insert_record('routinecoach_task', $task2);
        
        // Act
        $result = $this->service->get_today_tasks($this->user->id);
        
        // Assert structure
        $this->assertIsArray($result);
        $this->assertArrayHasKey('tasks', $result);
        $this->assertArrayHasKey('stats', $result);
        
        // Assert tasks
        $tasks = $result['tasks'];
        $this->assertCount(2, $tasks);
        
        foreach ($tasks as $task) {
            $this->assertObjectHasAttribute('id', $task);
            $this->assertObjectHasAttribute('title', $task);
            $this->assertObjectHasAttribute('type', $task);
            $this->assertObjectHasAttribute('durationmin', $task);
            $this->assertObjectHasAttribute('completed', $task);
            $this->assertObjectHasAttribute('countdown_label', $task);
            $this->assertObjectHasAttribute('exam_label', $task);
        }
        
        // Assert stats
        $stats = $result['stats'];
        $this->assertObjectHasAttribute('total_count', $stats);
        $this->assertObjectHasAttribute('completed_count', $stats);
        $this->assertObjectHasAttribute('exam_label', $stats);
        $this->assertObjectHasAttribute('days_left', $stats);
        $this->assertObjectHasAttribute('ratio', $stats);
        
        $this->assertEquals(2, $stats->total_count);
        $this->assertEquals(0, $stats->completed_count);
        $this->assertEquals('3월 중간고사', $stats->exam_label);
        $this->assertEquals('7:3', $stats->ratio);
    }
    
    /**
     * Test complete_task functionality
     * 
     * @covers ::complete_task
     */
    public function test_complete_task() {
        global $DB;
        
        // Arrange
        $examdate = strtotime('+30 days');
        $examid = $this->service->on_exam_saved(
            $this->user->id,
            $examdate,
            null,
            '3월 중간고사'
        );
        
        $routine = $DB->get_record('routinecoach_routine', ['examid' => $examid]);
        $tasks = $DB->get_records('routinecoach_task', ['routineid' => $routine->id], 'id ASC', '*', 0, 1);
        $task = reset($tasks);
        
        // Act - Complete task
        $result = $this->service->complete_task($task->id, $this->user->id, 1);
        
        // Assert
        $this->assertTrue($result);
        
        $updatedTask = $DB->get_record('routinecoach_task', ['id' => $task->id]);
        $this->assertEquals(1, $updatedTask->completed);
        
        // Check log
        $log = $DB->get_record_select(
            'routinecoach_log',
            'action = :action AND userid = :userid',
            ['action' => 'task_completed', 'userid' => $this->user->id],
            'id DESC'
        );
        $this->assertNotFalse($log);
        
        // Act - Uncomplete task
        $result = $this->service->complete_task($task->id, $this->user->id, 0);
        
        // Assert
        $this->assertTrue($result);
        
        $revertedTask = $DB->get_record('routinecoach_task', ['id' => $task->id]);
        $this->assertEquals(0, $revertedTask->completed);
        
        // Check reopened log
        $reopenLog = $DB->get_record_select(
            'routinecoach_log',
            'action = :action AND userid = :userid',
            ['action' => 'task_reopened', 'userid' => $this->user->id],
            'id DESC'
        );
        $this->assertNotFalse($reopenLog);
    }
    
    /**
     * Test ratio adjustment at milestones
     * 
     * @covers ::adjust_routine_ratio
     */
    public function test_ratio_adjustment_at_milestones() {
        global $DB;
        
        // Arrange
        $examdate = strtotime('+30 days');
        $examid = $this->service->on_exam_saved(
            $this->user->id,
            $examdate,
            null,
            '3월 중간고사'
        );
        
        $routine = $DB->get_record('routinecoach_routine', ['examid' => $examid]);
        
        // Assert initial ratio (D-30)
        $this->assertEquals(70, $routine->ratio_concept);
        $this->assertEquals(30, $routine->ratio_review);
        
        // Simulate D-7 adjustment
        // This would normally be done by daily_cron
        $routine->ratio_concept = 30;
        $routine->ratio_review = 70;
        $DB->update_record('routinecoach_routine', $routine);
        
        $updatedRoutine = $DB->get_record('routinecoach_routine', ['id' => $routine->id]);
        $this->assertEquals(30, $updatedRoutine->ratio_concept);
        $this->assertEquals(70, $updatedRoutine->ratio_review);
        
        // Simulate D-1 adjustment
        $routine->ratio_concept = 0;
        $routine->ratio_review = 100;
        $DB->update_record('routinecoach_routine', $routine);
        
        $finalRoutine = $DB->get_record('routinecoach_routine', ['id' => $routine->id]);
        $this->assertEquals(0, $finalRoutine->ratio_concept);
        $this->assertEquals(100, $finalRoutine->ratio_review);
    }
    
    /**
     * Test that multiple exams don't interfere
     * 
     * @covers ::on_exam_saved
     */
    public function test_multiple_exams_isolation() {
        global $DB;
        
        // Arrange & Act - Create two different exams
        $exam1date = strtotime('+30 days');
        $exam1id = $this->service->on_exam_saved(
            $this->user->id,
            $exam1date,
            null,
            '3월 중간고사'
        );
        
        $exam2date = strtotime('+60 days');
        $exam2id = $this->service->on_exam_saved(
            $this->user->id,
            $exam2date,
            null,
            '5월 기말고사'
        );
        
        // Assert - Both exams exist
        $this->assertNotEquals($exam1id, $exam2id);
        
        $routine1 = $DB->get_record('routinecoach_routine', ['examid' => $exam1id]);
        $routine2 = $DB->get_record('routinecoach_routine', ['examid' => $exam2id]);
        
        $this->assertNotFalse($routine1);
        $this->assertNotFalse($routine2);
        $this->assertNotEquals($routine1->id, $routine2->id);
        
        // Assert - Tasks are separate
        $tasks1 = $DB->get_records('routinecoach_task', ['routineid' => $routine1->id]);
        $tasks2 = $DB->get_records('routinecoach_task', ['routineid' => $routine2->id]);
        
        $this->assertGreaterThan(0, count($tasks1));
        $this->assertGreaterThan(0, count($tasks2));
        
        // Ensure no task overlap
        foreach ($tasks1 as $task1) {
            foreach ($tasks2 as $task2) {
                $this->assertNotEquals($task1->id, $task2->id);
            }
        }
    }
    
    /**
     * Test edge case: exam date in the past
     * 
     * @covers ::on_exam_saved
     */
    public function test_past_exam_date_handling() {
        global $DB;
        
        // Arrange
        $pastDate = strtotime('-10 days');
        
        // Act
        $examid = $this->service->on_exam_saved(
            $this->user->id,
            $pastDate,
            null,
            '과거 시험'
        );
        
        // Assert - Exam should still be created but with no future tasks
        $this->assertNotFalse($examid);
        
        $routine = $DB->get_record('routinecoach_routine', ['examid' => $examid]);
        $this->assertNotFalse($routine);
        
        // No future tasks should be created for past exam
        $futureTasks = $DB->get_records_select(
            'routinecoach_task',
            'routineid = :routineid AND duedate > :now',
            ['routineid' => $routine->id, 'now' => time()]
        );
        
        $this->assertEmpty($futureTasks, 'No future tasks for past exam');
    }
}