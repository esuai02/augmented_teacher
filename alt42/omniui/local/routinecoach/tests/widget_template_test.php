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
 * Widget template snapshot tests
 *
 * @package    local_routinecoach
 * @category   test
 * @copyright  2024 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_routinecoach\tests;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use core\output\mustache_engine;

/**
 * Test class for widget template rendering
 */
class widget_template_test extends advanced_testcase {
    
    /** @var mustache_engine Mustache renderer */
    private $mustache;
    
    /**
     * Set up test environment
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        
        // Initialize Mustache engine
        $this->mustache = new mustache_engine([
            'loader' => new \Mustache_Loader_FilesystemLoader(
                __DIR__ . '/../templates'
            )
        ]);
    }
    
    /**
     * Test widget template renders correctly with tasks
     */
    public function test_widget_template_with_tasks() {
        // Arrange - Prepare template context
        $context = [
            'hastasks' => true,
            'tasks' => [
                (object)[
                    'id' => 1,
                    'title' => '선행학습 - 30일 전',
                    'type' => 'concept',
                    'durationmin' => 60,
                    'completed' => 0,
                    'countdown_label' => 'D-30'
                ],
                (object)[
                    'id' => 2,
                    'title' => '복습 - 30일 전',
                    'type' => 'review',
                    'durationmin' => 30,
                    'completed' => 0,
                    'countdown_label' => 'D-30'
                ],
                (object)[
                    'id' => 3,
                    'title' => '오답노트 복습',
                    'type' => 'wrongnote',
                    'durationmin' => 45,
                    'completed' => 1,
                    'countdown_label' => 'D-30'
                ]
            ],
            'stats' => (object)[
                'exam_label' => '3월 중간고사',
                'days_left' => 30,
                'ratio' => '7:3',
                'completed_count' => 1,
                'total_count' => 3,
                'progress_percent' => 33
            ]
        ];
        
        // Act - Render template
        $html = $this->mustache->render('widget', $context);
        
        // Assert - Check key elements exist
        $this->assertStringContainsString('routinecoach-widget', $html);
        $this->assertStringContainsString('3월 중간고사', $html);
        $this->assertStringContainsString('D-30', $html);
        $this->assertStringContainsString('7:3', $html);
        $this->assertStringContainsString('1 / 3 완료', $html);
        $this->assertStringContainsString('33%', $html);
        
        // Assert - Check tasks rendered
        $this->assertStringContainsString('선행학습 - 30일 전', $html);
        $this->assertStringContainsString('복습 - 30일 전', $html);
        $this->assertStringContainsString('오답노트 복습', $html);
        
        // Assert - Check task badges
        $this->assertStringContainsString('badge-concept', $html);
        $this->assertStringContainsString('badge-review', $html);
        $this->assertStringContainsString('badge-wrongnote', $html);
        
        // Assert - Check duration badges
        $this->assertStringContainsString('60분', $html);
        $this->assertStringContainsString('30분', $html);
        $this->assertStringContainsString('45분', $html);
        
        // Assert - Check completed task has checkbox checked
        $this->assertMatchesRegularExpression('/task-3.*checked/s', $html);
        
        // Create snapshot for comparison
        $this->createSnapshot('widget_with_tasks', $html);
    }
    
    /**
     * Test widget template renders correctly without tasks
     */
    public function test_widget_template_without_tasks() {
        // Arrange
        $context = [
            'hastasks' => false,
            'tasks' => [],
            'stats' => (object)[
                'exam_label' => '3월 중간고사',
                'days_left' => 30,
                'ratio' => '7:3',
                'completed_count' => 0,
                'total_count' => 0,
                'progress_percent' => 0
            ]
        ];
        
        // Act
        $html = $this->mustache->render('widget', $context);
        
        // Assert
        $this->assertStringContainsString('오늘 할 일이 없습니다', $html);
        $this->assertStringContainsString('🎉', $html);
        $this->assertStringNotContainsString('task-item', $html);
        
        // Create snapshot
        $this->createSnapshot('widget_without_tasks', $html);
    }
    
    /**
     * Test widget template with various completion states
     */
    public function test_widget_template_completion_states() {
        // Arrange - All tasks completed
        $context = [
            'hastasks' => true,
            'tasks' => [
                (object)[
                    'id' => 1,
                    'title' => '선행학습 - 완료',
                    'type' => 'concept',
                    'durationmin' => 60,
                    'completed' => 1,
                    'countdown_label' => 'D-7'
                ],
                (object)[
                    'id' => 2,
                    'title' => '복습 - 완료',
                    'type' => 'review',
                    'durationmin' => 30,
                    'completed' => 1,
                    'countdown_label' => 'D-7'
                ]
            ],
            'stats' => (object)[
                'exam_label' => '3월 중간고사',
                'days_left' => 7,
                'ratio' => '3:7',
                'completed_count' => 2,
                'total_count' => 2,
                'progress_percent' => 100
            ]
        ];
        
        // Act
        $html = $this->mustache->render('widget', $context);
        
        // Assert - 100% completion
        $this->assertStringContainsString('100%', $html);
        $this->assertStringContainsString('2 / 2 완료', $html);
        
        // Assert - D-7 ratio change
        $this->assertStringContainsString('3:7', $html);
        $this->assertStringContainsString('D-7', $html);
        
        // Create snapshot
        $this->createSnapshot('widget_all_completed', $html);
    }
    
    /**
     * Test widget template with D-1 state
     */
    public function test_widget_template_d1_state() {
        // Arrange - D-1 with review only
        $context = [
            'hastasks' => true,
            'tasks' => [
                (object)[
                    'id' => 1,
                    'title' => '최종 복습 - 1일 전',
                    'type' => 'review',
                    'durationmin' => 120,
                    'completed' => 0,
                    'countdown_label' => 'D-1'
                ]
            ],
            'stats' => (object)[
                'exam_label' => '3월 중간고사',
                'days_left' => 1,
                'ratio' => '0:10',
                'completed_count' => 0,
                'total_count' => 1,
                'progress_percent' => 0
            ]
        ];
        
        // Act
        $html = $this->mustache->render('widget', $context);
        
        // Assert - D-1 specific elements
        $this->assertStringContainsString('D-1', $html);
        $this->assertStringContainsString('0:10', $html);
        $this->assertStringContainsString('최종 복습', $html);
        $this->assertStringContainsString('120분', $html);
        
        // Create snapshot
        $this->createSnapshot('widget_d1_state', $html);
    }
    
    /**
     * Test widget CSS and structure
     */
    public function test_widget_css_structure() {
        // Arrange
        $context = [
            'hastasks' => true,
            'tasks' => [
                (object)[
                    'id' => 1,
                    'title' => 'Test Task',
                    'type' => 'concept',
                    'durationmin' => 30,
                    'completed' => 0,
                    'countdown_label' => 'D-15'
                ]
            ],
            'stats' => (object)[
                'exam_label' => 'Test Exam',
                'days_left' => 15,
                'ratio' => '5:5',
                'completed_count' => 0,
                'total_count' => 1,
                'progress_percent' => 0
            ]
        ];
        
        // Act
        $html = $this->mustache->render('widget', $context);
        
        // Assert - Check CSS classes exist
        $this->assertStringContainsString('widget-header', $html);
        $this->assertStringContainsString('widget-body', $html);
        $this->assertStringContainsString('stats-row', $html);
        $this->assertStringContainsString('progress-bar', $html);
        $this->assertStringContainsString('progress-fill', $html);
        $this->assertStringContainsString('task-list', $html);
        $this->assertStringContainsString('task-item', $html);
        $this->assertStringContainsString('task-checkbox', $html);
        $this->assertStringContainsString('task-label', $html);
        
        // Assert - Check inline styles
        $this->assertStringContainsString('position: fixed', $html);
        $this->assertStringContainsString('bottom: 20px', $html);
        $this->assertStringContainsString('right: 20px', $html);
        $this->assertStringContainsString('z-index: 1000', $html);
        
        // Create snapshot
        $this->createSnapshot('widget_css_structure', $html);
    }
    
    /**
     * Helper method to create/compare snapshots
     * 
     * @param string $name Snapshot name
     * @param string $content Content to snapshot
     */
    private function createSnapshot($name, $content) {
        $snapshotDir = __DIR__ . '/snapshots';
        if (!is_dir($snapshotDir)) {
            mkdir($snapshotDir, 0777, true);
        }
        
        $snapshotFile = $snapshotDir . '/' . $name . '.html';
        
        if (file_exists($snapshotFile)) {
            // Compare with existing snapshot
            $existing = file_get_contents($snapshotFile);
            
            // Normalize for comparison (remove dynamic timestamps, etc.)
            $normalizedExisting = $this->normalizeHtml($existing);
            $normalizedContent = $this->normalizeHtml($content);
            
            $this->assertEquals(
                $normalizedExisting,
                $normalizedContent,
                "Snapshot mismatch for: $name"
            );
        } else {
            // Create new snapshot
            file_put_contents($snapshotFile, $content);
            $this->markTestIncomplete("Created new snapshot: $name");
        }
    }
    
    /**
     * Normalize HTML for snapshot comparison
     * 
     * @param string $html HTML to normalize
     * @return string Normalized HTML
     */
    private function normalizeHtml($html) {
        // Remove dynamic content
        $html = preg_replace('/data-taskid="\d+"/', 'data-taskid="X"', $html);
        $html = preg_replace('/id="task-\d+"/', 'id="task-X"', $html);
        $html = preg_replace('/for="task-\d+"/', 'for="task-X"', $html);
        
        // Normalize whitespace
        $html = preg_replace('/\s+/', ' ', $html);
        $html = trim($html);
        
        return $html;
    }
}