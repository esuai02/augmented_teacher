<?php
/**
 * Default Response Template
 *
 * 기본 응답 템플릿 - 특정 페르소나/상황 템플릿이 없을 때 사용
 *
 * Available Variables:
 * - $user_id: 사용자 ID
 * - $persona_id: 페르소나 ID (예: S1_P1)
 * - $persona_name: 페르소나 이름
 * - $situation_id: 상황 ID (예: S1)
 * - $situation_name: 상황 이름
 * - $confidence: 식별 신뢰도
 * - $context: 컨텍스트 스냅샷
 * - $message: 사용자 메시지
 * - $timestamp: 타임스탬프
 */

// 기본 응답 구성
$responses = array(
    'greeting' => '안녕하세요!',
    'acknowledgment' => '네, 말씀하신 내용을 이해했어요.',
    'offer_help' => '무엇을 도와드릴까요?',
    'closing' => '언제든 질문해 주세요.'
);

// 메시지가 있으면 응답 형태 변경
if (!empty($message)) {
    echo $responses['acknowledgment'] . ' ' . $responses['offer_help'];
} else {
    echo $responses['greeting'] . ' ' . $responses['offer_help'];
}
