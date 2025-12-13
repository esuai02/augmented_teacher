<?php
/**
 * 비침습적 질문 매니저
 * 5가지 비침습적 질문 방식을 관리하고 실행
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 * @version    1.0
 */

class NonIntrusiveQuestionManager {
    
    /**
     * 질문 생성
     */
    public function generateQuestion($inference, $method) {
        $question = [
            'question_id' => 'Q_' . time() . '_' . mt_rand(1000, 9999),
            'type' => $method,
            'content' => $this->getQuestionContent($inference, $method),
            'position' => $this->getQuestionPosition($method),
            'style' => $this->getQuestionStyle($method),
            'interaction' => $this->getInteractionConfig($method),
            'gesture_responses' => $this->getGestureResponses($method),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        return $question;
    }
    
    /**
     * 질문 내용 생성
     */
    private function getQuestionContent($inference, $method) {
        $state = $inference['state'] ?? '';
        
        $contentMapping = [
            'margin_whisper' => [
                '막힘' => '생각 중이야?',
                '백지 막힘' => '막혔어?',
                '혼란' => '어디가 헷갈려?'
            ],
            'breathing_bar' => [
                '막힘' => '괜찮아?',
                '백지 막힘' => '힌트 줄까?'
            ],
            'corner_emoji' => [
                '막힘' => '🤔',
                '혼란' => '🤔'
            ],
            'inline_question' => [
                '막힘' => 'x에 대해 풀어볼까?',
                '혼란' => '이 부분 다시 설명할까?'
            ],
            'gesture_response' => [
                '막힘' => '여기서 막혔어?',
                '혼란' => '어디가 헷갈려?'
            ]
        ];
        
        $mapping = $contentMapping[$method] ?? [];
        return $mapping[$state] ?? '괜찮아?';
    }
    
    /**
     * 질문 위치 설정
     */
    private function getQuestionPosition($method) {
        $positions = [
            'margin_whisper' => [
                'side' => 'right',
                'offset_x' => 10,
                'offset_y' => 0
            ],
            'breathing_bar' => [
                'side' => 'bottom',
                'offset_x' => 0,
                'offset_y' => -2
            ],
            'corner_emoji' => [
                'side' => 'top_right',
                'offset_x' => -30,
                'offset_y' => 10
            ],
            'inline_question' => [
                'side' => 'inline',
                'offset_x' => 5,
                'offset_y' => 0
            ],
            'gesture_response' => [
                'side' => 'center',
                'offset_x' => 0,
                'offset_y' => 0
            ]
        ];
        
        return $positions[$method] ?? $positions['margin_whisper'];
    }
    
    /**
     * 질문 스타일 설정
     */
    private function getQuestionStyle($method) {
        $styles = [
            'margin_whisper' => [
                'font_size' => 12,
                'color' => '#CCCCCC',
                'opacity' => 0.6
            ],
            'breathing_bar' => [
                'height' => 2,
                'color' => '#FFD700', // 노랑
                'animation' => 'breathing'
            ],
            'corner_emoji' => [
                'size' => 24,
                'opacity' => 0.8
            ],
            'inline_question' => [
                'font_size' => 14,
                'color' => '#999999',
                'opacity' => 0.7
            ],
            'gesture_response' => [
                'font_size' => 16,
                'color' => '#666666',
                'opacity' => 0.8
            ]
        ];
        
        return $styles[$method] ?? $styles['margin_whisper'];
    }
    
    /**
     * 상호작용 설정
     */
    private function getInteractionConfig($method) {
        $configs = [
            'margin_whisper' => [
                'auto_hide' => true,
                'hide_after' => 5,
                'on_click' => 'show_response_options'
            ],
            'breathing_bar' => [
                'auto_hide' => false,
                'on_tap' => 'expand_message',
                'animation' => 'breathing'
            ],
            'corner_emoji' => [
                'auto_hide' => false,
                'on_tap' => 'send_state',
                'response_time' => 0.1
            ],
            'inline_question' => [
                'auto_hide' => false,
                'on_click' => 'show_hint'
            ],
            'gesture_response' => [
                'auto_hide' => true,
                'hide_after' => 10,
                'gesture_enabled' => true
            ]
        ];
        
        return $configs[$method] ?? $configs['margin_whisper'];
    }
    
    /**
     * 제스처 응답 설정
     */
    private function getGestureResponses($method) {
        $gestures = [
            'check' => ['meaning' => 'yes', 'response' => '긍정'],
            'cross' => ['meaning' => 'no', 'response' => '부정'],
            'question' => ['meaning' => 'confused', 'response' => '다른 게 헷갈려'],
            'arrow' => ['meaning' => 'continue', 'response' => '괜찮아, 계속 할게']
        ];
        
        return $gestures;
    }
    
    /**
     * 제스처 인식
     */
    public function recognizeGesture($strokeData) {
        // 간단한 제스처 인식 로직
        $points = $strokeData['points'] ?? [];
        if (count($points) < 3) {
            return null;
        }
        
        // 체크 마크 (✓) 인식
        if ($this->isCheckMark($points)) {
            return 'check';
        }
        
        // 엑스 (✗) 인식
        if ($this->isCross($points)) {
            return 'cross';
        }
        
        // 물음표 (?) 인식
        if ($this->isQuestionMark($points)) {
            return 'question';
        }
        
        // 화살표 (→) 인식
        if ($this->isArrow($points)) {
            return 'arrow';
        }
        
        return null;
    }
    
    /**
     * 체크 마크 인식
     */
    private function isCheckMark($points) {
        // 간단한 체크 마크 패턴 (V자 형태)
        if (count($points) < 3) {
            return false;
        }
        
        // 구현 생략 (실제로는 더 정교한 패턴 매칭 필요)
        return false;
    }
    
    /**
     * 엑스 인식
     */
    private function isCross($points) {
        // 간단한 엑스 패턴 (X자 형태)
        if (count($points) < 4) {
            return false;
        }
        
        // 구현 생략
        return false;
    }
    
    /**
     * 물음표 인식
     */
    private function isQuestionMark($points) {
        // 물음표 패턴
        // 구현 생략
        return false;
    }
    
    /**
     * 화살표 인식
     */
    private function isArrow($points) {
        // 화살표 패턴
        // 구현 생략
        return false;
    }
    
    /**
     * 점진적 강화
     */
    public function escalateQuestion($question, $noResponseTime) {
        $currentMethod = $question['type'];
        
        // 시간에 따른 강화 단계
        if ($noResponseTime >= 10 && $currentMethod === 'margin_whisper') {
            // 여백 속삭임 → 하단 호흡 바로 강화
            return $this->generateQuestion($question['inference'] ?? [], 'breathing_bar');
        } elseif ($noResponseTime >= 15 && $currentMethod === 'breathing_bar') {
            // 하단 호흡 바 → 인라인 질문으로 강화
            return $this->generateQuestion($question['inference'] ?? [], 'inline_question');
        }
        
        return null; // 더 이상 강화 불가
    }
}

