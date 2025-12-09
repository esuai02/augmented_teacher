<?php declare(strict_types=1);
/**
 * RatioCalculator Unit Tests
 * 
 * @package    local_spiral
 * @copyright  2024 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_spiral\tests;

use PHPUnit\Framework\TestCase;
use omniui\spiral\core\RatioCalculator;

/**
 * Test cases for RatioCalculator
 */
class RatioCalculatorTest extends TestCase {
    
    private RatioCalculator $calculator;
    
    protected function setUp(): void {
        parent::setUp();
        $this->calculator = new RatioCalculator();
    }
    
    /**
     * Test 7:3 ratio achievement within 5% tolerance
     */
    public function testStandardRatioAchievement(): void {
        // 100개 후보로 7:3 비율 달성 테스트
        $candidates = $this->generateCandidates(100);
        
        $result = $this->calculator->split($candidates, 0.7, 0.3);
        
        $this->assertArrayHasKey('preview', $result);
        $this->assertArrayHasKey('review', $result);
        $this->assertArrayHasKey('ratio', $result);
        
        // 달성한 비율 확인 (±5% 허용)
        $achievedPreview = $result['ratio']['achieved'][0];
        $achievedReview = $result['ratio']['achieved'][1];
        
        $this->assertEqualsWithDelta(0.7, $achievedPreview, 0.05, 'Preview ratio should be 70% ±5%');
        $this->assertEqualsWithDelta(0.3, $achievedReview, 0.05, 'Review ratio should be 30% ±5%');
        
        // 합이 1이 되는지 확인
        $this->assertEqualsWithDelta(1.0, $achievedPreview + $achievedReview, 0.001);
    }
    
    /**
     * Test boundary values α=0.6 and α=0.8
     */
    public function testBoundaryValues(): void {
        $candidates = $this->generateCandidates(50);
        
        // α=0.6, β=0.4 테스트
        $result60 = $this->calculator->split($candidates, 0.6, 0.4);
        $this->assertEqualsWithDelta(0.6, $result60['ratio']['achieved'][0], 0.05);
        $this->assertEqualsWithDelta(0.4, $result60['ratio']['achieved'][1], 0.05);
        
        // α=0.8, β=0.2 테스트
        $result80 = $this->calculator->split($candidates, 0.8, 0.2);
        $this->assertEqualsWithDelta(0.8, $result80['ratio']['achieved'][0], 0.05);
        $this->assertEqualsWithDelta(0.2, $result80['ratio']['achieved'][1], 0.05);
    }
    
    /**
     * Test with small candidate set
     */
    public function testSmallCandidateSet(): void {
        // 10개만으로 테스트
        $candidates = $this->generateCandidates(10);
        
        $result = $this->calculator->split($candidates, 0.7, 0.3);
        
        // 작은 세트에서도 비율 근사치 달성
        $previewCount = count($result['preview']);
        $reviewCount = count($result['review']);
        $total = $previewCount + $reviewCount;
        
        $this->assertEquals(10, $total, 'All candidates should be assigned');
        
        // 7:3 비율 확인 (작은 세트이므로 허용 오차 증가)
        $actualPreviewRatio = $previewCount / $total;
        $this->assertEqualsWithDelta(0.7, $actualPreviewRatio, 0.15, 'Small set allows larger deviation');
    }
    
    /**
     * Test with weighted candidates
     */
    public function testWeightedCandidates(): void {
        $candidates = [
            ['type' => null, 'weight' => 3.0, 'unit_id' => 'unit_1'],
            ['type' => null, 'weight' => 1.0, 'unit_id' => 'unit_2'],
            ['type' => null, 'weight' => 2.0, 'unit_id' => 'unit_3'],
            ['type' => null, 'weight' => 1.5, 'unit_id' => 'unit_4'],
            ['type' => null, 'weight' => 2.5, 'unit_id' => 'unit_5'],
        ];
        
        $result = $this->calculator->split($candidates, 0.7, 0.3);
        
        // 가중치 합계 계산
        $previewWeight = array_sum(array_column($result['preview'], 'weight'));
        $reviewWeight = array_sum(array_column($result['review'], 'weight'));
        $totalWeight = $previewWeight + $reviewWeight;
        
        // 가중치 기반 비율 확인
        $weightedPreviewRatio = $previewWeight / $totalWeight;
        $weightedReviewRatio = $reviewWeight / $totalWeight;
        
        $this->assertEqualsWithDelta(0.7, $weightedPreviewRatio, 0.1);
        $this->assertEqualsWithDelta(0.3, $weightedReviewRatio, 0.1);
    }
    
    /**
     * Test auto-adjustment mechanism
     */
    public function testAutoAdjustment(): void {
        // 의도적으로 불균형한 초기 할당 시뮬레이션
        $candidates = $this->generateCandidates(100);
        
        // 첫 70개를 preview로 강제 설정
        for ($i = 0; $i < 70; $i++) {
            $candidates[$i]['type'] = 'preview';
        }
        
        $result = $this->calculator->split($candidates, 0.7, 0.3);
        
        // 자동 조정 후에도 목표 비율 달성 확인
        $achievedPreview = $result['ratio']['achieved'][0];
        $achievedReview = $result['ratio']['achieved'][1];
        
        $this->assertEqualsWithDelta(0.7, $achievedPreview, 0.05);
        $this->assertEqualsWithDelta(0.3, $achievedReview, 0.05);
    }
    
    /**
     * Test edge case: empty candidates
     */
    public function testEmptyCandidates(): void {
        $result = $this->calculator->split([], 0.7, 0.3);
        
        $this->assertEmpty($result['preview']);
        $this->assertEmpty($result['review']);
        $this->assertEquals([0, 0], $result['ratio']['achieved']);
    }
    
    /**
     * Test edge case: single candidate
     */
    public function testSingleCandidate(): void {
        $candidates = [
            ['type' => null, 'weight' => 1.0, 'unit_id' => 'unit_1']
        ];
        
        $result = $this->calculator->split($candidates, 0.7, 0.3);
        
        // 단일 후보는 preview에 할당되어야 함 (α > β)
        $this->assertCount(1, $result['preview']);
        $this->assertCount(0, $result['review']);
    }
    
    /**
     * Test invalid ratio inputs
     */
    public function testInvalidRatios(): void {
        $candidates = $this->generateCandidates(10);
        
        // 합이 1이 아닌 경우
        $this->expectException(\InvalidArgumentException::class);
        $this->calculator->split($candidates, 0.6, 0.3);
    }
    
    /**
     * Test extreme ratios
     */
    public function testExtremeRatios(): void {
        $candidates = $this->generateCandidates(100);
        
        // 100% preview
        $result100 = $this->calculator->split($candidates, 1.0, 0.0);
        $this->assertCount(100, $result100['preview']);
        $this->assertCount(0, $result100['review']);
        
        // 100% review
        $result0 = $this->calculator->split($candidates, 0.0, 1.0);
        $this->assertCount(0, $result0['preview']);
        $this->assertCount(100, $result0['review']);
    }
    
    /**
     * Helper: Generate test candidates
     */
    private function generateCandidates(int $count): array {
        $candidates = [];
        for ($i = 0; $i < $count; $i++) {
            $candidates[] = [
                'type' => null,
                'weight' => 1.0 + (mt_rand(0, 20) / 10), // 1.0 ~ 3.0
                'unit_id' => 'unit_' . ($i + 1),
                'subject' => ['math', 'korean', 'english'][mt_rand(0, 2)]
            ];
        }
        return $candidates;
    }
}