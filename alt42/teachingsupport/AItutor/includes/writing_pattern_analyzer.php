<?php
/**
 * 필기 패턴 분석기
 * 필기 스트로크 데이터를 분석하여 패턴을 감지하고 유추
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 * @version    1.0
 */

class WritingPatternAnalyzer {
    
    /**
     * 필기 패턴 감지
     * 
     * @param array $strokeData 스트로크 데이터
     * @param array $timingData 타이밍 데이터
     * @return array 감지된 패턴 목록
     */
    public function detectPatterns($strokeData, $timingData) {
        $patterns = [];
        
        // 멈춤 패턴 감지
        $pausePatterns = $this->detectPausePatterns($timingData);
        $patterns = array_merge($patterns, $pausePatterns);
        
        // 지우기 패턴 감지
        $erasePatterns = $this->detectErasePatterns($strokeData);
        $patterns = array_merge($patterns, $erasePatterns);
        
        // 덧쓰기 패턴 감지
        $overwritePatterns = $this->detectOverwritePatterns($strokeData);
        $patterns = array_merge($patterns, $overwritePatterns);
        
        // 진행 패턴 감지
        $progressPatterns = $this->detectProgressPatterns($strokeData, $timingData);
        $patterns = array_merge($patterns, $progressPatterns);
        
        // 오류 패턴 감지
        $errorPatterns = $this->detectErrorPatterns($strokeData);
        $patterns = array_merge($patterns, $errorPatterns);
        
        return $patterns;
    }
    
    /**
     * 멈춤 패턴 감지
     */
    private function detectPausePatterns($timingData) {
        $patterns = [];
        
        // 연속된 스트로크 간 시간 간격 분석
        for ($i = 0; $i < count($timingData) - 1; $i++) {
            $timeGap = $timingData[$i + 1]['timestamp'] - $timingData[$i]['timestamp'];
            
            if ($timeGap >= 3 && $timeGap < 5) {
                // 3-5초 멈춤
                $patterns[] = [
                    'pattern_id' => 'PATTERN_PAUSE_3S',
                    'pattern_type' => 'pause',
                    'duration' => $timeGap,
                    'confidence' => 0.6,
                    'inference' => '막힘 또는 사고 중'
                ];
            } elseif ($timeGap >= 5 && $timeGap < 10) {
                // 5-10초 멈춤
                $patterns[] = [
                    'pattern_id' => 'PATTERN_PAUSE_5S',
                    'pattern_type' => 'pause',
                    'duration' => $timeGap,
                    'confidence' => 0.7,
                    'inference' => '백지 막힘 가능성'
                ];
            } elseif ($timeGap >= 10) {
                // 10초 이상 멈춤
                $patterns[] = [
                    'pattern_id' => 'PATTERN_PAUSE_10S',
                    'pattern_type' => 'pause',
                    'duration' => $timeGap,
                    'confidence' => 0.9,
                    'inference' => '백지 막힘'
                ];
            }
        }
        
        return $patterns;
    }
    
    /**
     * 지우기 패턴 감지
     */
    private function detectErasePatterns($strokeData) {
        $patterns = [];
        $eraseCount = 0;
        $lastEraseTime = null;
        
        foreach ($strokeData as $stroke) {
            if (isset($stroke['type']) && $stroke['type'] === 'erase') {
                $eraseCount++;
                $lastEraseTime = $stroke['timestamp'];
            }
        }
        
        if ($eraseCount >= 3) {
            $patterns[] = [
                'pattern_id' => 'PATTERN_ERASE_REPEAT',
                'pattern_type' => 'erase',
                'count' => $eraseCount,
                'confidence' => 0.85,
                'inference' => '혼란, 불확실'
            ];
        }
        
        return $patterns;
    }
    
    /**
     * 덧쓰기 패턴 감지
     */
    private function detectOverwritePatterns($strokeData) {
        $patterns = [];
        $positionMap = [];
        
        foreach ($strokeData as $stroke) {
            if (isset($stroke['points'])) {
                foreach ($stroke['points'] as $point) {
                    $key = round($point['x'] / 10) . '_' . round($point['y'] / 10);
                    if (!isset($positionMap[$key])) {
                        $positionMap[$key] = 0;
                    }
                    $positionMap[$key]++;
                }
            }
        }
        
        // 같은 위치에 여러 번 쓰기
        foreach ($positionMap as $key => $count) {
            if ($count >= 2) {
                $patterns[] = [
                    'pattern_id' => 'PATTERN_OVERWRITE',
                    'pattern_type' => 'overwrite',
                    'count' => $count,
                    'confidence' => 0.7,
                    'inference' => '자기 수정 시도'
                ];
                break; // 첫 번째 덧쓰기만 기록
            }
        }
        
        return $patterns;
    }
    
    /**
     * 진행 패턴 감지
     */
    private function detectProgressPatterns($strokeData, $timingData) {
        $patterns = [];
        
        // 빠른 진행 감지
        $totalTime = 0;
        $strokeCount = count($strokeData);
        if ($strokeCount > 0 && count($timingData) > 0) {
            $totalTime = $timingData[count($timingData) - 1]['timestamp'] - $timingData[0]['timestamp'];
            $avgTimePerStroke = $totalTime / $strokeCount;
            
            if ($avgTimePerStroke < 0.5) {
                $patterns[] = [
                    'pattern_id' => 'PATTERN_FAST_PROGRESS',
                    'pattern_type' => 'progress',
                    'avg_time_per_stroke' => $avgTimePerStroke,
                    'confidence' => 0.9,
                    'inference' => '이해하고 진행 중'
                ];
            }
        }
        
        return $patterns;
    }
    
    /**
     * 오류 패턴 감지
     */
    private function detectErrorPatterns($strokeData) {
        $patterns = [];
        
        // 부호 위에 고침 흔적 감지
        foreach ($strokeData as $stroke) {
            if (isset($stroke['overlay']) && $stroke['overlay'] === true) {
                $patterns[] = [
                    'pattern_id' => 'PATTERN_SIGN_CORRECTION',
                    'pattern_type' => 'error',
                    'confidence' => 0.8,
                    'inference' => '부호 실수 가능성'
                ];
                break;
            }
        }
        
        return $patterns;
    }
    
    /**
     * 패턴 분류
     */
    public function classifyPattern($pattern) {
        $type = $pattern['pattern_type'] ?? 'unknown';
        
        $classifications = [
            'pause' => '인지 상태',
            'erase' => '인지 상태',
            'overwrite' => '인지 상태',
            'progress' => '진행 상태',
            'error' => '오류 유형'
        ];
        
        return $classifications[$type] ?? '기타';
    }
    
    /**
     * 패턴 확신도 계산
     */
    public function calculateConfidence($pattern, $context = []) {
        $baseConfidence = $pattern['confidence'] ?? 0.5;
        
        // 컨텍스트 기반 보정
        if (isset($context['student_history'])) {
            // 학생의 과거 패턴과 일치하면 확신도 증가
            $baseConfidence += 0.1;
        }
        
        if (isset($context['time_of_day'])) {
            // 시간대에 따른 보정 (예: 오후 피곤 시간대)
            // 구현 생략
        }
        
        return min(1.0, max(0.0, $baseConfidence));
    }
}

