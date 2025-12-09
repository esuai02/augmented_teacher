<?php declare(strict_types=1);
/**
 * Ratio Calculator - 7:3 비율 계산 엔진
 * 
 * @package    OmniUI
 * @subpackage spiral/core
 * @copyright  2024 MathKing
 */

namespace omniui\spiral\core;

final class RatioCalculator {
    
    /**
     * 후보 컨텐츠를 α:β 비율로 분할
     * 
     * @param array $candidates 후보 목록 [['type'=>'preview|review', 'weight'=>float, 'unit_id'=>...], ...]
     * @param float $alpha 첫 번째 비율 (예: 0.7)
     * @param float $beta 두 번째 비율 (예: 0.3)
     * @param float $tolerance 허용 오차 (기본 5%)
     * @return array 분할된 결과
     */
    public function split(array $candidates, float $alpha, float $beta, float $tolerance = 0.05): array {
        // 비율 정규화
        $total = $alpha + $beta;
        if ($total == 0) {
            throw new \InvalidArgumentException('Alpha and beta sum cannot be zero');
        }
        
        $normalizedAlpha = $alpha / $total;
        $normalizedBeta = $beta / $total;
        
        // 후보 분류
        $previewCandidates = [];
        $reviewCandidates = [];
        $unassignedCandidates = [];
        
        foreach ($candidates as $candidate) {
            if (!isset($candidate['type']) || $candidate['type'] === '') {
                $unassignedCandidates[] = $candidate;
            } elseif ($candidate['type'] === 'preview') {
                $previewCandidates[] = $candidate;
            } elseif ($candidate['type'] === 'review') {
                $reviewCandidates[] = $candidate;
            }
        }
        
        // 미할당 후보 자동 분류
        if (!empty($unassignedCandidates)) {
            $assigned = $this->autoAssign($unassignedCandidates, $normalizedAlpha, $normalizedBeta);
            $previewCandidates = array_merge($previewCandidates, $assigned['preview']);
            $reviewCandidates = array_merge($reviewCandidates, $assigned['review']);
        }
        
        // 가중치 계산
        $previewWeight = $this->calculateTotalWeight($previewCandidates);
        $reviewWeight = $this->calculateTotalWeight($reviewCandidates);
        $totalWeight = $previewWeight + $reviewWeight;
        
        if ($totalWeight == 0) {
            return [
                'preview' => [],
                'review' => [],
                'ratio' => [
                    'target' => [$normalizedAlpha, $normalizedBeta],
                    'achieved' => [0, 0],
                    'deviation' => 0
                ]
            ];
        }
        
        // 현재 비율 계산
        $currentAlphaRatio = $previewWeight / $totalWeight;
        $currentBetaRatio = $reviewWeight / $totalWeight;
        
        // 비율 조정 필요 여부 확인
        $deviation = abs($currentAlphaRatio - $normalizedAlpha);
        
        if ($deviation > $tolerance) {
            // 비율 조정
            list($previewCandidates, $reviewCandidates) = $this->adjustRatio(
                $previewCandidates,
                $reviewCandidates,
                $normalizedAlpha,
                $normalizedBeta,
                $tolerance
            );
            
            // 조정 후 재계산
            $previewWeight = $this->calculateTotalWeight($previewCandidates);
            $reviewWeight = $this->calculateTotalWeight($reviewCandidates);
            $totalWeight = $previewWeight + $reviewWeight;
            
            if ($totalWeight > 0) {
                $currentAlphaRatio = $previewWeight / $totalWeight;
                $currentBetaRatio = $reviewWeight / $totalWeight;
            }
        }
        
        return [
            'preview' => $previewCandidates,
            'review' => $reviewCandidates,
            'ratio' => [
                'target' => [$normalizedAlpha, $normalizedBeta],
                'achieved' => [
                    round($currentAlphaRatio, 3),
                    round($currentBetaRatio, 3)
                ],
                'deviation' => round($deviation, 3),
                'within_tolerance' => $deviation <= $tolerance
            ]
        ];
    }
    
    /**
     * 미할당 후보 자동 분류
     */
    private function autoAssign(array $candidates, float $alphaRatio, float $betaRatio): array {
        $preview = [];
        $review = [];
        
        // 가중치 기준 정렬 (높은 것부터)
        usort($candidates, function($a, $b) {
            return ($b['weight'] ?? 1.0) <=> ($a['weight'] ?? 1.0);
        });
        
        $totalCount = count($candidates);
        $targetPreviewCount = (int)($totalCount * $alphaRatio);
        
        // 상위 항목을 preview로, 나머지를 review로 할당
        foreach ($candidates as $index => $candidate) {
            if ($index < $targetPreviewCount) {
                $candidate['type'] = 'preview';
                $preview[] = $candidate;
            } else {
                $candidate['type'] = 'review';
                $review[] = $candidate;
            }
        }
        
        return ['preview' => $preview, 'review' => $review];
    }
    
    /**
     * 총 가중치 계산
     */
    private function calculateTotalWeight(array $candidates): float {
        $total = 0.0;
        foreach ($candidates as $candidate) {
            $total += ($candidate['weight'] ?? 1.0);
        }
        return $total;
    }
    
    /**
     * 비율 조정
     */
    private function adjustRatio(
        array $preview,
        array $review,
        float $targetAlpha,
        float $targetBeta,
        float $tolerance
    ): array {
        $iterations = 0;
        $maxIterations = 10;
        
        while ($iterations < $maxIterations) {
            $previewWeight = $this->calculateTotalWeight($preview);
            $reviewWeight = $this->calculateTotalWeight($review);
            $totalWeight = $previewWeight + $reviewWeight;
            
            if ($totalWeight == 0) break;
            
            $currentAlpha = $previewWeight / $totalWeight;
            $deviation = abs($currentAlpha - $targetAlpha);
            
            if ($deviation <= $tolerance) {
                break;
            }
            
            // 이동할 항목 선택
            if ($currentAlpha > $targetAlpha && !empty($preview)) {
                // preview에서 review로 이동
                $candidate = $this->selectCandidateForMove($preview, 'min');
                if ($candidate !== null) {
                    $preview = array_filter($preview, fn($c) => $c !== $candidate);
                    $candidate['type'] = 'review';
                    $review[] = $candidate;
                }
            } elseif ($currentAlpha < $targetAlpha && !empty($review)) {
                // review에서 preview로 이동
                $candidate = $this->selectCandidateForMove($review, 'max');
                if ($candidate !== null) {
                    $review = array_filter($review, fn($c) => $c !== $candidate);
                    $candidate['type'] = 'preview';
                    $preview[] = $candidate;
                }
            }
            
            $iterations++;
        }
        
        return [array_values($preview), array_values($review)];
    }
    
    /**
     * 이동할 후보 선택
     */
    private function selectCandidateForMove(array $candidates, string $criteria): ?array {
        if (empty($candidates)) {
            return null;
        }
        
        if ($criteria === 'min') {
            // 가중치가 가장 낮은 항목 선택
            usort($candidates, function($a, $b) {
                return ($a['weight'] ?? 1.0) <=> ($b['weight'] ?? 1.0);
            });
        } else {
            // 가중치가 가장 높은 항목 선택
            usort($candidates, function($a, $b) {
                return ($b['weight'] ?? 1.0) <=> ($a['weight'] ?? 1.0);
            });
        }
        
        return $candidates[0] ?? null;
    }
}