<?php
/**
 * 42가지 개입 활동 → 룰 액션 매핑
 * 
 * Phase 1: 개입 활동과 룰 시스템의 연결
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 * @version    1.0
 * @see        RULE_ONTOLOGY_BALANCE_DESIGN.md
 * @see        db/schema.sql - mdl_alt42_intervention_activities
 */

/**
 * 개입 활동 정의
 * 
 * 카테고리 (7개):
 * 1. pause_wait (멈춤/대기) - 5개
 * 2. repeat_rephrase (재설명) - 6개
 * 3. alternative_explanation (전환 설명) - 7개
 * 4. emphasis_alerting (강조/주의환기) - 5개
 * 5. questioning_probing (질문/탐색) - 7개
 * 6. immediate_intervention (즉시 개입) - 6개
 * 7. emotional_regulation (정서 조절) - 6개
 */

return [
    // ========================================
    // 1. 멈춤/대기 (Pause & Wait) — 5개
    // ========================================
    'INT_1_1' => [
        'activity_id' => 'INT_1_1',
        'category' => 'pause_wait',
        'name' => '인지 부하 대기',
        'description' => '설명을 멈추고 3~5초 침묵, 처리 시간 확보',
        'trigger_signals' => ['눈 깜빡임 증가', '시선 고정', '멍한 표정', 'pause_duration >= 3'],
        'persona_mapping' => ['P001', 'P005', 'P009'],
        'priority' => 1,
        'duration' => '3-5초',
        'action_type' => 'pause',
        'ui_action' => [
            'type' => 'feedback',
            'style' => 'gentle',
            'show_breathing_bar' => true,
            'message' => null, // 침묵
            'duration_ms' => 5000
        ],
        'follow_up' => 'INT_5_7' // 메타인지 질문
    ],
    
    'INT_1_2' => [
        'activity_id' => 'INT_1_2',
        'category' => 'pause_wait',
        'name' => '필기 동기화 대기',
        'description' => '학생이 적을 때까지 말을 멈추고 기다림',
        'trigger_signals' => ['고개 숙임', '펜 움직임', '화면/종이 응시'],
        'persona_mapping' => ['P002', 'P008'],
        'priority' => 2,
        'duration' => '필기 완료까지',
        'action_type' => 'wait_for_writing',
        'ui_action' => [
            'type' => 'observe',
            'monitor' => 'writing_activity',
            'resume_on' => 'writing_pause >= 2'
        ]
    ],
    
    'INT_1_3' => [
        'activity_id' => 'INT_1_3',
        'category' => 'pause_wait',
        'name' => '사고 여백 제공',
        'description' => '"한번 생각해봐" 후 10초 이상 기다림',
        'trigger_signals' => ['질문 직후', '어려운 개념 제시 직후'],
        'persona_mapping' => ['P001', 'P006', 'P012'],
        'priority' => 1,
        'duration' => '10초 이상',
        'action_type' => 'thinking_space',
        'ui_action' => [
            'type' => 'feedback',
            'message' => '한번 생각해봐 💭',
            'style' => 'whisper',
            'duration_ms' => 10000,
            'show_timer' => false
        ]
    ],
    
    'INT_1_4' => [
        'activity_id' => 'INT_1_4',
        'category' => 'pause_wait',
        'name' => '감정 진정 대기',
        'description' => '좌절/혼란 시 다그치지 않고 잠시 쉼',
        'trigger_signals' => ['한숨', '펜 내려놓음', '고개 떨굼', 'emotion_type = frustrated'],
        'persona_mapping' => ['P003', 'P011'],
        'priority' => 1,
        'duration' => '5-10초',
        'action_type' => 'emotional_pause',
        'ui_action' => [
            'type' => 'feedback',
            'message' => null,
            'style' => 'calm',
            'show_breathing_bar' => true,
            'duration_ms' => 8000
        ]
    ],
    
    'INT_1_5' => [
        'activity_id' => 'INT_1_5',
        'category' => 'pause_wait',
        'name' => '자기 수정 대기',
        'description' => '학생이 스스로 오류 인식할 시간 제공',
        'trigger_signals' => ['말하다 멈춤', '아 잠깐...', '표정 변화'],
        'persona_mapping' => ['P004', 'P012'],
        'priority' => 2,
        'duration' => '5-10초',
        'action_type' => 'self_correction_wait',
        'ui_action' => [
            'type' => 'observe',
            'duration_ms' => 8000,
            'on_no_correction' => 'INT_6_1' // 즉시 교정
        ]
    ],
    
    // ========================================
    // 2. 재설명 (Repeat & Rephrase) — 6개
    // ========================================
    'INT_2_1' => [
        'activity_id' => 'INT_2_1',
        'category' => 'repeat_rephrase',
        'name' => '동일 반복',
        'description' => '같은 내용을 천천히, 또박또박 다시',
        'trigger_signals' => ['네?', '다시요?', '되묻기'],
        'persona_mapping' => ['P002', 'P010'],
        'priority' => 2,
        'action_type' => 'repeat_slow',
        'ui_action' => [
            'type' => 'feedback',
            'message_template' => '다시 말해줄게. {previous_explanation}',
            'style' => 'slow',
            'highlight_key_points' => true
        ]
    ],
    
    'INT_2_2' => [
        'activity_id' => 'INT_2_2',
        'category' => 'repeat_rephrase',
        'name' => '강조점 이동 반복',
        'description' => '같은 문장에서 강조 위치를 바꿔 반복',
        'trigger_signals' => ['부분적 이해 표현', '앞부분은 알겠는데...'],
        'persona_mapping' => ['P005', 'P009'],
        'priority' => 2,
        'action_type' => 'emphasis_shift',
        'ui_action' => [
            'type' => 'feedback',
            'highlight_position' => 'shift',
            'animation' => 'pulse'
        ]
    ],
    
    'INT_2_3' => [
        'activity_id' => 'INT_2_3',
        'category' => 'repeat_rephrase',
        'name' => '단계 분해',
        'description' => '한 덩어리를 2~3개 미니 스텝으로 쪼갬',
        'trigger_signals' => ['복합 과정에서 중간에 막힘'],
        'persona_mapping' => ['P001', 'P005', 'P009'],
        'priority' => 1,
        'action_type' => 'step_decompose',
        'ui_action' => [
            'type' => 'show_steps',
            'decompose' => true,
            'step_count' => 3,
            'animate_sequence' => true
        ]
    ],
    
    'INT_2_4' => [
        'activity_id' => 'INT_2_4',
        'category' => 'repeat_rephrase',
        'name' => '역순 재구성',
        'description' => '결론 → 중간 → 시작 순으로 거꾸로 설명',
        'trigger_signals' => ['왜 이렇게 되는지 모르겠어요'],
        'persona_mapping' => ['P006', 'P012'],
        'priority' => 3,
        'action_type' => 'reverse_explain',
        'ui_action' => [
            'type' => 'feedback',
            'order' => 'reverse',
            'message_template' => '거꾸로 보자. 결론은 {conclusion}이야. 왜냐하면...'
        ]
    ],
    
    'INT_2_5' => [
        'activity_id' => 'INT_2_5',
        'category' => 'repeat_rephrase',
        'name' => '연결고리 명시',
        'description' => '"A이기 때문에 B, B이기 때문에 C" 인과 강조',
        'trigger_signals' => ['단계는 따라오나 연결을 못 느낌'],
        'persona_mapping' => ['P006', 'P007'],
        'priority' => 2,
        'action_type' => 'show_connection',
        'ui_action' => [
            'type' => 'feedback',
            'show_arrows' => true,
            'message_template' => '{A}이기 때문에 {B}, {B}이기 때문에 {C}'
        ]
    ],
    
    'INT_2_6' => [
        'activity_id' => 'INT_2_6',
        'category' => 'repeat_rephrase',
        'name' => '요약 압축',
        'description' => '긴 설명을 한 문장으로 핵심만 재진술',
        'trigger_signals' => ['정보 과다로 혼란', '그래서 뭐가 중요한 거예요?'],
        'persona_mapping' => ['P004', 'P007'],
        'priority' => 2,
        'action_type' => 'summarize',
        'ui_action' => [
            'type' => 'feedback',
            'style' => 'highlight',
            'message_template' => '핵심은 이거야: {summary}'
        ]
    ],
    
    // ========================================
    // 3. 전환 설명 (Alternative Explanation) — 7개
    // ========================================
    'INT_3_1' => [
        'activity_id' => 'INT_3_1',
        'category' => 'alternative_explanation',
        'name' => '일상 비유',
        'description' => '추상 개념을 일상 경험에 빗대어 설명',
        'trigger_signals' => ['수학 용어에서 막힘', '개념 자체 이해 불가'],
        'persona_mapping' => ['P009', 'P011'],
        'priority' => 1,
        'action_type' => 'daily_analogy',
        'ui_action' => [
            'type' => 'feedback',
            'use_emoji' => true,
            'message_template' => '이건 마치 {analogy}와 비슷해!'
        ]
    ],
    
    'INT_3_2' => [
        'activity_id' => 'INT_3_2',
        'category' => 'alternative_explanation',
        'name' => '시각화 전환',
        'description' => '말 → 그림/도표/그래프로 표현 방식 변경',
        'trigger_signals' => ['언어적 설명에 반응 없음', '청각 처리 한계'],
        'persona_mapping' => ['P005', 'P009'],
        'priority' => 1,
        'action_type' => 'visualize',
        'ui_action' => [
            'type' => 'show_visual',
            'visual_type' => 'diagram',
            'animate' => true
        ]
    ],
    
    'INT_3_3' => [
        'activity_id' => 'INT_3_3',
        'category' => 'alternative_explanation',
        'name' => '구체적 수 대입',
        'description' => '문자식을 특정 숫자로 바꿔 계산 흐름 시연',
        'trigger_signals' => ['변수/문자에 대한 두려움', 'x가 뭔데요'],
        'persona_mapping' => ['P001', 'P009'],
        'priority' => 1,
        'action_type' => 'substitute_numbers',
        'ui_action' => [
            'type' => 'feedback',
            'show_substitution' => true,
            'message_template' => 'x 대신 {number}를 넣어보면...',
            'animate_calculation' => true
        ]
    ],
    
    'INT_3_4' => [
        'activity_id' => 'INT_3_4',
        'category' => 'alternative_explanation',
        'name' => '극단적 예시',
        'description' => '0, 1, 무한대 등 극단값으로 직관 형성',
        'trigger_signals' => ['일반적 설명으로 감 못 잡음'],
        'persona_mapping' => ['P006', 'P012'],
        'priority' => 3,
        'action_type' => 'extreme_example',
        'ui_action' => [
            'type' => 'feedback',
            'message_template' => '만약 이게 0이라면? 무한대라면?'
        ]
    ],
    
    'INT_3_5' => [
        'activity_id' => 'INT_3_5',
        'category' => 'alternative_explanation',
        'name' => '반례 제시',
        'description' => '"만약 이렇게 하면 왜 안 되는지 볼까?"',
        'trigger_signals' => ['잘못된 방법을 확신함', '오개념 고착'],
        'persona_mapping' => ['P004', 'P008'],
        'priority' => 2,
        'action_type' => 'counter_example',
        'ui_action' => [
            'type' => 'feedback',
            'message_template' => '만약 이렇게 하면? {wrong_way} → 결과가 {wrong_result}! 그래서 안 돼.'
        ]
    ],
    
    'INT_3_6' => [
        'activity_id' => 'INT_3_6',
        'category' => 'alternative_explanation',
        'name' => '학생 언어 번역',
        'description' => '학생이 쓰는 표현/용어로 재설명',
        'trigger_signals' => ['교과서 용어에 거부감', '자기 말로 표현 시도'],
        'persona_mapping' => ['P009', 'P011'],
        'priority' => 1,
        'action_type' => 'student_language',
        'ui_action' => [
            'type' => 'feedback',
            'adapt_vocabulary' => true
        ]
    ],
    
    'INT_3_7' => [
        'activity_id' => 'INT_3_7',
        'category' => 'alternative_explanation',
        'name' => '신체/동작 비유',
        'description' => '손동작, 움직임으로 개념 체화',
        'trigger_signals' => ['정적 설명에 집중 못함', '운동감각형 학습자'],
        'persona_mapping' => ['P005', 'P010'],
        'priority' => 2,
        'action_type' => 'kinesthetic',
        'ui_action' => [
            'type' => 'feedback',
            'show_animation' => true,
            'message_template' => '손으로 따라해봐. {gesture_description}'
        ]
    ],
    
    // ========================================
    // 4. 강조/주의환기 (Emphasis & Alerting) — 5개
    // ========================================
    'INT_4_1' => [
        'activity_id' => 'INT_4_1',
        'category' => 'emphasis_alerting',
        'name' => '핵심 반복 강조',
        'description' => '"이게 제일 중요해" 동일 포인트 2~3회',
        'trigger_signals' => ['핵심을 지나치고 지엽적인 것에 집중'],
        'persona_mapping' => ['P004', 'P005'],
        'priority' => 2,
        'action_type' => 'repeat_emphasis',
        'ui_action' => [
            'type' => 'feedback',
            'style' => 'important',
            'repeat_count' => 2,
            'message_template' => '⭐ 이게 제일 중요해: {key_point}'
        ]
    ],
    
    'INT_4_2' => [
        'activity_id' => 'INT_4_2',
        'category' => 'emphasis_alerting',
        'name' => '대비 강조',
        'description' => '"A가 아니라 B야" 오개념과 정개념 병렬',
        'trigger_signals' => ['흔한 오류 패턴 감지', '헷갈리는 개념'],
        'persona_mapping' => ['P004', 'P008'],
        'priority' => 2,
        'action_type' => 'contrast',
        'ui_action' => [
            'type' => 'feedback',
            'show_comparison' => true,
            'message_template' => '❌ {wrong} 가 아니라 ✅ {correct} 야!'
        ]
    ],
    
    'INT_4_3' => [
        'activity_id' => 'INT_4_3',
        'category' => 'emphasis_alerting',
        'name' => '톤/속도 변화',
        'description' => '갑자기 천천히, 또는 높은 톤으로 전환',
        'trigger_signals' => ['주의력 저하', '멍한 상태', '습관적 고개 끄덕임'],
        'persona_mapping' => ['P005', 'P011'],
        'priority' => 1,
        'action_type' => 'tone_change',
        'ui_action' => [
            'type' => 'feedback',
            'animation' => 'attention_grab',
            'style' => 'alert'
        ]
    ],
    
    'INT_4_4' => [
        'activity_id' => 'INT_4_4',
        'category' => 'emphasis_alerting',
        'name' => '시각적 마킹',
        'description' => '밑줄, 동그라미, 색깔로 주의 집중 유도',
        'trigger_signals' => ['시각 자료에서 핵심 못 찾음'],
        'persona_mapping' => ['P005', 'P009'],
        'priority' => 2,
        'action_type' => 'visual_mark',
        'ui_action' => [
            'type' => 'highlight',
            'highlight_style' => 'circle',
            'color' => 'accent'
        ]
    ],
    
    'INT_4_5' => [
        'activity_id' => 'INT_4_5',
        'category' => 'emphasis_alerting',
        'name' => '예고 신호',
        'description' => '"지금부터 말하는 거 시험에 나와" 경고',
        'trigger_signals' => ['전반적 이완 상태', '중요도 인식 부족'],
        'persona_mapping' => ['P007', 'P011'],
        'priority' => 3,
        'action_type' => 'warning',
        'ui_action' => [
            'type' => 'feedback',
            'style' => 'alert',
            'message_template' => '🎯 이거 시험에 나와! {content}'
        ]
    ],
    
    // ========================================
    // 5. 질문/탐색 (Questioning & Probing) — 7개
    // ========================================
    'INT_5_1' => [
        'activity_id' => 'INT_5_1',
        'category' => 'questioning_probing',
        'name' => '확인 질문',
        'description' => '"여기까지 이해됐어?" 단순 예/아니오',
        'trigger_signals' => ['설명 구간 완료 시점', '표정 불확실'],
        'persona_mapping' => ['P002', 'P010'],
        'priority' => 2,
        'action_type' => 'yes_no_question',
        'ui_action' => [
            'type' => 'question',
            'question_type' => 'binary',
            'message' => '여기까지 이해됐어?',
            'options' => ['✓ 네', '✗ 아니요']
        ]
    ],
    
    'INT_5_2' => [
        'activity_id' => 'INT_5_2',
        'category' => 'questioning_probing',
        'name' => '예측 질문',
        'description' => '"다음엔 뭘 해야 할 것 같아?"',
        'trigger_signals' => ['수동적 청취 지속', '능동 사고 유도 필요'],
        'persona_mapping' => ['P010', 'P011'],
        'priority' => 2,
        'action_type' => 'prediction_question',
        'ui_action' => [
            'type' => 'question',
            'question_type' => 'open',
            'message' => '다음엔 뭘 해야 할 것 같아?'
        ]
    ],
    
    'INT_5_3' => [
        'activity_id' => 'INT_5_3',
        'category' => 'questioning_probing',
        'name' => '역질문',
        'description' => '"왜 그렇게 생각했어?" 사고과정 탐색',
        'trigger_signals' => ['답은 맞으나 과정 불명확', '찍기 의심'],
        'persona_mapping' => ['P004', 'P012'],
        'priority' => 2,
        'action_type' => 'reverse_question',
        'ui_action' => [
            'type' => 'question',
            'question_type' => 'open',
            'message' => '왜 그렇게 생각했어? 어떻게 풀었는지 설명해봐'
        ]
    ],
    
    'INT_5_4' => [
        'activity_id' => 'INT_5_4',
        'category' => 'questioning_probing',
        'name' => '선택지 질문',
        'description' => '"A일까 B일까?" 이지선다로 부담 경감',
        'trigger_signals' => ['열린 질문에 대답 못함', '막막해함'],
        'persona_mapping' => ['P001', 'P002', 'P011'],
        'priority' => 1,
        'action_type' => 'choice_question',
        'ui_action' => [
            'type' => 'question',
            'question_type' => 'choice',
            'options_count' => 2,
            'message_template' => '{option_a}일까, {option_b}일까?'
        ]
    ],
    
    'INT_5_5' => [
        'activity_id' => 'INT_5_5',
        'category' => 'questioning_probing',
        'name' => '힌트 질문',
        'description' => '"만약 여기가 0이면?" 방향 유도',
        'trigger_signals' => ['시작점을 못 잡음', '백지 상태'],
        'persona_mapping' => ['P001', 'P011'],
        'priority' => 1,
        'action_type' => 'hint_question',
        'ui_action' => [
            'type' => 'question',
            'question_type' => 'hint',
            'message_template' => '만약 {hint_condition}이라면?'
        ]
    ],
    
    'INT_5_6' => [
        'activity_id' => 'INT_5_6',
        'category' => 'questioning_probing',
        'name' => '연결 질문',
        'description' => '"이거 저번에 한 거랑 뭐가 비슷해?"',
        'trigger_signals' => ['새 개념에 고립감', '기존 지식 활성화 필요'],
        'persona_mapping' => ['P006', 'P009'],
        'priority' => 2,
        'action_type' => 'connection_question',
        'ui_action' => [
            'type' => 'question',
            'question_type' => 'open',
            'message' => '이거 저번에 한 거랑 뭐가 비슷해?'
        ]
    ],
    
    'INT_5_7' => [
        'activity_id' => 'INT_5_7',
        'category' => 'questioning_probing',
        'name' => '메타인지 질문',
        'description' => '"지금 어디가 헷갈려?" 자기 상태 인식 유도',
        'trigger_signals' => ['막연한 모르겠어요', '구체화 필요'],
        'persona_mapping' => ['P001', 'P011', 'P012'],
        'priority' => 1,
        'action_type' => 'metacognition_question',
        'ui_action' => [
            'type' => 'question',
            'question_type' => 'open',
            'message' => '지금 어디가 헷갈려? 어느 부분에서 막혔어?'
        ]
    ],
    
    // ========================================
    // 6. 즉시 개입 (Immediate Intervention) — 6개
    // ========================================
    'INT_6_1' => [
        'activity_id' => 'INT_6_1',
        'category' => 'immediate_intervention',
        'name' => '즉시 교정',
        'description' => '오류 순간 "잠깐!" 바로 멈추고 수정',
        'trigger_signals' => ['계산 실수', '부호 오류', '공식 오적용'],
        'persona_mapping' => ['P004', 'P008'],
        'priority' => 1,
        'action_type' => 'immediate_correct',
        'ui_action' => [
            'type' => 'feedback',
            'style' => 'alert',
            'interrupt' => true,
            'message_template' => '잠깐! 여기 {error_point} 다시 확인해봐'
        ]
    ],
    
    'INT_6_2' => [
        'activity_id' => 'INT_6_2',
        'category' => 'immediate_intervention',
        'name' => '부분 인정 확장',
        'description' => '"거기까진 맞아, 근데..." 긍정 후 보완',
        'trigger_signals' => ['방향은 맞으나 불완전한 답변'],
        'persona_mapping' => ['P002', 'P003'],
        'priority' => 2,
        'action_type' => 'partial_acknowledge',
        'ui_action' => [
            'type' => 'feedback',
            'style' => 'encouraging',
            'message_template' => '거기까진 맞아! 👍 근데 {correction}'
        ]
    ],
    
    'INT_6_3' => [
        'activity_id' => 'INT_6_3',
        'category' => 'immediate_intervention',
        'name' => '함께 완성',
        'description' => '막힌 부분부터 같이 써가며 이끌기',
        'trigger_signals' => ['말/글이 중간에 끊김', '다음 진행 불가'],
        'persona_mapping' => ['P001', 'P010'],
        'priority' => 1,
        'action_type' => 'co_complete',
        'ui_action' => [
            'type' => 'guided_practice',
            'mode' => 'collaborative',
            'message' => '같이 해보자! 여기서부터...'
        ]
    ],
    
    'INT_6_4' => [
        'activity_id' => 'INT_6_4',
        'category' => 'immediate_intervention',
        'name' => '되물어 확인',
        'description' => '"네 말은 ~라는 거지?" 재구성 확인',
        'trigger_signals' => ['답변이 모호하거나 문장이 불완전'],
        'persona_mapping' => ['P002', 'P009'],
        'priority' => 2,
        'action_type' => 'paraphrase_confirm',
        'ui_action' => [
            'type' => 'question',
            'question_type' => 'confirm',
            'message_template' => '네 말은 {paraphrased}라는 거지?'
        ]
    ],
    
    'INT_6_5' => [
        'activity_id' => 'INT_6_5',
        'category' => 'immediate_intervention',
        'name' => '오개념 즉시 분리',
        'description' => '"그건 다른 거야" 혼동 요소 명확 분리',
        'trigger_signals' => ['두 개념 혼합 사용', '용어 혼란'],
        'persona_mapping' => ['P004', 'P008'],
        'priority' => 1,
        'action_type' => 'concept_separate',
        'ui_action' => [
            'type' => 'feedback',
            'style' => 'clarify',
            'message_template' => '잠깐! {concept_a}랑 {concept_b}는 다른 거야. {explanation}'
        ]
    ],
    
    'INT_6_6' => [
        'activity_id' => 'INT_6_6',
        'category' => 'immediate_intervention',
        'name' => '실시간 시범',
        'description' => '학생 시도 옆에서 바로 올바른 과정 시연',
        'trigger_signals' => ['같은 실수 반복', '말로 교정 안 됨'],
        'persona_mapping' => ['P004', 'P010'],
        'priority' => 1,
        'action_type' => 'live_demo',
        'ui_action' => [
            'type' => 'demonstration',
            'mode' => 'step_by_step',
            'show_on_whiteboard' => true
        ]
    ],
    
    // ========================================
    // 7. 정서 조절 (Emotional Regulation) — 6개
    // ========================================
    'INT_7_1' => [
        'activity_id' => 'INT_7_1',
        'category' => 'emotional_regulation',
        'name' => '노력 인정',
        'description' => '"열심히 생각했네" 과정 자체 칭찬',
        'trigger_signals' => ['오답이지만 시도함', '좌절 직전'],
        'persona_mapping' => ['P003', 'P011'],
        'priority' => 1,
        'action_type' => 'effort_acknowledge',
        'ui_action' => [
            'type' => 'feedback',
            'style' => 'encouraging',
            'use_emoji' => true,
            'message_template' => '열심히 생각했네! 👏 {encouragement}'
        ]
    ],
    
    'INT_7_2' => [
        'activity_id' => 'INT_7_2',
        'category' => 'emotional_regulation',
        'name' => '정상화',
        'description' => '"이거 다 어려워해" 혼자가 아님 전달',
        'trigger_signals' => ['자책', '나만 못해요 표현'],
        'persona_mapping' => ['P003', 'P011'],
        'priority' => 1,
        'action_type' => 'normalize',
        'ui_action' => [
            'type' => 'feedback',
            'style' => 'comforting',
            'message_template' => '이거 다 어려워해. 너만 그런 게 아니야 🤗'
        ]
    ],
    
    'INT_7_3' => [
        'activity_id' => 'INT_7_3',
        'category' => 'emotional_regulation',
        'name' => '난이도 조정 예고',
        'description' => '"이건 어려운 거야, 천천히 가자"',
        'trigger_signals' => ['불안 상승', '조급함', '빨리 끝내려 함'],
        'persona_mapping' => ['P003', 'P008'],
        'priority' => 1,
        'action_type' => 'difficulty_acknowledge',
        'ui_action' => [
            'type' => 'feedback',
            'style' => 'calming',
            'message' => '이건 어려운 거야. 천천히 가도 괜찮아 🌱'
        ]
    ],
    
    'INT_7_4' => [
        'activity_id' => 'INT_7_4',
        'category' => 'emotional_regulation',
        'name' => '작은 성공 만들기',
        'description' => '일부러 쉬운 질문으로 성취감 제공',
        'trigger_signals' => ['연속 오답', '자신감 저하'],
        'persona_mapping' => ['P003', 'P011'],
        'priority' => 1,
        'action_type' => 'small_win',
        'ui_action' => [
            'type' => 'question',
            'difficulty' => 'easy',
            'guaranteed_success' => true,
            'message' => '이건 할 수 있을 거야! 해보자 💪'
        ]
    ],
    
    'INT_7_5' => [
        'activity_id' => 'INT_7_5',
        'category' => 'emotional_regulation',
        'name' => '유머/가벼운 전환',
        'description' => '잠깐 긴장 풀어주는 가벼운 말',
        'trigger_signals' => ['과도한 긴장', '어깨 경직', '호흡 얕음'],
        'persona_mapping' => ['P003', 'P008'],
        'priority' => 2,
        'action_type' => 'humor_break',
        'ui_action' => [
            'type' => 'feedback',
            'style' => 'light',
            'use_emoji' => true,
            'message' => '심호흡 한번! 😊 잠깐 쉬어가자'
        ]
    ],
    
    'INT_7_6' => [
        'activity_id' => 'INT_7_6',
        'category' => 'emotional_regulation',
        'name' => '선택권 부여',
        'description' => '"이거 먼저 할까, 저거 먼저 할까?"',
        'trigger_signals' => ['통제감 상실', '무기력 신호'],
        'persona_mapping' => ['P010', 'P011'],
        'priority' => 1,
        'action_type' => 'give_choice',
        'ui_action' => [
            'type' => 'question',
            'question_type' => 'choice',
            'message' => '이거 먼저 할까, 저거 먼저 할까? 네가 골라봐',
            'empowerment' => true
        ]
    ],
    
    // ========================================
    // 시스템 액션 (Non-Intervention)
    // ========================================
    'STEP_ADVANCE' => [
        'activity_id' => 'STEP_ADVANCE',
        'category' => 'system',
        'name' => '단계 진행',
        'description' => '다음 풀이 단계로 이동',
        'action_type' => 'system_action',
        'ui_action' => [
            'type' => 'step_change',
            'direction' => 'next'
        ]
    ],
    
    'ITEM_ADVANCE' => [
        'activity_id' => 'ITEM_ADVANCE',
        'category' => 'system',
        'name' => '문항 이동',
        'description' => '다음 문항으로 이동',
        'action_type' => 'system_action',
        'ui_action' => [
            'type' => 'item_change',
            'direction' => 'next'
        ]
    ],
    
    'SESSION_INIT' => [
        'activity_id' => 'SESSION_INIT',
        'category' => 'system',
        'name' => '세션 초기화',
        'description' => '학습 세션 시작',
        'action_type' => 'system_action',
        'ui_action' => [
            'type' => 'session_start'
        ]
    ],
    
    'UPDATE_PROGRESS' => [
        'activity_id' => 'UPDATE_PROGRESS',
        'category' => 'system',
        'name' => '진행률 업데이트',
        'description' => '진행률 갱신',
        'action_type' => 'system_action',
        'ui_action' => [
            'type' => 'progress_update'
        ]
    ],
    
    'NON_INTRUSIVE_QUESTION' => [
        'activity_id' => 'NON_INTRUSIVE_QUESTION',
        'category' => 'system',
        'name' => '비침습적 질문',
        'description' => '여백에 조용히 질문 표시',
        'action_type' => 'non_intrusive',
        'ui_action' => [
            'type' => 'margin_whisper',
            'position' => 'corner'
        ]
    ],
    
    'SUGGEST_CHALLENGE' => [
        'activity_id' => 'SUGGEST_CHALLENGE',
        'category' => 'system',
        'name' => '도전 제안',
        'description' => '고난도 문제 도전 제안',
        'action_type' => 'challenge',
        'ui_action' => [
            'type' => 'feedback',
            'style' => 'challenge',
            'message' => '좀 더 어려운 문제 도전해볼까? 🚀'
        ]
    ],
    
    'LOG_EFFECTIVENESS' => [
        'activity_id' => 'LOG_EFFECTIVENESS',
        'category' => 'system',
        'name' => '효과 로깅',
        'description' => '개입 효과성 기록',
        'action_type' => 'logging',
        'ui_action' => [
            'type' => 'background_log'
        ]
    ]
];

