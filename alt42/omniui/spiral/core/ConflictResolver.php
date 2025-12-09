<?php declare(strict_types=1);
/**
 * Conflict Resolver - 충돌 감지 및 해결 엔진
 * 
 * @package    OmniUI
 * @subpackage spiral/core
 * @copyright  2024 MathKing
 */

namespace omniui\spiral\core;

final class ConflictResolver {
    
    public const TYPES = [
        'TIME_OVERLAP',
        'PREREQUISITE', 
        'COGNITIVE_LOAD',
        'PHYSICAL_LIMIT'
    ];
    
    /**
     * 세션 배열 검사하여 충돌 목록 반환
     * 
     * @param array $sessions 세션 배열
     * @return array 충돌 목록
     */
    public function scan(array $sessions): array {
        $conflicts = [];
        
        // 1. TIME_OVERLAP 검사
        $timeConflicts = $this->scanTimeOverlap($sessions);
        if (!empty($timeConflicts)) {
            $conflicts = array_merge($conflicts, $timeConflicts);
        }
        
        // 2. PREREQUISITE 검사
        $prereqConflicts = $this->scanPrerequisites($sessions);
        if (!empty($prereqConflicts)) {
            $conflicts = array_merge($conflicts, $prereqConflicts);
        }
        
        // 3. COGNITIVE_LOAD 검사
        $cognitiveConflicts = $this->scanCognitiveLoad($sessions);
        if (!empty($cognitiveConflicts)) {
            $conflicts = array_merge($conflicts, $cognitiveConflicts);
        }
        
        // 4. PHYSICAL_LIMIT 검사
        $physicalConflicts = $this->scanPhysicalLimits($sessions);
        if (!empty($physicalConflicts)) {
            $conflicts = array_merge($conflicts, $physicalConflicts);
        }
        
        return $conflicts;
    }
    
    /**
     * 충돌 해결
     * 
     * @param array $sessions 세션 배열
     * @param array $policy 해결 정책
     * @return array 해결 결과
     */
    public function resolve(array $sessions, array $policy = []): array {
        // 기본 정책 설정
        $policy = array_merge([
            'max_daily' => 120,
            'shift_step' => 10,
            'min_duration' => 20,
            'max_difficulty_sum' => 12
        ], $policy);
        
        $changes = [];
        $conflicts = $this->scan($sessions);
        
        foreach ($conflicts as $conflict) {
            switch ($conflict['type']) {
                case 'TIME_OVERLAP':
                    $resolution = $this->resolveTimeOverlap($sessions, $conflict, $policy);
                    break;
                    
                case 'PHYSICAL_LIMIT':
                    $resolution = $this->resolvePhysicalLimit($sessions, $conflict, $policy);
                    break;
                    
                case 'COGNITIVE_LOAD':
                    $resolution = $this->resolveCognitiveLoad($sessions, $conflict, $policy);
                    break;
                    
                case 'PREREQUISITE':
                    $resolution = $this->resolvePrerequisite($sessions, $conflict, $policy);
                    break;
                    
                default:
                    $resolution = null;
            }
            
            if ($resolution !== null) {
                $sessions = $resolution['sessions'];
                $changes = array_merge($changes, $resolution['changes']);
            }
        }
        
        return [
            'sessions' => $sessions,
            'changes' => $changes,
            'resolved_count' => count($changes),
            'remaining_conflicts' => $this->scan($sessions)
        ];
    }
    
    /**
     * 시간 중복 검사
     */
    private function scanTimeOverlap(array $sessions): array {
        $conflicts = [];
        $timeSlots = [];
        
        foreach ($sessions as $index => $session) {
            if (!isset($session['date']) || !isset($session['time'])) {
                continue;
            }
            
            $key = $session['date'] . '_' . ($session['student_id'] ?? 'default');
            
            if (!isset($timeSlots[$key])) {
                $timeSlots[$key] = [];
            }
            
            $startTime = strtotime($session['date'] . ' ' . $session['time']);
            $endTime = $startTime + (($session['duration'] ?? 30) * 60);
            
            // 기존 슬롯과 비교
            foreach ($timeSlots[$key] as $existingSlot) {
                $existingStart = $existingSlot['start'];
                $existingEnd = $existingSlot['end'];
                
                // 겹침 검사
                if (($startTime >= $existingStart && $startTime < $existingEnd) ||
                    ($endTime > $existingStart && $endTime <= $existingEnd) ||
                    ($startTime <= $existingStart && $endTime >= $existingEnd)) {
                    
                    $conflicts[] = [
                        'type' => 'TIME_OVERLAP',
                        'session_ids' => [$existingSlot['id'], $index],
                        'detail' => [
                            'date' => $session['date'],
                            'time1' => $existingSlot['time'],
                            'time2' => $session['time']
                        ]
                    ];
                }
            }
            
            $timeSlots[$key][] = [
                'id' => $index,
                'start' => $startTime,
                'end' => $endTime,
                'time' => $session['time']
            ];
        }
        
        return $conflicts;
    }
    
    /**
     * 선수학습 검사
     */
    private function scanPrerequisites(array $sessions): array {
        $conflicts = [];
        
        foreach ($sessions as $index => $session) {
            if (isset($session['meta']['prereq_done']) && $session['meta']['prereq_done'] === false) {
                $conflicts[] = [
                    'type' => 'PREREQUISITE',
                    'session_ids' => [$index],
                    'detail' => [
                        'unit_id' => $session['unit_id'] ?? null,
                        'missing_prereq' => $session['meta']['prereq_list'] ?? []
                    ]
                ];
            }
        }
        
        return $conflicts;
    }
    
    /**
     * 인지 부하 검사
     */
    private function scanCognitiveLoad(array $sessions, float $threshold = 12.0): array {
        $conflicts = [];
        $dailyLoads = [];
        
        foreach ($sessions as $index => $session) {
            $date = $session['date'] ?? '';
            $difficulty = $session['difficulty'] ?? 3;
            
            if (!isset($dailyLoads[$date])) {
                $dailyLoads[$date] = [
                    'total' => 0,
                    'sessions' => []
                ];
            }
            
            $dailyLoads[$date]['total'] += $difficulty;
            $dailyLoads[$date]['sessions'][] = $index;
        }
        
        foreach ($dailyLoads as $date => $load) {
            if ($load['total'] > $threshold) {
                $conflicts[] = [
                    'type' => 'COGNITIVE_LOAD',
                    'session_ids' => $load['sessions'],
                    'detail' => [
                        'date' => $date,
                        'total_difficulty' => $load['total'],
                        'threshold' => $threshold,
                        'excess' => $load['total'] - $threshold
                    ]
                ];
            }
        }
        
        return $conflicts;
    }
    
    /**
     * 물리적 한계 검사
     */
    private function scanPhysicalLimits(array $sessions, int $dailyLimit = 120): array {
        $conflicts = [];
        $dailyTotals = [];
        
        foreach ($sessions as $index => $session) {
            $date = $session['date'] ?? '';
            $duration = $session['duration'] ?? 30;
            
            if (!isset($dailyTotals[$date])) {
                $dailyTotals[$date] = [
                    'total' => 0,
                    'sessions' => []
                ];
            }
            
            $dailyTotals[$date]['total'] += $duration;
            $dailyTotals[$date]['sessions'][] = $index;
        }
        
        foreach ($dailyTotals as $date => $total) {
            if ($total['total'] > $dailyLimit) {
                $conflicts[] = [
                    'type' => 'PHYSICAL_LIMIT',
                    'session_ids' => $total['sessions'],
                    'detail' => [
                        'date' => $date,
                        'total_minutes' => $total['total'],
                        'limit' => $dailyLimit,
                        'excess' => $total['total'] - $dailyLimit
                    ]
                ];
            }
        }
        
        return $conflicts;
    }
    
    /**
     * 시간 중복 해결
     */
    private function resolveTimeOverlap(array &$sessions, array $conflict, array $policy): array {
        $changes = [];
        $shiftStep = $policy['shift_step'];
        
        // 두 번째 세션을 뒤로 이동
        if (isset($conflict['session_ids'][1])) {
            $sessionId = $conflict['session_ids'][1];
            
            if (isset($sessions[$sessionId])) {
                $currentTime = strtotime($sessions[$sessionId]['date'] . ' ' . $sessions[$sessionId]['time']);
                $newTime = $currentTime + ($shiftStep * 60);
                
                $sessions[$sessionId]['time'] = date('H:i:s', $newTime);
                
                $changes[] = [
                    'session_id' => $sessionId,
                    'action' => 'shift',
                    'detail' => [
                        'minutes' => $shiftStep,
                        'new_time' => $sessions[$sessionId]['time']
                    ]
                ];
            }
        }
        
        return [
            'sessions' => $sessions,
            'changes' => $changes
        ];
    }
    
    /**
     * 물리적 한계 해결
     */
    private function resolvePhysicalLimit(array &$sessions, array $conflict, array $policy): array {
        $changes = [];
        $excessMinutes = $conflict['detail']['excess'] ?? 0;
        $sessionIds = $conflict['session_ids'] ?? [];
        
        // 우선순위가 낮은 세션부터 단축
        $sortedIds = $this->sortByPriority($sessions, $sessionIds);
        
        foreach ($sortedIds as $sessionId) {
            if ($excessMinutes <= 0) break;
            
            if (isset($sessions[$sessionId])) {
                $currentDuration = $sessions[$sessionId]['duration'] ?? 30;
                $reduction = min(10, $excessMinutes, $currentDuration - $policy['min_duration']);
                
                if ($reduction > 0) {
                    $sessions[$sessionId]['duration'] = $currentDuration - $reduction;
                    $excessMinutes -= $reduction;
                    
                    $changes[] = [
                        'session_id' => $sessionId,
                        'action' => 'shrink',
                        'detail' => [
                            'reduced_by' => $reduction,
                            'new_duration' => $sessions[$sessionId]['duration']
                        ]
                    ];
                }
            }
        }
        
        return [
            'sessions' => $sessions,
            'changes' => $changes
        ];
    }
    
    /**
     * 인지 부하 해결
     */
    private function resolveCognitiveLoad(array &$sessions, array $conflict, array $policy): array {
        $changes = [];
        $sessionIds = $conflict['session_ids'] ?? [];
        $date = $conflict['detail']['date'] ?? '';
        
        // 일부 review 세션을 다음날로 이동
        foreach ($sessionIds as $sessionId) {
            if (isset($sessions[$sessionId]) && 
                ($sessions[$sessionId]['type'] ?? '') === 'review') {
                
                $currentDate = strtotime($sessions[$sessionId]['date']);
                $nextDate = date('Y-m-d', $currentDate + 86400);
                
                $sessions[$sessionId]['date'] = $nextDate;
                
                $changes[] = [
                    'session_id' => $sessionId,
                    'action' => 'move',
                    'detail' => [
                        'from_date' => $date,
                        'to_date' => $nextDate
                    ]
                ];
                
                break; // 하나만 이동
            }
        }
        
        return [
            'sessions' => $sessions,
            'changes' => $changes
        ];
    }
    
    /**
     * 선수학습 해결
     */
    private function resolvePrerequisite(array &$sessions, array $conflict, array $policy): array {
        $changes = [];
        $sessionId = $conflict['session_ids'][0] ?? null;
        
        if ($sessionId !== null && isset($sessions[$sessionId])) {
            // 세션을 뒤로 재배치
            $currentDate = strtotime($sessions[$sessionId]['date']);
            $newDate = date('Y-m-d', $currentDate + 86400);
            
            $sessions[$sessionId]['date'] = $newDate;
            $sessions[$sessionId]['meta']['prereq_check_required'] = true;
            
            $changes[] = [
                'session_id' => $sessionId,
                'action' => 'postpone',
                'detail' => [
                    'reason' => 'prerequisite_missing',
                    'new_date' => $newDate
                ]
            ];
        }
        
        return [
            'sessions' => $sessions,
            'changes' => $changes
        ];
    }
    
    /**
     * 우선순위 정렬
     */
    private function sortByPriority(array $sessions, array $sessionIds): array {
        usort($sessionIds, function($a, $b) use ($sessions) {
            $priorityA = $sessions[$a]['priority'] ?? 5;
            $priorityB = $sessions[$b]['priority'] ?? 5;
            return $priorityA <=> $priorityB;
        });
        
        return $sessionIds;
    }
}