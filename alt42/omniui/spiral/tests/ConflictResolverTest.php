<?php declare(strict_types=1);
/**
 * ConflictResolver Unit Tests
 * 
 * @package    local_spiral
 * @copyright  2024 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_spiral\tests;

use PHPUnit\Framework\TestCase;
use omniui\spiral\core\ConflictResolver;

/**
 * Test cases for ConflictResolver
 */
class ConflictResolverTest extends TestCase {
    
    private ConflictResolver $resolver;
    
    protected function setUp(): void {
        parent::setUp();
        $this->resolver = new ConflictResolver();
    }
    
    /**
     * Test TIME_OVERLAP conflict detection
     */
    public function testTimeOverlapDetection(): void {
        $sessions = [
            [
                'date' => '2024-01-01',
                'time' => '09:00',
                'duration' => 60,
                'type' => 'preview',
                'unit_id' => 'unit_1'
            ],
            [
                'date' => '2024-01-01',
                'time' => '09:30',  // Overlaps with first session
                'duration' => 60,
                'type' => 'review',
                'unit_id' => 'unit_2'
            ]
        ];
        
        $conflicts = $this->resolver->scan($sessions);
        
        $this->assertNotEmpty($conflicts);
        
        $timeOverlapFound = false;
        foreach ($conflicts as $conflict) {
            if ($conflict['type'] === 'TIME_OVERLAP') {
                $timeOverlapFound = true;
                $this->assertCount(2, $conflict['sessions']);
                break;
            }
        }
        
        $this->assertTrue($timeOverlapFound, 'TIME_OVERLAP conflict should be detected');
    }
    
    /**
     * Test PREREQUISITE conflict detection
     */
    public function testPrerequisiteConflictDetection(): void {
        $sessions = [
            [
                'date' => '2024-01-02',
                'time' => '10:00',
                'duration' => 40,
                'type' => 'review',  // Review before preview
                'unit_id' => 'unit_1'
            ],
            [
                'date' => '2024-01-03',
                'time' => '10:00',
                'duration' => 40,
                'type' => 'preview',
                'unit_id' => 'unit_1'
            ]
        ];
        
        $conflicts = $this->resolver->scan($sessions);
        
        $prerequisiteFound = false;
        foreach ($conflicts as $conflict) {
            if ($conflict['type'] === 'PREREQUISITE') {
                $prerequisiteFound = true;
                $this->assertEquals('unit_1', $conflict['unit_id']);
                break;
            }
        }
        
        $this->assertTrue($prerequisiteFound, 'PREREQUISITE conflict should be detected');
    }
    
    /**
     * Test COGNITIVE_LOAD conflict detection
     */
    public function testCognitiveLoadConflictDetection(): void {
        $sessions = [
            [
                'date' => '2024-01-01',
                'time' => '09:00',
                'duration' => 40,
                'type' => 'preview',
                'difficulty' => 5,  // High difficulty
                'unit_id' => 'unit_1'
            ],
            [
                'date' => '2024-01-01',
                'time' => '10:00',
                'duration' => 40,
                'type' => 'preview',
                'difficulty' => 5,  // High difficulty
                'unit_id' => 'unit_2'
            ],
            [
                'date' => '2024-01-01',
                'time' => '11:00',
                'duration' => 40,
                'type' => 'preview',
                'difficulty' => 5,  // High difficulty - too many high difficulty in one day
                'unit_id' => 'unit_3'
            ]
        ];
        
        $conflicts = $this->resolver->scan($sessions);
        
        $cognitiveLoadFound = false;
        foreach ($conflicts as $conflict) {
            if ($conflict['type'] === 'COGNITIVE_LOAD') {
                $cognitiveLoadFound = true;
                $this->assertGreaterThan(10, $conflict['daily_load']);
                break;
            }
        }
        
        $this->assertTrue($cognitiveLoadFound, 'COGNITIVE_LOAD conflict should be detected');
    }
    
    /**
     * Test PHYSICAL_LIMIT conflict detection
     */
    public function testPhysicalLimitConflictDetection(): void {
        $sessions = [];
        
        // 하루에 5시간 이상의 세션
        for ($i = 0; $i < 8; $i++) {
            $sessions[] = [
                'date' => '2024-01-01',
                'time' => sprintf('%02d:00', 9 + $i),
                'duration' => 40,  // 8 * 40분 = 320분 (5시간 20분)
                'type' => 'preview',
                'unit_id' => 'unit_' . ($i + 1)
            ];
        }
        
        $conflicts = $this->resolver->scan($sessions);
        
        $physicalLimitFound = false;
        foreach ($conflicts as $conflict) {
            if ($conflict['type'] === 'PHYSICAL_LIMIT') {
                $physicalLimitFound = true;
                $this->assertGreaterThan(300, $conflict['total_minutes']);
                break;
            }
        }
        
        $this->assertTrue($physicalLimitFound, 'PHYSICAL_LIMIT conflict should be detected');
    }
    
    /**
     * Test conflict resolution - shift strategy
     */
    public function testResolveShiftStrategy(): void {
        $conflict = [
            'type' => 'TIME_OVERLAP',
            'sessions' => [0, 1],
            'overlap_minutes' => 30
        ];
        
        $resolution = $this->resolver->resolve($conflict, 'shift');
        
        $this->assertEquals('shift', $resolution['strategy']);
        $this->assertArrayHasKey('new_time', $resolution);
        $this->assertArrayHasKey('affected_sessions', $resolution);
    }
    
    /**
     * Test conflict resolution - shrink strategy
     */
    public function testResolveShrinkStrategy(): void {
        $conflict = [
            'type' => 'COGNITIVE_LOAD',
            'date' => '2024-01-01',
            'daily_load' => 15,
            'sessions' => [0, 1, 2]
        ];
        
        $resolution = $this->resolver->resolve($conflict, 'shrink');
        
        $this->assertEquals('shrink', $resolution['strategy']);
        $this->assertArrayHasKey('new_duration', $resolution);
        $this->assertLessThan(40, $resolution['new_duration']);  // Duration should be reduced
    }
    
    /**
     * Test conflict resolution - move strategy
     */
    public function testResolveMoveStrategy(): void {
        $conflict = [
            'type' => 'PHYSICAL_LIMIT',
            'date' => '2024-01-01',
            'total_minutes' => 360,
            'sessions' => [0, 1, 2, 3, 4, 5]
        ];
        
        $resolution = $this->resolver->resolve($conflict, 'move');
        
        $this->assertEquals('move', $resolution['strategy']);
        $this->assertArrayHasKey('new_date', $resolution);
        $this->assertNotEquals('2024-01-01', $resolution['new_date']);
    }
    
    /**
     * Test no conflicts scenario
     */
    public function testNoConflicts(): void {
        $sessions = [
            [
                'date' => '2024-01-01',
                'time' => '09:00',
                'duration' => 40,
                'type' => 'preview',
                'difficulty' => 3,
                'unit_id' => 'unit_1'
            ],
            [
                'date' => '2024-01-01',
                'time' => '14:00',  // Well separated
                'duration' => 40,
                'type' => 'review',
                'difficulty' => 2,
                'unit_id' => 'unit_2'
            ],
            [
                'date' => '2024-01-02',
                'time' => '10:00',
                'duration' => 40,
                'type' => 'preview',
                'difficulty' => 3,
                'unit_id' => 'unit_3'
            ]
        ];
        
        $conflicts = $this->resolver->scan($sessions);
        
        $this->assertEmpty($conflicts, 'No conflicts should be detected');
    }
    
    /**
     * Test multiple conflicts detection
     */
    public function testMultipleConflictsDetection(): void {
        $sessions = [
            // Time overlap
            ['date' => '2024-01-01', 'time' => '09:00', 'duration' => 60, 'type' => 'preview', 'unit_id' => 'unit_1', 'difficulty' => 5],
            ['date' => '2024-01-01', 'time' => '09:30', 'duration' => 60, 'type' => 'preview', 'unit_id' => 'unit_2', 'difficulty' => 5],
            
            // Prerequisite violation
            ['date' => '2024-01-01', 'time' => '14:00', 'duration' => 40, 'type' => 'review', 'unit_id' => 'unit_3', 'difficulty' => 3],
            ['date' => '2024-01-02', 'time' => '09:00', 'duration' => 40, 'type' => 'preview', 'unit_id' => 'unit_3', 'difficulty' => 3],
            
            // More sessions for potential cognitive/physical conflicts
            ['date' => '2024-01-01', 'time' => '15:00', 'duration' => 60, 'type' => 'preview', 'unit_id' => 'unit_4', 'difficulty' => 5],
            ['date' => '2024-01-01', 'time' => '16:30', 'duration' => 60, 'type' => 'preview', 'unit_id' => 'unit_5', 'difficulty' => 4],
        ];
        
        $conflicts = $this->resolver->scan($sessions);
        
        $this->assertGreaterThan(1, count($conflicts), 'Multiple conflicts should be detected');
        
        $conflictTypes = array_column($conflicts, 'type');
        $this->assertContains('TIME_OVERLAP', $conflictTypes);
        $this->assertContains('PREREQUISITE', $conflictTypes);
    }
    
    /**
     * Test edge case - empty sessions
     */
    public function testEmptySessions(): void {
        $conflicts = $this->resolver->scan([]);
        
        $this->assertIsArray($conflicts);
        $this->assertEmpty($conflicts);
    }
    
    /**
     * Test edge case - single session
     */
    public function testSingleSession(): void {
        $sessions = [
            [
                'date' => '2024-01-01',
                'time' => '10:00',
                'duration' => 40,
                'type' => 'preview',
                'unit_id' => 'unit_1'
            ]
        ];
        
        $conflicts = $this->resolver->scan($sessions);
        
        $this->assertEmpty($conflicts, 'Single session should have no conflicts');
    }
    
    /**
     * Test resolution priority
     */
    public function testResolutionPriority(): void {
        $conflicts = [
            ['type' => 'TIME_OVERLAP', 'priority' => 1],
            ['type' => 'PREREQUISITE', 'priority' => 2],
            ['type' => 'COGNITIVE_LOAD', 'priority' => 3],
            ['type' => 'PHYSICAL_LIMIT', 'priority' => 4]
        ];
        
        // TIME_OVERLAP should be resolved first (highest priority)
        $resolution = $this->resolver->resolve($conflicts[0], 'auto');
        $this->assertNotNull($resolution);
        
        // Verify automatic strategy selection
        $this->assertContains($resolution['strategy'], ['shift', 'shrink', 'move']);
    }
}