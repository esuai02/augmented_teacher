<?php declare(strict_types=1);
/**
 * Time Allocator - 시간 할당 엔진
 * 
 * @package    OmniUI
 * @subpackage spiral/core
 * @copyright  2024 MathKing
 */

namespace omniui\spiral\core;

final class TimeAllocator {
    
    /**
     * 일자 배열과 총 시간을 받아 세션 슬롯을 생성
     * 
     * @param array $days 일자 배열 [['date'=>'Y-m-d', 'limit'=>int, 'weight'=>float], ...]
     * @param int $totalMinutes 할당할 총 시간(분)
     * @param array $rules 세션 규칙 ['min'=>20, 'max'=>50, 'break'=>10]
     * @param array $subjectAlloc 과목별 할당 비율 ['math'=>0.4, 'korean'=>0.3, 'english'=>0.3]
     * @return array 할당된 시간 슬롯
     */
    public function allocate(array $days, int $totalMinutes, array $rules, array $subjectAlloc = []): array {
        // 기본값 설정
        $minSession = $rules['min'] ?? 20;
        $maxSession = $rules['max'] ?? 50;
        $breakTime = $rules['break'] ?? 10;
        
        // 일자별 가중치 계산
        $totalWeight = 0;
        foreach ($days as $day) {
            $totalWeight += ($day['weight'] ?? 1.0);
        }
        
        if ($totalWeight == 0) {
            $totalWeight = count($days);
        }
        
        // 결과 배열 초기화
        $result = [];
        $remainingMinutes = $totalMinutes;
        
        // 각 날짜에 시간 분배
        foreach ($days as $index => $day) {
            $date = $day['date'];
            $dailyLimit = $day['limit'] ?? 120; // 기본 120분
            $weight = $day['weight'] ?? 1.0;
            
            // 가중치 기반 할당량 계산
            $targetMinutes = (int)($totalMinutes * ($weight / $totalWeight));
            
            // 일일 한도 적용
            $allocatedMinutes = min($targetMinutes, $dailyLimit, $remainingMinutes);
            
            // 세션 슬롯 생성
            $slots = $this->createSessions($allocatedMinutes, $minSession, $maxSession, $breakTime);
            
            // 과목 레이블 부여
            if (!empty($subjectAlloc)) {
                $slots = $this->assignSubjects($slots, $subjectAlloc);
            }
            
            $result[] = [
                'date' => $date,
                'slots' => $slots,
                'total' => array_sum(array_column($slots, 'duration')),
                'limit' => $dailyLimit,
                'utilization' => round($allocatedMinutes / $dailyLimit * 100, 1)
            ];
            
            $remainingMinutes -= $allocatedMinutes;
            
            if ($remainingMinutes <= 0) {
                break;
            }
        }
        
        // 남은 시간이 있다면 여유 있는 날에 추가 할당
        if ($remainingMinutes > 0) {
            $result = $this->distributeRemaining($result, $remainingMinutes, $minSession, $maxSession);
        }
        
        return $result;
    }
    
    /**
     * 세션 슬롯 생성
     */
    private function createSessions(int $totalMinutes, int $min, int $max, int $break): array {
        $sessions = [];
        $remaining = $totalMinutes;
        
        while ($remaining >= $min) {
            // 세션 길이 결정 (min~max 사이)
            $sessionLength = min(
                $remaining,
                $this->getOptimalSessionLength($remaining, $min, $max)
            );
            
            $sessions[] = [
                'duration' => $sessionLength,
                'subject' => null,
                'type' => null
            ];
            
            $remaining -= $sessionLength;
            
            // 휴식 시간 고려
            if ($remaining > $min) {
                $remaining -= $break;
            }
        }
        
        return $sessions;
    }
    
    /**
     * 최적 세션 길이 계산
     */
    private function getOptimalSessionLength(int $remaining, int $min, int $max): int {
        if ($remaining <= $max) {
            return $remaining;
        }
        
        // 남은 시간을 균등하게 분할할 수 있는 길이 찾기
        $idealLength = (int)($remaining / ceil($remaining / $max));
        
        // min과 max 범위 내에서 조정
        return max($min, min($max, $idealLength));
    }
    
    /**
     * 과목 레이블 할당
     */
    private function assignSubjects(array $slots, array $subjectAlloc): array {
        $subjects = [];
        $weights = [];
        
        // 과목별 가중치 배열 준비
        foreach ($subjectAlloc as $subject => $weight) {
            $subjects[] = $subject;
            $weights[] = $weight;
        }
        
        // 누적 가중치 계산
        $cumulative = [];
        $sum = 0;
        foreach ($weights as $weight) {
            $sum += $weight;
            $cumulative[] = $sum;
        }
        
        // 각 슬롯에 과목 할당
        foreach ($slots as &$slot) {
            $rand = mt_rand() / mt_getrandmax() * $sum;
            
            for ($i = 0; $i < count($cumulative); $i++) {
                if ($rand <= $cumulative[$i]) {
                    $slot['subject'] = $subjects[$i];
                    break;
                }
            }
        }
        
        return $slots;
    }
    
    /**
     * 남은 시간 재분배
     */
    private function distributeRemaining(array $days, int $remaining, int $min, int $max): array {
        foreach ($days as &$day) {
            if ($remaining <= 0) break;
            
            $available = $day['limit'] - $day['total'];
            if ($available >= $min) {
                $addMinutes = min($available, $remaining, $max);
                
                $day['slots'][] = [
                    'duration' => $addMinutes,
                    'subject' => null,
                    'type' => 'additional'
                ];
                
                $day['total'] += $addMinutes;
                $day['utilization'] = round($day['total'] / $day['limit'] * 100, 1);
                $remaining -= $addMinutes;
            }
        }
        
        return $days;
    }
}