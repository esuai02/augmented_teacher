<?php
/**
 * Agent04 개념이해 페르소나 시스템 설정
 *
 * 개념 학습 시 발생하는 인지관성(Cognitive Inertia) 패턴 정의
 * 각 패턴에 대한 해결 전략, 확인 포인트, 선생님 대화 템플릿 포함
 *
 * @package AugmentedTeacher\Agent04\PersonaSystem
 * @version 2.0
 * @since 2025-12-03
 */

return [
    // ==========================================
    // 상황 기본 정보
    // ==========================================
    'situation' => [
        'id' => 1,
        'code' => 'concept_understanding',
        'name' => '개념이해',
        'description' => '개념 학습 시 발생하는 인지관성 패턴을 탐지하고 개선 전략을 제시',
        'icon' => '📖',
        'color' => '#3b82f6',
        'version' => '2.0.0',
    ],

    // ==========================================
    // 시스템 설정
    // ==========================================
    'system' => [
        'debug_mode' => false,
        'log_enabled' => true,
        'log_level' => 'info',
    ],

    // ==========================================
    // 엔진 설정
    // ==========================================
    'engine' => [
        'default_persona' => 'concept_analyzer',
        'default_tone' => 'Supportive',
        'confidence_threshold' => 0.6,
        'enable_audio_feedback' => true,
        'audio_base_url' => 'https://mathking.kr/Contents/personas/concept_understanding/',
        'audio_format' => 'wav',
    ],

    // ==========================================
    // 세부 항목 정의
    // ==========================================
    'sub_items' => [
        'concept_study_method_efficacy' => [
            'name' => '개념공부법 효능감 인식',
            'description' => '개념 학습 방법에 대한 자기효능감 인식',
            'icon' => '💡',
        ],
        'concept_reading' => [
            'name' => '개념정독',
            'description' => '개념을 꼼꼼히 읽고 이해하는 과정',
            'icon' => '📚',
        ],
        'concept_understanding' => [
            'name' => '개념이해',
            'description' => '개념의 의미와 원리를 파악하는 과정',
            'icon' => '🧠',
        ],
        'concept_check' => [
            'name' => '개념체크',
            'description' => '이해한 개념을 확인하는 과정',
            'icon' => '✅',
        ],
        'example_quiz' => [
            'name' => '예제퀴즈',
            'description' => '예제를 통해 개념을 확인하는 과정',
            'icon' => '❓',
        ],
        'representative_type' => [
            'name' => '대표유형',
            'description' => '대표 문제 유형을 학습하는 과정',
            'icon' => '⭐',
        ],
        'topic_test' => [
            'name' => '주제별테스트',
            'description' => '주제별로 이해도를 확인하는 과정',
            'icon' => '📝',
        ],
        'unit_test' => [
            'name' => '단원별테스트',
            'description' => '단원 전체 이해도를 확인하는 과정',
            'icon' => '📋',
        ],
        'listen_explanation' => [
            'name' => '설명듣기',
            'description' => '선생님의 설명을 듣고 이해하는 과정',
            'icon' => '👂',
        ],
    ],

    // ==========================================
    // 카테고리 정의 (개념이해 상황 특화)
    // ==========================================
    'categories' => [
        'cognitive_overload' => [
            'name' => '인지 과부하',
            'description' => '너무 많은 개념을 한꺼번에 처리하려는 패턴',
            'icon' => '🧠',
            'color' => '#ef4444',
            'priority_weight' => 1.0,
        ],
        'surface_learning' => [
            'name' => '표면적 학습',
            'description' => '깊은 이해 없이 겉핥기로 넘어가는 패턴',
            'icon' => '🏃',
            'color' => '#f59e0b',
            'priority_weight' => 0.95,
        ],
        'misconception' => [
            'name' => '개념 오해',
            'description' => '개념을 잘못 이해하거나 왜곡하는 패턴',
            'icon' => '❌',
            'color' => '#8b5cf6',
            'priority_weight' => 0.9,
        ],
        'passive_learning' => [
            'name' => '수동적 학습',
            'description' => '능동적 참여 없이 수동적으로 받아들이는 패턴',
            'icon' => '😴',
            'color' => '#06b6d4',
            'priority_weight' => 0.85,
        ],
        'attention_deficit' => [
            'name' => '주의력 결핍',
            'description' => '집중하지 못하고 산만해지는 패턴',
            'icon' => '👁️',
            'color' => '#10b981',
            'priority_weight' => 0.85,
        ],
        'avoidance' => [
            'name' => '회피 성향',
            'description' => '어려운 개념을 회피하려는 패턴',
            'icon' => '🏃‍♂️',
            'color' => '#ec4899',
            'priority_weight' => 0.8,
        ],
        'overconfidence' => [
            'name' => '과신',
            'description' => '충분히 이해하지 못했는데 알았다고 착각하는 패턴',
            'icon' => '😤',
            'color' => '#6366f1',
            'priority_weight' => 0.9,
        ],
    ],

    // ==========================================
    // 우선순위 레벨 정의
    // ==========================================
    'priority_levels' => [
        'high' => [
            'name' => '높음',
            'color' => '#ef4444',
            'weight' => 1.0,
            'intervention_urgency' => 'immediate',
        ],
        'medium' => [
            'name' => '보통',
            'color' => '#f59e0b',
            'weight' => 0.7,
            'intervention_urgency' => 'scheduled',
        ],
        'low' => [
            'name' => '낮음',
            'color' => '#10b981',
            'weight' => 0.4,
            'intervention_urgency' => 'optional',
        ],
    ],

    // ==========================================
    // 인지관성 페르소나 정의
    // ==========================================
    'personas' => [
        // ------------------------------------------
        // 개념공부법 효능감 인식 관련 페르소나
        // ------------------------------------------
        1 => [
            'id' => 1,
            'name' => '무기력 학습자형',
            'desc' => '개념 공부를 해도 성적이 오르지 않을 것이라는 학습된 무기력을 보이는 패턴.',
            'sub_item' => 'concept_study_method_efficacy',
            'category' => 'passive_learning',
            'category_name' => '수동적 학습',
            'icon' => '😔',
            'priority' => 'high',
            'audio_time' => '2:10',
            'solution' => [
                'action' => '작은 성공 경험 만들기 → 개념 하나를 5분 학습 후 바로 관련 예제 1개 풀기 → 정답 시 \'성공 일기\'에 기록',
                'check' => '이번 주 성공 일기에 3개 이상 기록했는지 확인. 작은 성취감을 느꼈는지 피드백',
                'teacher_dialog' => '선생님, 이번 주 성공 일기에 작은 성취 3개를 기록했어요. 제가 개념 공부에 효과가 있다는 걸 느끼기 시작했는지 피드백 부탁드려요!',
            ],
        ],
        2 => [
            'id' => 2,
            'name' => '완벽주의 지연형',
            'desc' => '완벽하게 이해해야 한다는 압박으로 개념 학습 시작을 미루는 패턴.',
            'sub_item' => 'concept_study_method_efficacy',
            'category' => 'avoidance',
            'category_name' => '회피 성향',
            'icon' => '⏰',
            'priority' => 'high',
            'audio_time' => '1:55',
            'solution' => [
                'action' => '\'70% 이해 OK\' 마인드 설정 → 일단 시작하고 첫 10분만 집중 → 모르는 부분 표시 후 나중에 복습',
                'check' => '시작 지연 시간이 5분 이내로 줄었는지 확인. 표시한 부분을 복습했는지 점검',
                'teacher_dialog' => '선생님, 오늘은 \'일단 시작\' 전략을 써서 10분 안에 개념 학습을 시작했어요. 모르는 부분을 표시해뒀는데 같이 봐주실 수 있나요?',
            ],
        ],
        3 => [
            'id' => 3,
            'name' => '비교 열등감형',
            'desc' => '다른 학생들과 비교하며 자신의 개념 이해 능력을 과소평가하는 패턴.',
            'sub_item' => 'concept_study_method_efficacy',
            'category' => 'passive_learning',
            'category_name' => '수동적 학습',
            'icon' => '😢',
            'priority' => 'medium',
            'audio_time' => '2:00',
            'solution' => [
                'action' => '\'나만의 성장 그래프\' 작성 → 어제의 나와 오늘의 나 비교 → 개념 이해 개수 누적 기록',
                'check' => '성장 그래프에서 향상된 부분이 있는지 확인. 자기 비교에 집중했는지 점검',
                'teacher_dialog' => '선생님, 성장 그래프를 보니 지난주보다 이해한 개념이 3개 늘었어요. 저만의 속도로 성장하고 있는 건지 확인해주세요!',
            ],
        ],

        // ------------------------------------------
        // 개념정독 관련 페르소나
        // ------------------------------------------
        4 => [
            'id' => 4,
            'name' => '속독 건너뛰기형',
            'desc' => '개념을 빠르게 훑어보며 핵심 내용을 놓치는 패턴.',
            'sub_item' => 'concept_reading',
            'category' => 'surface_learning',
            'category_name' => '표면적 학습',
            'icon' => '🏃',
            'priority' => 'high',
            'audio_time' => '1:45',
            'solution' => [
                'action' => '\'손가락 따라가기\' 기법 → 한 문장씩 소리 내어 읽기 → 핵심 단어에 형광펜 표시',
                'check' => '형광펜 표시한 핵심 단어를 설명할 수 있는지 확인',
                'teacher_dialog' => '선생님, 오늘 개념을 손가락으로 짚어가며 읽었어요. 제가 표시한 핵심 단어를 제대로 골랐는지 확인해주세요!',
            ],
        ],
        5 => [
            'id' => 5,
            'name' => '단어 무시형',
            'desc' => '모르는 수학 용어를 그냥 넘어가며 이해의 구멍을 만드는 패턴.',
            'sub_item' => 'concept_reading',
            'category' => 'misconception',
            'category_name' => '개념 오해',
            'icon' => '❓',
            'priority' => 'high',
            'audio_time' => '2:05',
            'solution' => [
                'action' => '\'모르는 단어 노트\' 작성 → 읽다가 막히면 바로 노트에 기록 → 학습 후 용어 사전에서 찾아보기',
                'check' => '모르는 단어를 몇 개나 찾았는지, 의미를 이해했는지 확인',
                'teacher_dialog' => '선생님, 오늘 개념 읽으면서 모르는 단어 5개를 찾았어요. 제가 찾은 뜻이 맞는지 확인 부탁드려요!',
            ],
        ],
        6 => [
            'id' => 6,
            'name' => '반복 없는 일회독형',
            'desc' => '한 번 읽고 끝내며 기억 정착이 안 되는 패턴.',
            'sub_item' => 'concept_reading',
            'category' => 'surface_learning',
            'category_name' => '표면적 학습',
            'icon' => '1️⃣',
            'priority' => 'medium',
            'audio_time' => '1:50',
            'solution' => [
                'action' => '\'3회독 시스템\' → 1독: 전체 훑기, 2독: 밑줄 긋기, 3독: 요약하기',
                'check' => '3회독을 완료했는지, 요약 내용이 핵심을 담고 있는지 확인',
                'teacher_dialog' => '선생님, 오늘 개념을 3번 읽었어요. 제 요약이 핵심을 잘 담았는지 봐주세요!',
            ],
        ],

        // ------------------------------------------
        // 개념이해 관련 페르소나
        // ------------------------------------------
        7 => [
            'id' => 7,
            'name' => '암기 의존형',
            'desc' => '개념의 원리를 이해하지 않고 공식만 외우려는 패턴.',
            'sub_item' => 'concept_understanding',
            'category' => 'surface_learning',
            'category_name' => '표면적 학습',
            'icon' => '📝',
            'priority' => 'high',
            'audio_time' => '2:20',
            'solution' => [
                'action' => '\'왜?\' 질문하기 → 공식을 보면 \'왜 이렇게 되지?\' 자문 → 유도 과정 직접 써보기',
                'check' => '공식의 유도 과정을 설명할 수 있는지 확인',
                'teacher_dialog' => '선생님, 오늘 공식의 유도 과정을 직접 써봤어요. 제가 이해한 과정이 맞는지 설명해드릴게요!',
            ],
        ],
        8 => [
            'id' => 8,
            'name' => '연결 단절형',
            'desc' => '개념들 사이의 연결고리를 파악하지 못해 파편화된 지식을 가지는 패턴.',
            'sub_item' => 'concept_understanding',
            'category' => 'misconception',
            'category_name' => '개념 오해',
            'icon' => '🔗',
            'priority' => 'high',
            'audio_time' => '2:15',
            'solution' => [
                'action' => '\'개념 마인드맵\' 그리기 → 중심 개념에서 관련 개념을 화살표로 연결 → 연결 이유 한 줄 메모',
                'check' => '마인드맵에서 개념 간 연결 이유를 설명할 수 있는지 확인',
                'teacher_dialog' => '선생님, 개념 마인드맵을 그려봤어요. 제가 연결한 개념들이 논리적인지 봐주세요!',
            ],
        ],
        9 => [
            'id' => 9,
            'name' => '예시 부재형',
            'desc' => '추상적인 개념을 구체적인 예시로 변환하지 못하는 패턴.',
            'sub_item' => 'concept_understanding',
            'category' => 'passive_learning',
            'category_name' => '수동적 학습',
            'icon' => '💭',
            'priority' => 'medium',
            'audio_time' => '1:55',
            'solution' => [
                'action' => '\'내 예시 만들기\' → 개념을 읽고 실생활 예시 1개 떠올리기 → 노트에 기록',
                'check' => '만든 예시가 개념을 정확히 설명하는지 확인',
                'teacher_dialog' => '선생님, 오늘 배운 개념으로 실생활 예시를 만들어봤어요. 적절한 예시인지 확인해주세요!',
            ],
        ],
        10 => [
            'id' => 10,
            'name' => '조건 무시형',
            'desc' => '개념이 적용되는 조건과 범위를 무시하고 무분별하게 적용하는 패턴.',
            'sub_item' => 'concept_understanding',
            'category' => 'misconception',
            'category_name' => '개념 오해',
            'icon' => '⚠️',
            'priority' => 'high',
            'audio_time' => '2:00',
            'solution' => [
                'action' => '\'조건 체크리스트\' 만들기 → 개념마다 \'언제 쓸 수 있나?\' 조건 3가지 정리 → 문제 풀기 전 조건 확인',
                'check' => '조건 체크를 했는지, 조건 외 상황에서 실수가 줄었는지 확인',
                'teacher_dialog' => '선생님, 개념별 조건 체크리스트를 만들었어요. 제가 정리한 조건들이 맞는지 확인 부탁드려요!',
            ],
        ],

        // ------------------------------------------
        // 개념체크 관련 페르소나
        // ------------------------------------------
        11 => [
            'id' => 11,
            'name' => '확인 건너뛰기형',
            'desc' => '개념 학습 후 이해 확인 없이 바로 문제 풀이로 넘어가는 패턴.',
            'sub_item' => 'concept_check',
            'category' => 'surface_learning',
            'category_name' => '표면적 학습',
            'icon' => '⏭️',
            'priority' => 'high',
            'audio_time' => '1:45',
            'solution' => [
                'action' => '\'5분 자가 테스트\' → 개념 학습 직후 책 덮고 핵심 3가지 적기 → 다시 펴서 비교',
                'check' => '자가 테스트에서 핵심 3가지 중 몇 개를 맞췄는지 확인',
                'teacher_dialog' => '선생님, 5분 자가 테스트로 핵심 3가지를 적어봤어요. 제가 놓친 부분이 있는지 확인해주세요!',
            ],
        ],
        12 => [
            'id' => 12,
            'name' => '거짓 긍정형',
            'desc' => '모호하게 이해했는데 \'안다\'고 착각하는 패턴.',
            'sub_item' => 'concept_check',
            'category' => 'overconfidence',
            'category_name' => '과신',
            'icon' => '🤔',
            'priority' => 'high',
            'audio_time' => '2:10',
            'solution' => [
                'action' => '\'설명하기 테스트\' → 개념을 친구에게 설명하듯 혼자 말하기 → 막히는 부분 표시',
                'check' => '설명 중 막힌 부분이 어디인지, 그 부분을 보충 학습했는지 확인',
                'teacher_dialog' => '선생님, 혼자 설명하기 테스트를 해봤는데 중간에 막혔어요. 막힌 부분을 알려드릴게요!',
            ],
        ],
        13 => [
            'id' => 13,
            'name' => '표면적 확인형',
            'desc' => '단순 암기 확인만 하고 응용력은 확인하지 않는 패턴.',
            'sub_item' => 'concept_check',
            'category' => 'surface_learning',
            'category_name' => '표면적 학습',
            'icon' => '📋',
            'priority' => 'medium',
            'audio_time' => '1:50',
            'solution' => [
                'action' => '\'변형 질문 만들기\' → 개념을 다른 각도에서 묻는 질문 2개 작성 → 스스로 답하기',
                'check' => '변형 질문에 정확히 답했는지 확인',
                'teacher_dialog' => '선생님, 변형 질문 2개를 만들고 답해봤어요. 제 질문과 답이 적절한지 봐주세요!',
            ],
        ],

        // ------------------------------------------
        // 예제퀴즈 관련 페르소나
        // ------------------------------------------
        14 => [
            'id' => 14,
            'name' => '예제 스킵형',
            'desc' => '예제 풀이를 건너뛰고 바로 연습문제로 가는 패턴.',
            'sub_item' => 'example_quiz',
            'category' => 'surface_learning',
            'category_name' => '표면적 학습',
            'icon' => '⏩',
            'priority' => 'high',
            'audio_time' => '1:40',
            'solution' => [
                'action' => '\'예제 필수 풀기\' 규칙 → 모든 예제를 먼저 풀고 연습문제로 이동 → 예제 완료 체크',
                'check' => '예제를 전부 풀었는지, 풀이 과정이 이해됐는지 확인',
                'teacher_dialog' => '선생님, 오늘은 예제를 건너뛰지 않고 전부 풀었어요. 제 풀이가 맞는지 확인해주세요!',
            ],
        ],
        15 => [
            'id' => 15,
            'name' => '답만 보기형',
            'desc' => '예제의 답만 보고 풀이 과정을 이해하지 않는 패턴.',
            'sub_item' => 'example_quiz',
            'category' => 'passive_learning',
            'category_name' => '수동적 학습',
            'icon' => '👀',
            'priority' => 'high',
            'audio_time' => '2:00',
            'solution' => [
                'action' => '\'풀이 따라쓰기\' → 해설을 보며 한 줄씩 따라 쓰기 → 왜 이 단계가 필요한지 메모',
                'check' => '각 단계의 이유를 설명할 수 있는지 확인',
                'teacher_dialog' => '선생님, 예제 풀이를 따라 쓰면서 각 단계의 이유를 적어봤어요. 제가 이해한 게 맞는지 확인해주세요!',
            ],
        ],
        16 => [
            'id' => 16,
            'name' => '유형 고정형',
            'desc' => '한 유형의 예제만 반복해서 다른 유형에 적용을 못 하는 패턴.',
            'sub_item' => 'example_quiz',
            'category' => 'misconception',
            'category_name' => '개념 오해',
            'icon' => '🔒',
            'priority' => 'medium',
            'audio_time' => '1:55',
            'solution' => [
                'action' => '\'유형 비교표\' 만들기 → 비슷한 예제들의 공통점과 차이점 정리 → 각각 다르게 풀어보기',
                'check' => '유형 비교표를 완성했는지, 차이를 인식했는지 확인',
                'teacher_dialog' => '선생님, 유형 비교표를 만들어봤어요. 제가 찾은 공통점과 차이점이 맞는지 확인해주세요!',
            ],
        ],

        // ------------------------------------------
        // 대표유형 관련 페르소나
        // ------------------------------------------
        17 => [
            'id' => 17,
            'name' => '유형 혼동형',
            'desc' => '대표유형을 서로 혼동하여 잘못된 풀이법을 적용하는 패턴.',
            'sub_item' => 'representative_type',
            'category' => 'misconception',
            'category_name' => '개념 오해',
            'icon' => '🔀',
            'priority' => 'high',
            'audio_time' => '2:15',
            'solution' => [
                'action' => '\'유형 판별 훈련\' → 문제를 보고 어떤 유형인지 먼저 적기 → 풀이 전 유형 확인',
                'check' => '유형 판별이 정확했는지, 그에 맞는 풀이를 적용했는지 확인',
                'teacher_dialog' => '선생님, 문제를 보고 유형을 먼저 적어봤어요. 제 유형 판별이 맞는지 확인해주세요!',
            ],
        ],
        18 => [
            'id' => 18,
            'name' => '기계적 적용형',
            'desc' => '대표유형의 풀이법을 이해 없이 기계적으로 적용하는 패턴.',
            'sub_item' => 'representative_type',
            'category' => 'surface_learning',
            'category_name' => '표면적 학습',
            'icon' => '🤖',
            'priority' => 'high',
            'audio_time' => '2:05',
            'solution' => [
                'action' => '\'왜 이 방법?\' 질문 → 풀이 전 이 유형에 이 방법을 쓰는 이유 한 줄 적기',
                'check' => '적은 이유가 논리적인지 확인',
                'teacher_dialog' => '선생님, 풀이 방법을 쓰는 이유를 적어봤어요. 제가 이해한 이유가 맞는지 봐주세요!',
            ],
        ],
        19 => [
            'id' => 19,
            'name' => '변형 미인식형',
            'desc' => '대표유형의 변형 문제를 알아보지 못하는 패턴.',
            'sub_item' => 'representative_type',
            'category' => 'misconception',
            'category_name' => '개념 오해',
            'icon' => '🎭',
            'priority' => 'medium',
            'audio_time' => '1:50',
            'solution' => [
                'action' => '\'변형 찾기 훈련\' → 기본 유형과 변형 문제를 나란히 놓고 차이점 3가지 찾기',
                'check' => '차이점을 정확히 찾았는지, 변형에 대응할 수 있는지 확인',
                'teacher_dialog' => '선생님, 기본 유형과 변형 문제의 차이점 3가지를 찾아봤어요. 제가 찾은 게 맞는지 확인해주세요!',
            ],
        ],

        // ------------------------------------------
        // 주제별테스트 관련 페르소나
        // ------------------------------------------
        20 => [
            'id' => 20,
            'name' => '범위 혼란형',
            'desc' => '테스트 범위 내 개념들을 체계적으로 정리하지 못하는 패턴.',
            'sub_item' => 'topic_test',
            'category' => 'cognitive_overload',
            'category_name' => '인지 과부하',
            'icon' => '🌀',
            'priority' => 'high',
            'audio_time' => '2:10',
            'solution' => [
                'action' => '\'범위 체크리스트\' 작성 → 테스트 범위 내 개념 목록 만들기 → 이해도 ○△× 표시',
                'check' => '체크리스트 완성 여부와 △× 부분 보충 학습 여부 확인',
                'teacher_dialog' => '선생님, 범위 체크리스트를 만들었어요. △× 표시한 부분을 어떻게 보충하면 좋을지 조언 부탁드려요!',
            ],
        ],
        21 => [
            'id' => 21,
            'name' => '시험 불안형',
            'desc' => '테스트에 대한 불안으로 실력 발휘를 못 하는 패턴.',
            'sub_item' => 'topic_test',
            'category' => 'avoidance',
            'category_name' => '회피 성향',
            'icon' => '😰',
            'priority' => 'high',
            'audio_time' => '2:20',
            'solution' => [
                'action' => '\'3분 호흡\' + \'긍정 자기 대화\' → 테스트 전 심호흡 후 \'나는 준비됐다\' 3번 반복',
                'check' => '호흡법과 긍정 대화를 실천했는지, 불안이 감소했는지 확인',
                'teacher_dialog' => '선생님, 테스트 전에 호흡법과 긍정 대화를 했어요. 불안이 좀 줄었는데, 더 효과적인 방법이 있을까요?',
            ],
        ],
        22 => [
            'id' => 22,
            'name' => '시간 분배 실패형',
            'desc' => '테스트 시간 분배를 못해 뒷문제를 못 푸는 패턴.',
            'sub_item' => 'topic_test',
            'category' => 'cognitive_overload',
            'category_name' => '인지 과부하',
            'icon' => '⏱️',
            'priority' => 'medium',
            'audio_time' => '1:55',
            'solution' => [
                'action' => '\'문제당 시간 설정\' → 테스트 전 문제 수로 시간 배분 → 어려운 문제는 표시 후 나중에',
                'check' => '시간 배분을 지켰는지, 모든 문제에 답을 적었는지 확인',
                'teacher_dialog' => '선생님, 시간 배분 전략을 써봤어요. 더 효율적인 시간 관리법이 있을까요?',
            ],
        ],

        // ------------------------------------------
        // 단원별테스트 관련 페르소나
        // ------------------------------------------
        23 => [
            'id' => 23,
            'name' => '개념 통합 실패형',
            'desc' => '단원 전체 개념을 통합적으로 이해하지 못하는 패턴.',
            'sub_item' => 'unit_test',
            'category' => 'misconception',
            'category_name' => '개념 오해',
            'icon' => '🧩',
            'priority' => 'high',
            'audio_time' => '2:25',
            'solution' => [
                'action' => '\'단원 요약표\' 작성 → 단원의 핵심 개념들을 한 장에 정리 → 개념 간 관계 화살표로 표시',
                'check' => '요약표가 단원 전체를 포괄하는지, 관계가 정확한지 확인',
                'teacher_dialog' => '선생님, 단원 요약표를 만들었어요. 개념들의 관계를 제대로 이해했는지 확인해주세요!',
            ],
        ],
        24 => [
            'id' => 24,
            'name' => '복합 문제 회피형',
            'desc' => '여러 개념이 섞인 복합 문제를 회피하는 패턴.',
            'sub_item' => 'unit_test',
            'category' => 'avoidance',
            'category_name' => '회피 성향',
            'icon' => '🏃‍♀️',
            'priority' => 'high',
            'audio_time' => '2:10',
            'solution' => [
                'action' => '\'분해 후 조립\' 전략 → 복합 문제를 작은 단계로 분해 → 각 단계별로 필요한 개념 적기',
                'check' => '문제를 잘 분해했는지, 각 단계를 해결했는지 확인',
                'teacher_dialog' => '선생님, 복합 문제를 분해해서 풀어봤어요. 제 분해 방법이 맞는지 확인해주세요!',
            ],
        ],
        25 => [
            'id' => 25,
            'name' => '벼락치기 의존형',
            'desc' => '단원별테스트 직전에만 몰아서 공부하는 패턴.',
            'sub_item' => 'unit_test',
            'category' => 'passive_learning',
            'category_name' => '수동적 학습',
            'icon' => '⚡',
            'priority' => 'medium',
            'audio_time' => '1:50',
            'solution' => [
                'action' => '\'분산 학습 계획\' → 테스트 1주 전부터 매일 30분씩 복습 → 진도표에 체크',
                'check' => '분산 학습을 실천했는지, 테스트 전날 부담이 줄었는지 확인',
                'teacher_dialog' => '선생님, 이번엔 1주 전부터 매일 30분씩 공부했어요. 분산 학습이 도움이 됐는지 말씀드릴게요!',
            ],
        ],

        // ------------------------------------------
        // 설명듣기 관련 페르소나
        // ------------------------------------------
        26 => [
            'id' => 26,
            'name' => '수동 청취형',
            'desc' => '설명을 들으면서 메모나 질문 없이 수동적으로 듣기만 하는 패턴.',
            'sub_item' => 'listen_explanation',
            'category' => 'passive_learning',
            'category_name' => '수동적 학습',
            'icon' => '👂',
            'priority' => 'high',
            'audio_time' => '2:00',
            'solution' => [
                'action' => '\'능동 청취 노트\' → 설명 중 핵심 키워드 3개 적기 → 궁금한 점 1개 메모',
                'check' => '키워드와 질문을 적었는지, 질문을 해결했는지 확인',
                'teacher_dialog' => '선생님, 설명 들으면서 핵심 키워드 3개와 궁금한 점 1개를 적었어요. 확인해주세요!',
            ],
        ],
        27 => [
            'id' => 27,
            'name' => '집중력 분산형',
            'desc' => '설명을 듣다가 집중력이 흐트러져 중요한 부분을 놓치는 패턴.',
            'sub_item' => 'listen_explanation',
            'category' => 'attention_deficit',
            'category_name' => '주의력 결핍',
            'icon' => '💭',
            'priority' => 'high',
            'audio_time' => '1:55',
            'solution' => [
                'action' => '\'5분 집중 리셋\' → 5분마다 자세 바로잡기 + 방금 들은 내용 한 줄 요약',
                'check' => '5분 요약을 실천했는지, 핵심을 놓치지 않았는지 확인',
                'teacher_dialog' => '선생님, 5분마다 요약을 적어봤어요. 중요한 부분을 놓치지 않았는지 확인해주세요!',
            ],
        ],
        28 => [
            'id' => 28,
            'name' => '질문 회피형',
            'desc' => '모르는 부분이 있어도 질문하지 않고 넘어가는 패턴.',
            'sub_item' => 'listen_explanation',
            'category' => 'avoidance',
            'category_name' => '회피 성향',
            'icon' => '🙊',
            'priority' => 'high',
            'audio_time' => '2:05',
            'solution' => [
                'action' => '\'질문 필수 규칙\' → 설명 후 최소 1개 질문하기 → 질문 못 하면 메모해서 나중에 물어보기',
                'check' => '질문을 했거나 메모했는지, 의문이 해결됐는지 확인',
                'teacher_dialog' => '선생님, 오늘 설명 듣고 질문 1개를 했어요(또는 메모했어요). 더 물어볼 게 있어요!',
            ],
        ],
        29 => [
            'id' => 29,
            'name' => '속도 미스매치형',
            'desc' => '설명 속도를 따라가지 못해 중간에 포기하는 패턴.',
            'sub_item' => 'listen_explanation',
            'category' => 'cognitive_overload',
            'category_name' => '인지 과부하',
            'icon' => '🏃‍♂️',
            'priority' => 'medium',
            'audio_time' => '1:45',
            'solution' => [
                'action' => '\'멈춤 신호\' 활용 → 이해 안 되면 손을 들어 멈춤 요청 → 천천히 다시 설명 요청',
                'check' => '멈춤 신호를 활용했는지, 이해가 됐는지 확인',
                'teacher_dialog' => '선생님, 설명이 빨라서 손을 들어 멈춤 요청했어요. 다시 설명해주셔서 이해했어요!',
            ],
        ],
        30 => [
            'id' => 30,
            'name' => '복습 지연형',
            'desc' => '설명을 들은 후 복습을 미루다 내용을 잊어버리는 패턴.',
            'sub_item' => 'listen_explanation',
            'category' => 'passive_learning',
            'category_name' => '수동적 학습',
            'icon' => '📅',
            'priority' => 'medium',
            'audio_time' => '1:50',
            'solution' => [
                'action' => '\'24시간 복습 규칙\' → 설명 들은 후 24시간 내에 노트 복습 → 핵심 3줄 요약',
                'check' => '24시간 내 복습을 했는지, 요약이 정확한지 확인',
                'teacher_dialog' => '선생님, 어제 설명 듣고 오늘 복습해서 핵심 3줄을 정리했어요. 맞는지 확인해주세요!',
            ],
        ],
    ],
];
