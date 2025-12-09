<?php declare(strict_types=1);
/**
 * TimeAllocator Unit Tests
 * 
 * @package    local_spiral
 * @copyright  2024 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_spiral\tests;

use PHPUnit\Framework\TestCase;
use omniui\spiral\core\TimeAllocator;

/**
 * Test cases for TimeAllocator
 */
class TimeAllocatorTest extends TestCase {
    
    private TimeAllocator $allocator;
    
    protected function setUp(): void {
        parent::setUp();
        $this->allocator = new TimeAllocator();
    }
    
    /**
     * Test daily limit enforcement
     */
    public function testDailyLimitEnforcement(): void {
        $days = [
            ['date' => '2024-01-01', 'limit' => 120, 'weight' => 1.0],
            ['date' => '2024-01-02', 'limit' => 90, 'weight' => 1.0],
            ['date' => '2024-01-03', 'limit' => 150, 'weight' => 1.0],
        ];
        
        $totalMinutes = 360;
        $constraints = [
            'min' => 30,
            'max' => 60,
            'break' => 10
        ];
        
        $result = $this->allocator->allocate($days, $totalMinutes, $constraints);
        
        // 각 날짜별로 일일 제한 확인
        foreach ($result as $index => $dayAllocation) {
            $totalDayMinutes = array_sum(array_column($dayAllocation['slots'], 'duration'));
            
            $this->assertLessThanOrEqual(
                $days[$index]['limit'],
                $totalDayMinutes,
                "Day {$dayAllocation['date']} exceeds limit"
            );
        }
    }
    
    /**
     * Test session duration constraints
     */
    public function testSessionDurationConstraints(): void {
        $days = [
            ['date' => '2024-01-01', 'limit' => 200, 'weight' => 1.0],
        ];
        
        $constraints = [
            'min' => 20,
            'max' => 50,
            'break' => 10
        ];
        
        $result = $this->allocator->allocate($days, 180, $constraints);
        
        foreach ($result[0]['slots'] as $slot) {
            $this->assertGreaterThanOrEqual(20, $slot['duration'], 'Session too short');
            $this->assertLessThanOrEqual(50, $slot['duration'], 'Session too long');
        }
    }
    
    /**
     * Test weight-based distribution
     */
    public function testWeightBasedDistribution(): void {
        $days = [
            ['date' => '2024-01-01', 'limit' => 150, 'weight' => 1.0],  // 평일
            ['date' => '2024-01-02', 'limit' => 150, 'weight' => 1.5],  // 주말 (가중치 높음)
        ];
        
        $totalMinutes = 200;
        $constraints = ['min' => 30, 'max' => 60, 'break' => 10];
        
        $result = $this->allocator->allocate($days, $totalMinutes, $constraints);
        
        $day1Minutes = array_sum(array_column($result[0]['slots'], 'duration'));
        $day2Minutes = array_sum(array_column($result[1]['slots'], 'duration'));
        
        // 가중치가 높은 날에 더 많은 시간 할당
        $this->assertGreaterThan($day1Minutes, $day2Minutes, 'Higher weight should get more time');
    }
    
    /**
     * Test subject distribution
     */
    public function testSubjectDistribution(): void {
        $days = [
            ['date' => '2024-01-01', 'limit' => 180, 'weight' => 1.0],
        ];
        
        $subjects = [
            'math' => 0.4,      // 40%
            'korean' => 0.3,    // 30%
            'english' => 0.3    // 30%
        ];
        
        $result = $this->allocator->allocate($days, 180, [], $subjects);
        
        // 과목별 시간 집계
        $subjectTotals = ['math' => 0, 'korean' => 0, 'english' => 0];
        foreach ($result[0]['slots'] as $slot) {
            if (isset($slot['subject']) && isset($subjectTotals[$slot['subject']])) {
                $subjectTotals[$slot['subject']] += $slot['duration'];
            }
        }
        
        $totalAllocated = array_sum($subjectTotals);
        
        // 과목별 비율 확인 (±10% 허용)
        if ($totalAllocated > 0) {
            $mathRatio = $subjectTotals['math'] / $totalAllocated;
            $koreanRatio = $subjectTotals['korean'] / $totalAllocated;
            $englishRatio = $subjectTotals['english'] / $totalAllocated;
            
            $this->assertEqualsWithDelta(0.4, $mathRatio, 0.1, 'Math ratio');
            $this->assertEqualsWithDelta(0.3, $koreanRatio, 0.1, 'Korean ratio');
            $this->assertEqualsWithDelta(0.3, $englishRatio, 0.1, 'English ratio');
        }
    }
    
    /**
     * Test break time between sessions
     */
    public function testBreakTimeBetweenSessions(): void {
        $days = [
            ['date' => '2024-01-01', 'limit' => 150, 'weight' => 1.0],
        ];
        
        $constraints = [
            'min' => 30,
            'max' => 40,
            'break' => 15  // 15분 휴식
        ];
        
        $result = $this->allocator->allocate($days, 120, $constraints);
        
        $slots = $result[0]['slots'];
        
        // 세션이 2개 이상인 경우 휴식 시간 확인
        if (count($slots) >= 2) {
            for ($i = 1; $i < count($slots); $i++) {
                if (isset($slots[$i]['start_time']) && isset($slots[$i-1]['end_time'])) {
                    $breakTime = strtotime($slots[$i]['start_time']) - strtotime($slots[$i-1]['end_time']);
                    $this->assertGreaterThanOrEqual(15 * 60, $breakTime, 'Insufficient break time');
                }
            }
        }
    }
    
    /**
     * Test empty days handling
     */
    public function testEmptyDaysHandling(): void {
        $result = $this->allocator->allocate([], 100, []);
        
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
    
    /**
     * Test zero total minutes
     */
    public function testZeroTotalMinutes(): void {
        $days = [
            ['date' => '2024-01-01', 'limit' => 120, 'weight' => 1.0],
        ];
        
        $result = $this->allocator->allocate($days, 0, []);
        
        $this->assertCount(1, $result);
        $this->assertEmpty($result[0]['slots']);
    }
    
    /**
     * Test insufficient time for minimum session
     */
    public function testInsufficientTimeForMinimumSession(): void {
        $days = [
            ['date' => '2024-01-01', 'limit' => 100, 'weight' => 1.0],
        ];
        
        $constraints = [
            'min' => 60,  // 최소 60분
            'max' => 90
        ];
        
        $result = $this->allocator->allocate($days, 30, $constraints);  // 30분만 할당
        
        // 최소 시간보다 적으면 할당하지 않거나 최소값으로 조정
        if (!empty($result[0]['slots'])) {
            $this->assertGreaterThanOrEqual(30, $result[0]['slots'][0]['duration']);
        }
    }
    
    /**
     * Test multiple days allocation
     */
    public function testMultipleDaysAllocation(): void {
        $days = [];
        for ($i = 1; $i <= 7; $i++) {
            $days[] = [
                'date' => sprintf('2024-01-%02d', $i),
                'limit' => 120,
                'weight' => ($i === 6 || $i === 7) ? 1.2 : 1.0  // 주말 가중치
            ];
        }
        
        $totalMinutes = 600;
        $constraints = ['min' => 30, 'max' => 60, 'break' => 10];
        
        $result = $this->allocator->allocate($days, $totalMinutes, $constraints);
        
        $this->assertCount(7, $result);
        
        $totalAllocated = 0;
        foreach ($result as $day) {
            $dayTotal = array_sum(array_column($day['slots'], 'duration'));
            $totalAllocated += $dayTotal;
        }
        
        // 전체 할당 시간이 요청한 시간과 근사해야 함
        $this->assertEqualsWithDelta($totalMinutes, $totalAllocated, $totalMinutes * 0.1);
    }
    
    /**
     * Test slot metadata
     */
    public function testSlotMetadata(): void {
        $days = [
            ['date' => '2024-01-01', 'limit' => 100, 'weight' => 1.0],
        ];
        
        $result = $this->allocator->allocate($days, 60, ['min' => 30, 'max' => 60]);
        
        if (!empty($result[0]['slots'])) {
            $slot = $result[0]['slots'][0];
            
            $this->assertArrayHasKey('duration', $slot);
            $this->assertArrayHasKey('subject', $slot);
            $this->assertIsInt($slot['duration']);
            $this->assertIsString($slot['subject']);
        }
    }
    
    /**
     * Test extreme constraints
     */
    public function testExtremeConstraints(): void {
        $days = [
            ['date' => '2024-01-01', 'limit' => 500, 'weight' => 1.0],
        ];
        
        // 매우 큰 최소값
        $constraints1 = ['min' => 180, 'max' => 240];
        $result1 = $this->allocator->allocate($days, 400, $constraints1);
        
        if (!empty($result1[0]['slots'])) {
            foreach ($result1[0]['slots'] as $slot) {
                $this->assertGreaterThanOrEqual(180, $slot['duration']);
            }
        }
        
        // 매우 작은 최대값
        $constraints2 = ['min' => 5, 'max' => 10];
        $result2 = $this->allocator->allocate($days, 100, $constraints2);
        
        if (!empty($result2[0]['slots'])) {
            foreach ($result2[0]['slots'] as $slot) {
                $this->assertLessThanOrEqual(10, $slot['duration']);
            }
        }
    }
}