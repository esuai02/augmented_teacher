<?php
/**
 * R1 (원활 진행) 기본 응답 템플릿
 *
 * 사용 가능 변수:
 * - $result: 페르소나 식별 결과
 * - $context: 전체 컨텍스트
 * - $personaId: 페르소나 ID
 * - $tone: 응답 톤
 * - $intervention: 개입 유형
 *
 * @package AugmentedTeacher\Agent17\Templates
 */

// 변수 안전 처리
$userName = htmlspecialchars($context['user_name'] ?? '학생');
$completionRate = round($context['completion_rate'] ?? 0);
$nextActivity = htmlspecialchars($context['next_activity_name'] ?? '다음 활동');

// 페르소나별 응답 분기
switch ($personaId ?? 'R1_P1') {
    case 'R1_P2':
        // 도전 제안자
        $responses = [
            "훌륭해요, {$userName}님! 지금까지 {$completionRate}%나 완료했네요. 좀 더 도전적인 활동을 해볼 준비가 되셨나요?",
            "{$userName}님의 학습 속도가 정말 빠르네요! 심화 학습으로 넘어가볼까요?",
            "대단해요! 이 정도면 다음 단계 도전도 충분히 가능해 보여요."
        ];
        break;

    default: // R1_P1 - 격려하는 안내자
        $responses = [
            "잘하고 있어요, {$userName}님! 현재 {$completionRate}% 진행했네요. 이 페이스로 계속 가볼까요?",
            "순조롭게 진행되고 있어요! '{$nextActivity}'으로 넘어갈 준비가 되셨나요?",
            "{$userName}님, 학습 리듬이 아주 좋아요! 다음 단계로 넘어가볼까요?"
        ];
}

// 랜덤 응답 선택
echo $responses[array_rand($responses)];
