<?php declare(strict_types=1);
/**
 * Spiral Scheduler Integration Tests
 * 
 * @package    local_spiral
 * @copyright  2024 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_spiral\tests;

use PHPUnit\Framework\TestCase;
use omniui\spiral\core\SpiralScheduler;
use omniui\spiral\core\TimeAllocator;
use omniui\spiral\core\RatioCalculator;
use omniui\spiral\core\ConflictResolver;

/**
 * Integration test cases for complete Spiral Scheduler workflow
 */
class SpiralSchedulerIntegrationTest extends TestCase {
    
    private SpiralScheduler $scheduler;
    
    protected function setUp(): void {
        parent::setUp();
        
        // Initialize scheduler with dependencies
        $this->scheduler = new SpiralScheduler(
            new TimeAllocator(),
            new RatioCalculator(),
            new ConflictResolver()
        );
    }
    
    /**
     * Test complete schedule generation workflow
     */
    public function testCompleteScheduleGeneration(): void {
        // Prepare test parameters
        $params = [
            'student_id' => 123,
            'teacher_id' => 456,
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
            'hours_per_week' => 14,  // 2 hours per day
            'alpha' => 0.7,  // 70% preview
            'beta' => 0.3,   // 30% review
            'subjects' => [
                'math' => 0.4,
                'korean' => 0.3,
                'english' => 0.3
            ],
            'constraints' => [
                'min_session' => 30,
                'max_session' => 60,
                'break_time' => 10
            ]
        ];
        
        // Generate schedule
        $schedule = $this->scheduler->generate($params);
        
        // Verify schedule structure
        $this->assertArrayHasKey('sessions', $schedule);
        $this->assertArrayHasKey('summary', $schedule);
        $this->assertArrayHasKey('conflicts', $schedule);
        
        // Verify sessions generated
        $this->assertNotEmpty($schedule['sessions']);
        
        // Verify 7:3 ratio achieved
        $previewCount = 0;
        $reviewCount = 0;
        
        foreach ($schedule['sessions'] as $session) {
            if ($session['type'] === 'preview') {
                $previewCount++;
            } elseif ($session['type'] === 'review') {
                $reviewCount++;
            }
        }
        
        $total = $previewCount + $reviewCount;
        $this->assertGreaterThan(0, $total);
        
        $actualPreviewRatio = $previewCount / $total;
        $actualReviewRatio = $reviewCount / $total;
        
        // Check ratio within tolerance (±5%)
        $this->assertEqualsWithDelta(0.7, $actualPreviewRatio, 0.05);
        $this->assertEqualsWithDelta(0.3, $actualReviewRatio, 0.05);
    }
    
    /**
     * Test conflict detection and resolution integration
     */
    public function testConflictDetectionAndResolution(): void {
        // Create schedule with potential conflicts
        $params = [
            'student_id' => 123,
            'teacher_id' => 456,
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-07',  // One week
            'hours_per_week' => 35,  // 5 hours per day - likely to cause conflicts
            'alpha' => 0.7,
            'beta' => 0.3,
            'constraints' => [
                'min_session' => 40,
                'max_session' => 60,
                'break_time' => 10
            ]
        ];
        
        $schedule = $this->scheduler->generate($params);
        
        // Check conflicts detected
        $this->assertArrayHasKey('conflicts', $schedule);
        
        if (!empty($schedule['conflicts'])) {
            // Verify conflict structure
            foreach ($schedule['conflicts'] as $conflict) {
                $this->assertArrayHasKey('type', $conflict);
                $this->assertContains($conflict['type'], [
                    'TIME_OVERLAP',
                    'PREREQUISITE',
                    'COGNITIVE_LOAD',
                    'PHYSICAL_LIMIT'
                ]);
            }
            
            // Test resolution
            $resolved = $this->scheduler->resolveConflicts($schedule);
            
            // Check that some conflicts were resolved
            $this->assertLessThanOrEqual(
                count($schedule['conflicts']),
                count($resolved['conflicts']),
                'Conflicts should be resolved or same'
            );
        }
    }
    
    /**
     * Test subject distribution integration
     */
    public function testSubjectDistributionIntegration(): void {
        $params = [
            'student_id' => 123,
            'teacher_id' => 456,
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-14',  // Two weeks
            'hours_per_week' => 14,
            'alpha' => 0.7,
            'beta' => 0.3,
            'subjects' => [
                'math' => 0.5,     // 50%
                'korean' => 0.25,  // 25%
                'english' => 0.25  // 25%
            ]
        ];
        
        $schedule = $this->scheduler->generate($params);
        
        // Count subject distribution
        $subjectCounts = ['math' => 0, 'korean' => 0, 'english' => 0];
        
        foreach ($schedule['sessions'] as $session) {
            if (isset($session['subject']) && isset($subjectCounts[$session['subject']])) {
                $subjectCounts[$session['subject']]++;
            }
        }
        
        $total = array_sum($subjectCounts);
        
        if ($total > 0) {
            // Check subject ratios (with higher tolerance for integration test)
            $mathRatio = $subjectCounts['math'] / $total;
            $koreanRatio = $subjectCounts['korean'] / $total;
            $englishRatio = $subjectCounts['english'] / $total;
            
            $this->assertEqualsWithDelta(0.5, $mathRatio, 0.15);
            $this->assertEqualsWithDelta(0.25, $koreanRatio, 0.15);
            $this->assertEqualsWithDelta(0.25, $englishRatio, 0.15);
        }
    }
    
    /**
     * Test schedule modification workflow
     */
    public function testScheduleModification(): void {
        // Generate initial schedule
        $params = [
            'student_id' => 123,
            'teacher_id' => 456,
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-07',
            'hours_per_week' => 14,
            'alpha' => 0.7,
            'beta' => 0.3
        ];
        
        $schedule = $this->scheduler->generate($params);
        $originalSessionCount = count($schedule['sessions']);
        
        // Modify schedule - add session
        $newSession = [
            'date' => '2024-01-08',
            'time' => '10:00',
            'duration' => 40,
            'type' => 'review',
            'subject' => 'math',
            'unit_id' => 'unit_extra'
        ];
        
        $modifiedSchedule = $this->scheduler->addSession($schedule, $newSession);
        
        $this->assertCount(
            $originalSessionCount + 1,
            $modifiedSchedule['sessions'],
            'Session should be added'
        );
        
        // Modify schedule - remove session
        if (!empty($modifiedSchedule['sessions'])) {
            $modifiedSchedule = $this->scheduler->removeSession($modifiedSchedule, 0);
            
            $this->assertCount(
                $originalSessionCount,
                $modifiedSchedule['sessions'],
                'Session should be removed'
            );
        }
        
        // Modify schedule - update session
        if (!empty($modifiedSchedule['sessions'])) {
            $updates = ['duration' => 50, 'time' => '11:00'];
            $modifiedSchedule = $this->scheduler->updateSession($modifiedSchedule, 0, $updates);
            
            $this->assertEquals(50, $modifiedSchedule['sessions'][0]['duration']);
            $this->assertEquals('11:00', $modifiedSchedule['sessions'][0]['time']);
        }
    }
    
    /**
     * Test edge cases in integration
     */
    public function testEdgeCases(): void {
        // Test with minimal parameters
        $minimalParams = [
            'student_id' => 1,
            'teacher_id' => 2,
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-01',  // Single day
            'hours_per_week' => 1,        // Minimal hours
            'alpha' => 0.7,
            'beta' => 0.3
        ];
        
        $schedule = $this->scheduler->generate($minimalParams);
        $this->assertIsArray($schedule);
        $this->assertArrayHasKey('sessions', $schedule);
        
        // Test with maximum parameters
        $maximalParams = [
            'student_id' => 999,
            'teacher_id' => 888,
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',  // Full year
            'hours_per_week' => 40,       // Heavy load
            'alpha' => 0.9,               // Extreme ratio
            'beta' => 0.1,
            'subjects' => [
                'math' => 0.2,
                'korean' => 0.2,
                'english' => 0.2,
                'science' => 0.2,
                'history' => 0.2
            ]
        ];
        
        $schedule = $this->scheduler->generate($maximalParams);
        $this->assertIsArray($schedule);
        $this->assertNotEmpty($schedule['sessions']);
    }
    
    /**
     * Test schedule validation
     */
    public function testScheduleValidation(): void {
        $params = [
            'student_id' => 123,
            'teacher_id' => 456,
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
            'hours_per_week' => 14,
            'alpha' => 0.7,
            'beta' => 0.3
        ];
        
        $schedule = $this->scheduler->generate($params);
        
        // Validate schedule structure
        $validation = $this->scheduler->validate($schedule);
        
        $this->assertTrue($validation['valid']);
        $this->assertArrayHasKey('ratio_check', $validation);
        $this->assertArrayHasKey('conflict_check', $validation);
        $this->assertArrayHasKey('constraint_check', $validation);
        
        // Check ratio validation
        $this->assertTrue($validation['ratio_check']['passed']);
        $this->assertEqualsWithDelta(
            0.7,
            $validation['ratio_check']['actual_preview'],
            0.05
        );
        
        // Check conflict validation
        if (!empty($schedule['conflicts'])) {
            $this->assertFalse($validation['conflict_check']['passed']);
            $this->assertGreaterThan(0, $validation['conflict_check']['count']);
        } else {
            $this->assertTrue($validation['conflict_check']['passed']);
        }
    }
    
    /**
     * Test performance with large dataset
     */
    public function testPerformanceWithLargeDataset(): void {
        $startTime = microtime(true);
        
        // Generate schedule for 3 months
        $params = [
            'student_id' => 123,
            'teacher_id' => 456,
            'start_date' => '2024-01-01',
            'end_date' => '2024-03-31',  // 3 months
            'hours_per_week' => 20,
            'alpha' => 0.7,
            'beta' => 0.3,
            'subjects' => [
                'math' => 0.35,
                'korean' => 0.35,
                'english' => 0.30
            ]
        ];
        
        $schedule = $this->scheduler->generate($params);
        
        $executionTime = microtime(true) - $startTime;
        
        // Performance assertions
        $this->assertLessThan(5.0, $executionTime, 'Generation should complete within 5 seconds');
        $this->assertNotEmpty($schedule['sessions']);
        
        // Verify scalability
        $sessionCount = count($schedule['sessions']);
        $this->assertGreaterThan(100, $sessionCount, 'Should generate many sessions for 3 months');
        
        // Verify ratio maintained at scale
        $previewSessions = array_filter($schedule['sessions'], fn($s) => $s['type'] === 'preview');
        $reviewSessions = array_filter($schedule['sessions'], fn($s) => $s['type'] === 'review');
        
        $previewRatio = count($previewSessions) / $sessionCount;
        $reviewRatio = count($reviewSessions) / $sessionCount;
        
        $this->assertEqualsWithDelta(0.7, $previewRatio, 0.05);
        $this->assertEqualsWithDelta(0.3, $reviewRatio, 0.05);
    }
    
    /**
     * Test transaction-like behavior
     */
    public function testTransactionBehavior(): void {
        $params = [
            'student_id' => 123,
            'teacher_id' => 456,
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-07',
            'hours_per_week' => 14,
            'alpha' => 0.7,
            'beta' => 0.3
        ];
        
        // Generate initial schedule
        $originalSchedule = $this->scheduler->generate($params);
        
        try {
            // Start transaction-like operation
            $transactionSchedule = clone (object)$originalSchedule;
            
            // Make multiple changes
            $transactionSchedule = $this->scheduler->addSession($transactionSchedule, [
                'date' => '2024-01-08',
                'time' => '10:00',
                'duration' => 40,
                'type' => 'preview'
            ]);
            
            // Simulate error
            if (count($transactionSchedule['sessions']) > 50) {
                throw new \Exception('Too many sessions');
            }
            
            // If no error, changes are kept
            $finalSchedule = $transactionSchedule;
            
        } catch (\Exception $e) {
            // Rollback to original
            $finalSchedule = $originalSchedule;
        }
        
        // Verify rollback or commit
        $this->assertIsArray($finalSchedule);
        $this->assertArrayHasKey('sessions', $finalSchedule);
    }
}