<?php
/**
 * Agent04 Persona System - Conversational Chat Interface
 *
 * rules01.yaml 기반 인지관성 패턴 탐지를 위한 대화형 인터페이스
 *
 * 특징:
 * - 3탭 구조: 학생 대화 / 선생님 입력 / 시스템 데이터
 * - 아코디언 스타일의 순차 질문 진행
 * - 타이핑 효과 애니메이션
 * - 대화체 질문 (간접적, 친근한 톤)
 *
 * @package     AugmentedTeacher
 * @subpackage  Agent04_InspectWeakpoints
 * @version     3.0.0
 * @author      Augmented Teacher Team
 * @created     2025-12-04
 *
 * 파일 위치: /alt42/orchestration/agents/agent04_inspect_weakpoints/persona_system/chat01.php
 */

// Moodle 통합
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 사용자 역할 확인
$userrole=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22'  ");
$role=$userrole->data;

// 현재 파일 정보 (에러 추적용)
define('CURRENT_FILE', __FILE__);

/**
 * 대화형 질문 데이터 - 간접적이고 친근한 톤
 */
class ConversationalQuestions {

    /**
     * 학생 대화 질문 - 대화체로 변환
     */
    public static $studentQuestions = [
        // S1: 개념공부법 효능감 인식
        [
            'id' => 'learning_efficacy_belief',
            'category' => '학습 효능감',
            'icon' => '💭',
            'preview' => '개념 공부에 대한 생각',
            'message' => '혹시 개념 공부를 열심히 하면 정말 실력이 늘어나는 걸까... 하는 생각이 드나요? 솔직한 느낌이 궁금해요.',
            'type' => 'scale',
            'options' => [
                1 => '전혀 안 그래요',
                3 => '잘 모르겠어요',
                5 => '조금 그런 것 같아요',
                7 => '꽤 그렇다고 느껴요',
                10 => '확실히 그래요!'
            ]
        ],
        [
            'id' => 'success_diary_count',
            'category' => '학습 효능감',
            'icon' => '📝',
            'preview' => '작은 성취 기록',
            'message' => '이번 주에 "아, 이건 해냈다!" 싶은 작은 성취가 있었나요? 몇 개 정도 떠오르세요?',
            'type' => 'buttons',
            'options' => [
                0 => '없었어요 😅',
                1 => '1개 정도요',
                2 => '2개요',
                3 => '3개 이상이요! 🎉'
            ]
        ],
        [
            'id' => 'perfectionism_anxiety',
            'category' => '학습 효능감',
            'icon' => '🤔',
            'preview' => '완벽하게 이해해야 한다는 부담감',
            'message' => '공부 시작할 때 "완벽하게 이해 못 하면 어쩌지..." 하는 생각 때문에 망설여진 적 있나요?',
            'type' => 'buttons',
            'options' => [
                'often' => '자주 그래요',
                'sometimes' => '가끔 그래요',
                'rarely' => '별로 안 그래요',
                'never' => '그런 적 없어요'
            ]
        ],
        [
            'id' => 'peer_comparison',
            'category' => '학습 효능감',
            'icon' => '👥',
            'preview' => '친구들과 비교하는 마음',
            'message' => '친구들이 잘하는 걸 보면 "나는 왜..." 하고 비교하게 되나요?',
            'type' => 'buttons',
            'options' => [
                'always' => '항상 그래요 😔',
                'often' => '자주 그래요',
                'sometimes' => '가끔요',
                'never' => '거의 안 그래요 😊'
            ]
        ],

        // S2: 개념정독
        [
            'id' => 'reading_method',
            'category' => '개념정독',
            'icon' => '📖',
            'preview' => '개념 읽는 방법',
            'message' => '개념을 읽을 때 주로 어떤 방법을 쓰나요? 해당하는 거 다 골라주세요!',
            'type' => 'multiselect',
            'options' => [
                'skim' => '빠르게 훑어봐요',
                'finger' => '손가락으로 따라가며 읽어요',
                'aloud' => '소리 내서 읽어요',
                'underline' => '중요한 부분에 밑줄 그어요',
                'summarize' => '읽으면서 요약해요'
            ]
        ],
        [
            'id' => 'unknown_terms',
            'category' => '개념정독',
            'icon' => '❓',
            'preview' => '모르는 단어를 만났을 때',
            'message' => '읽다가 "이게 뭐지?" 싶은 단어가 나오면 보통 어떻게 하세요?',
            'type' => 'buttons',
            'options' => [
                'skip' => '일단 넘어가요',
                'note' => '따로 적어둬요',
                'search' => '바로 찾아봐요',
                'mark' => '나중에 물어보려고 표시해요'
            ]
        ],
        [
            'id' => 'reading_repetition',
            'category' => '개념정독',
            'icon' => '🔄',
            'preview' => '반복 읽기',
            'message' => '오늘 같은 개념을 몇 번 정도 읽어봤어요?',
            'type' => 'buttons',
            'options' => [
                1 => '한 번이요',
                2 => '두 번 정도요',
                3 => '세 번이요',
                'more' => '그 이상이요!'
            ]
        ],

        // S3: 개념이해
        [
            'id' => 'explain_derivation',
            'category' => '개념이해',
            'icon' => '🧠',
            'preview' => '공식의 이유 설명하기',
            'message' => '배운 공식이 "왜 이렇게 되는 거야?" 하고 물어보면 설명할 수 있을 것 같아요?',
            'type' => 'scale',
            'options' => [
                1 => '전혀 못 할 것 같아요',
                2 => '어려울 것 같아요',
                3 => '대충은 할 수 있을 것 같아요',
                4 => '꽤 잘 할 수 있을 것 같아요',
                5 => '자신 있어요!'
            ]
        ],
        [
            'id' => 'self_examples',
            'category' => '개념이해',
            'icon' => '💡',
            'preview' => '나만의 예시 만들기',
            'message' => '오늘 배운 내용으로 직접 예시를 만들어 본 적 있나요?',
            'type' => 'buttons',
            'options' => [
                0 => '아니요, 안 해봤어요',
                1 => '하나 정도요',
                2 => '두세 개요',
                'many' => '여러 개 만들어봤어요!'
            ]
        ],
        [
            'id' => 'concept_mindmap',
            'category' => '개념이해',
            'icon' => '🗺️',
            'preview' => '개념 연결 정리',
            'message' => '배운 개념들이 어떻게 연결되는지 그림이나 마인드맵으로 정리해 본 적 있어요?',
            'type' => 'buttons',
            'options' => [
                'yes' => '네, 해봤어요!',
                'partial' => '머릿속으론 해봤어요',
                'no' => '아니요, 안 해봤어요'
            ]
        ],

        // S4: 개념체크
        [
            'id' => 'self_test',
            'category' => '개념체크',
            'icon' => '✅',
            'preview' => '스스로 테스트하기',
            'message' => '책 덮고 "방금 뭐 배웠지?" 하면서 적어보거나 떠올려본 적 있나요?',
            'type' => 'buttons',
            'options' => [
                'yes' => '네, 해봤어요',
                'sometimes' => '가끔 해봐요',
                'no' => '아니요, 안 해봤어요'
            ]
        ],
        [
            'id' => 'explanation_stuck',
            'category' => '개념체크',
            'icon' => '🚧',
            'preview' => '설명하다 막히는 부분',
            'message' => '누군가에게 설명한다고 상상하면, 어디서 "어... 이건..." 하고 막힐 것 같아요?',
            'type' => 'text',
            'placeholder' => '막힐 것 같은 부분을 적어주세요 (없으면 "없음")'
        ],

        // S5: 예제퀴즈
        [
            'id' => 'example_completion',
            'category' => '예제퀴즈',
            'icon' => '📝',
            'preview' => '예제 풀이 상황',
            'message' => '오늘 예제는 어떻게 했어요?',
            'type' => 'buttons',
            'options' => [
                'skip' => '건너뛰었어요',
                'partial' => '일부만 풀었어요',
                'all' => '다 풀었어요',
                'understand' => '다 풀고 풀이도 이해했어요!'
            ]
        ],
        [
            'id' => 'solution_tracing',
            'category' => '예제퀴즈',
            'icon' => '✍️',
            'preview' => '풀이 따라쓰기',
            'message' => '해설 보면서 풀이 과정을 직접 따라 써본 적 있나요?',
            'type' => 'buttons',
            'options' => [
                'yes' => '네, 해봤어요',
                'read' => '읽기만 했어요',
                'no' => '해설은 안 봤어요'
            ]
        ],

        // S6: 대표유형
        [
            'id' => 'type_identification',
            'category' => '대표유형',
            'icon' => '🔍',
            'preview' => '문제 유형 파악',
            'message' => '문제를 보면 "아, 이건 이런 유형이구나!" 하고 먼저 생각해보나요?',
            'type' => 'buttons',
            'options' => [
                'always' => '항상 그래요',
                'sometimes' => '가끔 그래요',
                'rarely' => '잘 안 그래요',
                'never' => '그냥 바로 풀어요'
            ]
        ],
        [
            'id' => 'method_reason',
            'category' => '대표유형',
            'icon' => '🎯',
            'preview' => '풀이 방법의 이유',
            'message' => '"왜 이 방법을 써야 하지?" 하고 이유를 생각해본 적 있어요?',
            'type' => 'buttons',
            'options' => [
                'yes' => '네, 생각해봤어요',
                'sometimes' => '가끔요',
                'no' => '그냥 외운 대로 해요'
            ]
        ],

        // S7: 주제별테스트
        [
            'id' => 'scope_checklist',
            'category' => '테스트 준비',
            'icon' => '📋',
            'preview' => '범위 체크리스트',
            'message' => '테스트 전에 "이번 범위에서 이것저것 나올 수 있으니까..." 하면서 체크리스트 만들어 봤어요?',
            'type' => 'buttons',
            'options' => [
                'yes' => '네, 만들었어요',
                'mental' => '머릿속으론요',
                'no' => '아니요'
            ]
        ],
        [
            'id' => 'test_anxiety',
            'category' => '테스트 준비',
            'icon' => '😰',
            'preview' => '시험 전 마음 상태',
            'message' => '테스트 직전에 마음이 어때요?',
            'type' => 'scale',
            'options' => [
                1 => '편안해요 😊',
                3 => '살짝 긴장돼요',
                5 => '적당히 긴장돼요',
                7 => '꽤 불안해요',
                10 => '너무 불안해요 😰'
            ]
        ],
        [
            'id' => 'breathing_exercise',
            'category' => '테스트 준비',
            'icon' => '🧘',
            'preview' => '마음 진정시키기',
            'message' => '시험 전에 심호흡이나 "할 수 있어!" 같은 말로 마음을 진정시켜 본 적 있어요?',
            'type' => 'buttons',
            'options' => [
                'yes' => '네, 해봤어요',
                'sometimes' => '가끔요',
                'no' => '안 해봤어요'
            ]
        ],

        // S8: 단원별테스트
        [
            'id' => 'unit_summary',
            'category' => '단원 정리',
            'icon' => '📑',
            'preview' => '단원 요약 정리',
            'message' => '단원 끝날 때 전체 내용을 한 장으로 정리해 본 적 있어요?',
            'type' => 'buttons',
            'options' => [
                'yes' => '네, 해봤어요',
                'partial' => '부분적으로요',
                'no' => '아니요'
            ]
        ],
        [
            'id' => 'complex_problem',
            'category' => '단원 정리',
            'icon' => '🧩',
            'preview' => '복잡한 문제 만났을 때',
            'message' => '여러 개념이 섞인 복잡한 문제를 만나면 어떻게 해요?',
            'type' => 'buttons',
            'options' => [
                'skip' => '일단 넘겨요',
                'try' => '대충 시도해봐요',
                'break' => '쪼개서 풀어봐요',
                'solve' => '끝까지 풀어요!'
            ]
        ],
        [
            'id' => 'study_distribution',
            'category' => '단원 정리',
            'icon' => '📅',
            'preview' => '공부 분배',
            'message' => '시험 공부는 보통 어떻게 하세요?',
            'type' => 'buttons',
            'options' => [
                'cramming' => '직전에 몰아서요 😅',
                'twodays' => '이틀 전부터요',
                'week' => '일주일 전부터요',
                'steady' => '꾸준히 해요!'
            ]
        ],

        // S9: 설명듣기
        [
            'id' => 'note_taking',
            'category' => '설명듣기',
            'icon' => '📒',
            'preview' => '설명 들으면서 메모',
            'message' => '선생님 설명 들을 때 메모하나요?',
            'type' => 'buttons',
            'options' => [
                'always' => '항상 해요',
                'important' => '중요한 것만요',
                'rarely' => '거의 안 해요',
                'never' => '안 해요'
            ]
        ],
        [
            'id' => 'questions_asked',
            'category' => '설명듣기',
            'icon' => '🙋',
            'preview' => '질문하기',
            'message' => '모르는 게 있을 때 질문하나요?',
            'type' => 'buttons',
            'options' => [
                'always' => '바로 질문해요',
                'after' => '나중에 따로 물어봐요',
                'search' => '혼자 찾아봐요',
                'skip' => '그냥 넘어가요'
            ]
        ],
        [
            'id' => 'review_timing',
            'category' => '설명듣기',
            'icon' => '⏰',
            'preview' => '복습 타이밍',
            'message' => '수업 듣고 나서 언제 복습하세요?',
            'type' => 'buttons',
            'options' => [
                'same_day' => '당일에요',
                'next_day' => '다음 날이요',
                'before_test' => '시험 전에요',
                'never' => '따로 안 해요'
            ]
        ]
    ];

    /**
     * 선생님 판단 질문
     */
    public static $teacherQuestions = [
        [
            'id' => 'pattern_confirmed',
            'category' => '패턴 확정',
            'icon' => '🎯',
            'preview' => 'AI 탐지 패턴 확인',
            'message' => '시스템이 탐지한 학생의 인지관성 패턴을 확인해주세요.',
            'type' => 'buttons',
            'options' => [
                'confirmed' => '확정합니다',
                'modified' => '수정이 필요해요',
                'rejected' => '해당 없음'
            ]
        ],
        [
            'id' => 'pattern_override',
            'category' => '패턴 지정',
            'icon' => '✏️',
            'preview' => '패턴 수동 지정',
            'message' => '관찰하신 패턴이 있다면 선택해주세요.',
            'type' => 'select',
            'options' => [
                '' => '선택 안함',
                'helpless_learner' => '무기력 학습자형',
                'perfectionist_procrastinator' => '완벽주의 지연형',
                'comparison_inferiority' => '비교 열등감형',
                'speed_skimmer' => '속독 건너뛰기형',
                'term_ignorer' => '단어 무시형',
                'single_read' => '반복 없는 일회독형',
                'memorization_dependent' => '암기 의존형',
                'connection_disconnected' => '연결 단절형',
                'no_example' => '예시 부재형',
                'skip_check' => '확인 건너뛰기형',
                'false_positive' => '거짓 긍정형',
                'example_skipper' => '예제 스킵형',
                'answer_only' => '답만 보기형',
                'type_confusion' => '유형 혼동형',
                'test_anxiety' => '시험 불안형',
                'cramming_dependent' => '벼락치기 의존형',
                'passive_listener' => '수동 청취형',
                'question_avoider' => '질문 회피형',
                'review_delay' => '복습 지연형'
            ]
        ],
        [
            'id' => 'confidence_adjustment',
            'category' => '신뢰도',
            'icon' => '📊',
            'preview' => '신뢰도 조정',
            'message' => '패턴 탐지의 정확도를 어떻게 평가하시나요?',
            'type' => 'scale',
            'options' => [
                50 => '50% - 낮음',
                65 => '65%',
                75 => '75%',
                85 => '85% - 기본',
                95 => '95%',
                100 => '100% - 확실'
            ]
        ],
        [
            'id' => 'intervention_effectiveness',
            'category' => '개입 평가',
            'icon' => '📈',
            'preview' => '개입 효과',
            'message' => '지금까지의 개입이 얼마나 효과적이었나요?',
            'type' => 'scale',
            'options' => [
                1 => '전혀 효과 없음',
                2 => '약간 효과 있음',
                3 => '보통',
                4 => '효과적',
                5 => '매우 효과적'
            ]
        ],
        [
            'id' => 'intervention_notes',
            'category' => '관찰 기록',
            'icon' => '📝',
            'preview' => '관찰 노트',
            'message' => '학생에 대한 추가 관찰 사항을 기록해주세요.',
            'type' => 'textarea',
            'placeholder' => '학생의 변화, 반응, 특이사항 등...'
        ],
        [
            'id' => 'custom_action',
            'category' => '맞춤 지도',
            'icon' => '🎓',
            'preview' => '맞춤형 활동',
            'message' => '이 학생에게 추천하고 싶은 구체적인 활동이 있나요?',
            'type' => 'textarea',
            'placeholder' => '예: 매일 5분 개념 요약 쓰기, 친구에게 설명하기 등...'
        ]
    ];

    /**
     * 시스템 데이터 필드 정의
     */
    public static $systemDataFields = [
        // AI 탐지 결과
        [
            'id' => 'ai_detected_pattern',
            'category' => 'AI 탐지 결과',
            'icon' => '🤖',
            'label' => '탐지된 패턴',
            'type' => 'display',
            'source' => 'ai_model'
        ],
        [
            'id' => 'ai_confidence',
            'category' => 'AI 탐지 결과',
            'icon' => '📊',
            'label' => 'AI 신뢰도',
            'type' => 'gauge',
            'source' => 'ai_model'
        ],
        [
            'id' => 'detection_timestamp',
            'category' => 'AI 탐지 결과',
            'icon' => '🕐',
            'label' => '탐지 시각',
            'type' => 'timestamp',
            'source' => 'system'
        ],

        // 학습 분석 데이터
        [
            'id' => 'study_time_weekly',
            'category' => '학습 분석',
            'icon' => '⏱️',
            'label' => '주간 학습 시간',
            'type' => 'stat',
            'unit' => '시간',
            'source' => 'learning_analytics'
        ],
        [
            'id' => 'problem_accuracy',
            'category' => '학습 분석',
            'icon' => '✅',
            'label' => '문제 정답률',
            'type' => 'percentage',
            'source' => 'learning_analytics'
        ],
        [
            'id' => 'concept_mastery',
            'category' => '학습 분석',
            'icon' => '📈',
            'label' => '개념 이해도',
            'type' => 'percentage',
            'source' => 'learning_analytics'
        ],
        [
            'id' => 'engagement_score',
            'category' => '학습 분석',
            'icon' => '💪',
            'label' => '참여도 점수',
            'type' => 'score',
            'source' => 'learning_analytics'
        ],

        // 패턴 모델링 결과
        [
            'id' => 'pattern_history',
            'category' => '패턴 모델링',
            'icon' => '📉',
            'label' => '패턴 변화 추이',
            'type' => 'chart',
            'source' => 'pattern_model'
        ],
        [
            'id' => 'risk_factors',
            'category' => '패턴 모델링',
            'icon' => '⚠️',
            'label' => '위험 요소',
            'type' => 'list',
            'source' => 'pattern_model'
        ],
        [
            'id' => 'improvement_areas',
            'category' => '패턴 모델링',
            'icon' => '🎯',
            'label' => '개선 필요 영역',
            'type' => 'list',
            'source' => 'pattern_model'
        ],
        [
            'id' => 'recommended_interventions',
            'category' => '패턴 모델링',
            'icon' => '💡',
            'label' => '추천 개입 방법',
            'type' => 'list',
            'source' => 'pattern_model'
        ]
    ];
}

// 요청 처리
$studentId = optional_param('student_id', $USER->id, PARAM_INT);
$activeTab = optional_param('tab', 'student', PARAM_ALPHA);
$action = optional_param('action', '', PARAM_ALPHA);

// AJAX 데이터 저장 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save') {
    header('Content-Type: application/json');

    try {
        $data = json_decode(file_get_contents('php://input'), true);

        $record = new stdClass();
        $record->userid = $studentId;
        $record->data_type = $data['type'] ?? 'student_chat';
        $record->data_json = json_encode($data['answers'], JSON_UNESCAPED_UNICODE);
        $record->created_at = time();
        $record->created_by = $USER->id;

        $DB->insert_record('agent04_chat_data', $record);

        echo json_encode(['success' => true, 'message' => '저장되었습니다.']);
    } catch (Exception $e) {
        error_log("chat01.php:" . __LINE__ . " - 저장 오류: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => '저장 중 오류: ' . $e->getMessage()]);
    }
    exit;
}

// 시스템 데이터 조회 (실제 구현 시 DB에서 가져옴)
function getSystemData($studentId) {
    global $DB;

    // 샘플 데이터 (실제로는 DB에서 조회)
    return [
        'ai_detected_pattern' => '완벽주의 지연형',
        'ai_confidence' => 78,
        'detection_timestamp' => time() - 86400,
        'study_time_weekly' => 12.5,
        'problem_accuracy' => 72,
        'concept_mastery' => 65,
        'engagement_score' => 7.2,
        'pattern_history' => [
            ['date' => '11/25', 'score' => 45],
            ['date' => '11/27', 'score' => 52],
            ['date' => '11/29', 'score' => 48],
            ['date' => '12/01', 'score' => 58],
            ['date' => '12/03', 'score' => 65]
        ],
        'risk_factors' => ['시작 지연 빈도 높음', '완벽주의적 성향', '자기효능감 낮음'],
        'improvement_areas' => ['작은 목표 설정', '성취 기록 습관화', '시작 의식 만들기'],
        'recommended_interventions' => [
            '5분 타이머 기법',
            '일일 성취 일기',
            '단계별 목표 시각화'
        ]
    ];
}

$systemData = getSystemData($studentId);

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent04 - 대화형 학습 진단</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-light: #818cf8;
            --primary-dark: #4f46e5;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --bg: #0f172a;
            --bg-light: #1e293b;
            --bg-card: #334155;
            --text: #f1f5f9;
            --text-muted: #94a3b8;
            --border: #475569;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            line-height: 1.6;
        }

        .chat-container {
            max-width: 700px;
            margin: 0 auto;
            padding: 20px;
            padding-bottom: 100px;
        }

        /* 헤더 */
        .chat-header {
            text-align: center;
            padding: 20px;
            margin-bottom: 20px;
        }

        .chat-header h1 {
            font-size: 1.4rem;
            margin-bottom: 8px;
            background: linear-gradient(135deg, var(--primary-light), var(--success));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .chat-header p {
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        /* 탭 네비게이션 */
        .tab-nav {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            background: var(--bg-light);
            padding: 6px;
            border-radius: 12px;
        }

        .tab-btn {
            flex: 1;
            padding: 12px 16px;
            border: none;
            border-radius: 8px;
            background: transparent;
            color: var(--text-muted);
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .tab-btn:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .tab-btn.active {
            background: var(--primary);
            color: white;
        }

        .tab-btn .badge {
            background: rgba(255,255,255,0.2);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.75rem;
        }

        .tab-btn.active .badge {
            background: rgba(255,255,255,0.3);
        }

        /* 탭 컨텐츠 */
        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* 진행률 */
        .progress-bar {
            background: var(--bg-light);
            border-radius: 10px;
            height: 8px;
            margin-bottom: 20px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--success));
            width: 0%;
            transition: width 0.5s ease;
            border-radius: 10px;
        }

        .progress-text {
            text-align: center;
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 20px;
        }

        /* 질문 카드 */
        .question-card {
            background: var(--bg-light);
            border-radius: 16px;
            margin-bottom: 12px;
            overflow: hidden;
            border: 1px solid var(--border);
            transition: all 0.3s ease;
        }

        .question-card.completed {
            border-color: var(--success);
            opacity: 0.7;
        }

        .question-card.active {
            border-color: var(--primary);
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.2);
        }

        .question-card.locked {
            opacity: 0.4;
            pointer-events: none;
        }

        /* 질문 버튼 (접힌 상태) */
        .question-button {
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
            padding: 16px 20px;
            background: none;
            border: none;
            color: var(--text);
            cursor: pointer;
            text-align: left;
            transition: background 0.3s ease;
        }

        .question-button:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .question-icon {
            font-size: 1.5rem;
            min-width: 40px;
            text-align: center;
        }

        .question-preview {
            flex: 1;
        }

        .question-preview .category {
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .question-preview .title {
            font-weight: 500;
            font-size: 0.95rem;
        }

        .question-status {
            font-size: 1.2rem;
        }

        .question-card.completed .question-status::after {
            content: '✓';
            color: var(--success);
        }

        .question-card.active .question-status::after {
            content: '▼';
            color: var(--primary);
            font-size: 0.8rem;
        }

        .question-card:not(.active):not(.completed) .question-status::after {
            content: '○';
            color: var(--text-muted);
        }

        /* 질문 내용 (펼친 상태) */
        .question-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s ease, padding 0.4s ease;
        }

        .question-card.active .question-content {
            max-height: 500px;
            padding: 0 20px 20px;
        }

        .typing-area {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 16px;
            min-height: 60px;
        }

        .typing-text {
            color: var(--text);
            font-size: 1rem;
            line-height: 1.6;
        }

        .typing-cursor {
            display: inline-block;
            width: 2px;
            height: 1em;
            background: var(--primary);
            margin-left: 2px;
            animation: blink 1s infinite;
        }

        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0; }
        }

        /* 답변 옵션들 */
        .answer-options {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s ease;
        }

        .answer-options.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .answer-btn {
            padding: 12px 20px;
            border: 2px solid var(--border);
            border-radius: 25px;
            background: transparent;
            color: var(--text);
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            flex: 1;
            min-width: calc(50% - 10px);
            text-align: center;
        }

        .answer-btn:hover {
            border-color: var(--primary);
            background: rgba(99, 102, 241, 0.1);
        }

        .answer-btn.selected {
            border-color: var(--success);
            background: var(--success);
            color: white;
        }

        /* 스케일 답변 */
        .scale-options {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .scale-btn {
            padding: 12px 16px;
            border: 2px solid var(--border);
            border-radius: 12px;
            background: transparent;
            color: var(--text);
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .scale-btn:hover {
            border-color: var(--primary);
            background: rgba(99, 102, 241, 0.1);
        }

        .scale-btn.selected {
            border-color: var(--success);
            background: rgba(16, 185, 129, 0.2);
        }

        .scale-btn .value {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--bg-card);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .scale-btn.selected .value {
            background: var(--success);
            color: white;
        }

        /* 멀티셀렉트 */
        .multiselect-options {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .multi-btn {
            padding: 10px 16px;
            border: 2px solid var(--border);
            border-radius: 20px;
            background: transparent;
            color: var(--text);
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .multi-btn:hover {
            border-color: var(--primary);
        }

        .multi-btn.selected {
            border-color: var(--primary);
            background: rgba(99, 102, 241, 0.2);
        }

        .multi-submit {
            width: 100%;
            margin-top: 10px;
            padding: 12px;
            border: none;
            border-radius: 12px;
            background: var(--primary);
            color: white;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .multi-submit:hover {
            background: var(--primary-dark);
        }

        /* 텍스트 입력 */
        .text-input-area {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .text-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border);
            border-radius: 12px;
            background: var(--bg-card);
            color: var(--text);
            font-size: 0.95rem;
            resize: none;
            min-height: 80px;
        }

        .text-input:focus {
            outline: none;
            border-color: var(--primary);
        }

        .text-submit {
            align-self: flex-end;
            padding: 10px 24px;
            border: none;
            border-radius: 20px;
            background: var(--primary);
            color: white;
            font-size: 0.9rem;
            cursor: pointer;
        }

        /* 셀렉트 */
        .select-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border);
            border-radius: 12px;
            background: var(--bg-card);
            color: var(--text);
            font-size: 0.95rem;
            cursor: pointer;
        }

        .select-input:focus {
            outline: none;
            border-color: var(--primary);
        }

        .select-submit {
            margin-top: 10px;
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 12px;
            background: var(--primary);
            color: white;
            cursor: pointer;
        }

        /* 시스템 데이터 섹션 */
        .system-section {
            margin-bottom: 24px;
        }

        .system-section-title {
            font-size: 0.85rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 12px;
            padding-left: 8px;
        }

        .system-card {
            background: var(--bg-light);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 10px;
            border: 1px solid var(--border);
        }

        .system-card-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .system-card-icon {
            font-size: 1.3rem;
        }

        .system-card-label {
            font-weight: 500;
            font-size: 0.9rem;
        }

        .system-card-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-light);
        }

        .system-card-value.warning {
            color: var(--warning);
        }

        .system-card-value.success {
            color: var(--success);
        }

        .system-card-value.danger {
            color: var(--danger);
        }

        /* 게이지 */
        .gauge-container {
            margin-top: 8px;
        }

        .gauge-bar {
            height: 8px;
            background: var(--bg-card);
            border-radius: 4px;
            overflow: hidden;
        }

        .gauge-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.5s ease;
        }

        .gauge-fill.low { background: var(--danger); }
        .gauge-fill.medium { background: var(--warning); }
        .gauge-fill.high { background: var(--success); }

        /* 리스트 아이템 */
        .system-list {
            list-style: none;
        }

        .system-list li {
            padding: 8px 12px;
            background: var(--bg-card);
            border-radius: 8px;
            margin-bottom: 6px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .system-list li::before {
            content: '•';
            color: var(--primary);
        }

        /* 미니 차트 (간단한 바 차트) */
        .mini-chart {
            display: flex;
            align-items: flex-end;
            gap: 6px;
            height: 60px;
            padding: 8px 0;
        }

        .mini-chart-bar {
            flex: 1;
            background: var(--primary);
            border-radius: 4px 4px 0 0;
            transition: height 0.3s ease;
            position: relative;
        }

        .mini-chart-bar::after {
            content: attr(data-label);
            position: absolute;
            bottom: -20px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 0.7rem;
            color: var(--text-muted);
        }

        /* 완료 화면 */
        .completion-screen {
            display: none;
            text-align: center;
            padding: 60px 20px;
        }

        .completion-screen.show {
            display: block;
        }

        .completion-icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }

        .completion-screen h2 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: var(--success);
        }

        .completion-screen p {
            color: var(--text-muted);
            margin-bottom: 30px;
        }

        .restart-btn {
            padding: 14px 32px;
            border: none;
            border-radius: 25px;
            background: var(--primary);
            color: white;
            font-size: 1rem;
            cursor: pointer;
        }

        /* 반응형 */
        @media (max-width: 480px) {
            .answer-btn {
                min-width: 100%;
            }

            .tab-btn {
                padding: 10px 8px;
                font-size: 0.8rem;
            }

            .tab-btn .badge {
                display: none;
            }
        }

        /* 파일 전환 드랍업 메뉴 */
        .file-switcher {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 1000;
        }

        .file-switcher-btn {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(99, 102, 241, 0.4);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .file-switcher-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 25px rgba(99, 102, 241, 0.5);
        }

        .file-switcher-btn.active {
            background: linear-gradient(135deg, var(--danger), #dc2626);
        }

        .file-switcher-menu {
            position: absolute;
            bottom: 70px;
            right: 0;
            background: var(--bg-light);
            border-radius: 16px;
            border: 1px solid var(--border);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.3s ease;
            min-width: 180px;
        }

        .file-switcher-menu.open {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .file-switcher-menu-header {
            padding: 12px 16px;
            background: var(--bg-card);
            border-bottom: 1px solid var(--border);
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .file-switcher-menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: var(--text);
            text-decoration: none;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
        }

        .file-switcher-menu-item:hover {
            background: rgba(99, 102, 241, 0.1);
            border-left-color: var(--primary);
        }

        .file-switcher-menu-item.current {
            background: rgba(99, 102, 241, 0.2);
            border-left-color: var(--primary);
            color: var(--primary-light);
        }

        .file-switcher-menu-item .num {
            width: 24px;
            height: 24px;
            border-radius: 6px;
            background: var(--bg-card);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .file-switcher-menu-item.current .num {
            background: var(--primary);
            color: white;
        }

        .file-switcher-menu-item .label {
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="chat-header">
            <h1>🧠 학습 패턴 진단</h1>
            <p>rules01.yaml 기반 인지관성 패턴 분석</p>
        </div>

        <!-- 탭 네비게이션 -->
        <div class="tab-nav">
            <button class="tab-btn <?php echo $activeTab === 'student' ? 'active' : ''; ?>"
                    onclick="switchTab('student')">
                👨‍🎓 학생 대화
                <span class="badge"><?php echo count(ConversationalQuestions::$studentQuestions); ?></span>
            </button>
            <button class="tab-btn <?php echo $activeTab === 'teacher' ? 'active' : ''; ?>"
                    onclick="switchTab('teacher')">
                👨‍🏫 선생님 입력
                <span class="badge"><?php echo count(ConversationalQuestions::$teacherQuestions); ?></span>
            </button>
            <button class="tab-btn <?php echo $activeTab === 'system' ? 'active' : ''; ?>"
                    onclick="switchTab('system')">
                🤖 시스템 데이터
            </button>
        </div>

        <!-- 학생 대화 탭 -->
        <div class="tab-content <?php echo $activeTab === 'student' ? 'active' : ''; ?>" id="studentTab">
            <div class="progress-bar">
                <div class="progress-fill" id="studentProgressFill"></div>
            </div>
            <div class="progress-text" id="studentProgressText">0 / <?php echo count(ConversationalQuestions::$studentQuestions); ?> 완료</div>

            <div class="questions-container" id="studentQuestionsContainer">
                <?php foreach (ConversationalQuestions::$studentQuestions as $index => $q): ?>
                <div class="question-card <?php echo $index === 0 ? 'active' : 'locked'; ?>"
                     data-index="<?php echo $index; ?>"
                     data-id="<?php echo $q['id']; ?>"
                     data-type="<?php echo $q['type']; ?>"
                     data-tab="student">

                    <button class="question-button" onclick="toggleQuestion(<?php echo $index; ?>, 'student')">
                        <span class="question-icon"><?php echo $q['icon']; ?></span>
                        <div class="question-preview">
                            <div class="category"><?php echo htmlspecialchars($q['category']); ?></div>
                            <div class="title"><?php echo htmlspecialchars($q['preview']); ?></div>
                        </div>
                        <span class="question-status"></span>
                    </button>

                    <div class="question-content">
                        <div class="typing-area">
                            <span class="typing-text" data-message="<?php echo htmlspecialchars($q['message']); ?>"></span>
                            <span class="typing-cursor"></span>
                        </div>

                        <div class="answer-options" data-type="<?php echo $q['type']; ?>">
                            <?php if ($q['type'] === 'buttons'): ?>
                                <?php foreach ($q['options'] as $value => $label): ?>
                                <button class="answer-btn" data-value="<?php echo htmlspecialchars($value); ?>">
                                    <?php echo htmlspecialchars($label); ?>
                                </button>
                                <?php endforeach; ?>

                            <?php elseif ($q['type'] === 'scale'): ?>
                                <div class="scale-options">
                                    <?php foreach ($q['options'] as $value => $label): ?>
                                    <button class="scale-btn" data-value="<?php echo $value; ?>">
                                        <span class="value"><?php echo $value; ?></span>
                                        <span><?php echo htmlspecialchars($label); ?></span>
                                    </button>
                                    <?php endforeach; ?>
                                </div>

                            <?php elseif ($q['type'] === 'multiselect'): ?>
                                <div class="multiselect-options">
                                    <?php foreach ($q['options'] as $value => $label): ?>
                                    <button class="multi-btn" data-value="<?php echo htmlspecialchars($value); ?>">
                                        <?php echo htmlspecialchars($label); ?>
                                    </button>
                                    <?php endforeach; ?>
                                </div>
                                <button class="multi-submit">선택 완료</button>

                            <?php elseif ($q['type'] === 'text' || $q['type'] === 'textarea'): ?>
                                <div class="text-input-area">
                                    <textarea class="text-input"
                                              placeholder="<?php echo htmlspecialchars($q['placeholder'] ?? '입력해주세요...'); ?>"></textarea>
                                    <button class="text-submit">입력 완료</button>
                                </div>

                            <?php elseif ($q['type'] === 'select'): ?>
                                <select class="select-input">
                                    <?php foreach ($q['options'] as $value => $label): ?>
                                    <option value="<?php echo htmlspecialchars($value); ?>">
                                        <?php echo htmlspecialchars($label); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="select-submit">선택 완료</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="completion-screen" id="studentCompletionScreen">
                <div class="completion-icon">🎉</div>
                <h2>학생 대화 완료!</h2>
                <p>답변이 저장되었습니다. 감사합니다!</p>
                <button class="restart-btn" onclick="restartChat('student')">처음부터 다시</button>
            </div>
        </div>

        <!-- 선생님 입력 탭 -->
        <div class="tab-content <?php echo $activeTab === 'teacher' ? 'active' : ''; ?>" id="teacherTab">
            <?php if ($role !== 'student'): ?>
            <div class="progress-bar">
                <div class="progress-fill" id="teacherProgressFill"></div>
            </div>
            <div class="progress-text" id="teacherProgressText">0 / <?php echo count(ConversationalQuestions::$teacherQuestions); ?> 완료</div>

            <div class="questions-container" id="teacherQuestionsContainer">
                <?php foreach (ConversationalQuestions::$teacherQuestions as $index => $q): ?>
                <div class="question-card <?php echo $index === 0 ? 'active' : 'locked'; ?>"
                     data-index="<?php echo $index; ?>"
                     data-id="<?php echo $q['id']; ?>"
                     data-type="<?php echo $q['type']; ?>"
                     data-tab="teacher">

                    <button class="question-button" onclick="toggleQuestion(<?php echo $index; ?>, 'teacher')">
                        <span class="question-icon"><?php echo $q['icon']; ?></span>
                        <div class="question-preview">
                            <div class="category"><?php echo htmlspecialchars($q['category']); ?></div>
                            <div class="title"><?php echo htmlspecialchars($q['preview']); ?></div>
                        </div>
                        <span class="question-status"></span>
                    </button>

                    <div class="question-content">
                        <div class="typing-area">
                            <span class="typing-text" data-message="<?php echo htmlspecialchars($q['message']); ?>"></span>
                            <span class="typing-cursor"></span>
                        </div>

                        <div class="answer-options" data-type="<?php echo $q['type']; ?>">
                            <?php if ($q['type'] === 'buttons'): ?>
                                <?php foreach ($q['options'] as $value => $label): ?>
                                <button class="answer-btn" data-value="<?php echo htmlspecialchars($value); ?>">
                                    <?php echo htmlspecialchars($label); ?>
                                </button>
                                <?php endforeach; ?>

                            <?php elseif ($q['type'] === 'scale'): ?>
                                <div class="scale-options">
                                    <?php foreach ($q['options'] as $value => $label): ?>
                                    <button class="scale-btn" data-value="<?php echo $value; ?>">
                                        <span class="value"><?php echo $value; ?></span>
                                        <span><?php echo htmlspecialchars($label); ?></span>
                                    </button>
                                    <?php endforeach; ?>
                                </div>

                            <?php elseif ($q['type'] === 'textarea'): ?>
                                <div class="text-input-area">
                                    <textarea class="text-input"
                                              placeholder="<?php echo htmlspecialchars($q['placeholder'] ?? '입력해주세요...'); ?>"></textarea>
                                    <button class="text-submit">입력 완료</button>
                                </div>

                            <?php elseif ($q['type'] === 'select'): ?>
                                <select class="select-input">
                                    <?php foreach ($q['options'] as $value => $label): ?>
                                    <option value="<?php echo htmlspecialchars($value); ?>">
                                        <?php echo htmlspecialchars($label); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="select-submit">선택 완료</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="completion-screen" id="teacherCompletionScreen">
                <div class="completion-icon">✅</div>
                <h2>선생님 입력 완료!</h2>
                <p>판단 내용이 저장되었습니다.</p>
                <button class="restart-btn" onclick="restartChat('teacher')">처음부터 다시</button>
            </div>
            <?php else: ?>
            <div style="text-align: center; padding: 60px 20px;">
                <div style="font-size: 3rem; margin-bottom: 20px;">🔒</div>
                <h2 style="color: var(--text-muted);">선생님 전용</h2>
                <p style="color: var(--text-muted);">이 탭은 선생님만 접근할 수 있습니다.</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- 시스템 데이터 탭 -->
        <div class="tab-content <?php echo $activeTab === 'system' ? 'active' : ''; ?>" id="systemTab">
            <!-- AI 탐지 결과 -->
            <div class="system-section">
                <div class="system-section-title">🤖 AI 탐지 결과</div>

                <div class="system-card">
                    <div class="system-card-header">
                        <span class="system-card-icon">🎯</span>
                        <span class="system-card-label">탐지된 패턴</span>
                    </div>
                    <div class="system-card-value warning">
                        <?php echo htmlspecialchars($systemData['ai_detected_pattern']); ?>
                    </div>
                </div>

                <div class="system-card">
                    <div class="system-card-header">
                        <span class="system-card-icon">📊</span>
                        <span class="system-card-label">AI 신뢰도</span>
                    </div>
                    <div class="system-card-value"><?php echo $systemData['ai_confidence']; ?>%</div>
                    <div class="gauge-container">
                        <div class="gauge-bar">
                            <div class="gauge-fill <?php echo $systemData['ai_confidence'] >= 80 ? 'high' : ($systemData['ai_confidence'] >= 60 ? 'medium' : 'low'); ?>"
                                 style="width: <?php echo $systemData['ai_confidence']; ?>%"></div>
                        </div>
                    </div>
                </div>

                <div class="system-card">
                    <div class="system-card-header">
                        <span class="system-card-icon">🕐</span>
                        <span class="system-card-label">탐지 시각</span>
                    </div>
                    <div class="system-card-value" style="font-size: 1rem;">
                        <?php echo date('Y-m-d H:i', $systemData['detection_timestamp']); ?>
                    </div>
                </div>
            </div>

            <!-- 학습 분석 -->
            <div class="system-section">
                <div class="system-section-title">📈 학습 분석 데이터</div>

                <div class="system-card">
                    <div class="system-card-header">
                        <span class="system-card-icon">⏱️</span>
                        <span class="system-card-label">주간 학습 시간</span>
                    </div>
                    <div class="system-card-value"><?php echo $systemData['study_time_weekly']; ?> <span style="font-size: 0.8rem; color: var(--text-muted);">시간</span></div>
                </div>

                <div class="system-card">
                    <div class="system-card-header">
                        <span class="system-card-icon">✅</span>
                        <span class="system-card-label">문제 정답률</span>
                    </div>
                    <div class="system-card-value <?php echo $systemData['problem_accuracy'] >= 80 ? 'success' : ($systemData['problem_accuracy'] >= 60 ? '' : 'danger'); ?>">
                        <?php echo $systemData['problem_accuracy']; ?>%
                    </div>
                    <div class="gauge-container">
                        <div class="gauge-bar">
                            <div class="gauge-fill <?php echo $systemData['problem_accuracy'] >= 80 ? 'high' : ($systemData['problem_accuracy'] >= 60 ? 'medium' : 'low'); ?>"
                                 style="width: <?php echo $systemData['problem_accuracy']; ?>%"></div>
                        </div>
                    </div>
                </div>

                <div class="system-card">
                    <div class="system-card-header">
                        <span class="system-card-icon">📈</span>
                        <span class="system-card-label">개념 이해도</span>
                    </div>
                    <div class="system-card-value"><?php echo $systemData['concept_mastery']; ?>%</div>
                    <div class="gauge-container">
                        <div class="gauge-bar">
                            <div class="gauge-fill <?php echo $systemData['concept_mastery'] >= 80 ? 'high' : ($systemData['concept_mastery'] >= 60 ? 'medium' : 'low'); ?>"
                                 style="width: <?php echo $systemData['concept_mastery']; ?>%"></div>
                        </div>
                    </div>
                </div>

                <div class="system-card">
                    <div class="system-card-header">
                        <span class="system-card-icon">💪</span>
                        <span class="system-card-label">참여도 점수</span>
                    </div>
                    <div class="system-card-value"><?php echo $systemData['engagement_score']; ?> <span style="font-size: 0.8rem; color: var(--text-muted);">/ 10</span></div>
                </div>
            </div>

            <!-- 패턴 모델링 -->
            <div class="system-section">
                <div class="system-section-title">🔮 패턴 모델링 결과</div>

                <div class="system-card">
                    <div class="system-card-header">
                        <span class="system-card-icon">📉</span>
                        <span class="system-card-label">패턴 점수 변화 추이</span>
                    </div>
                    <div class="mini-chart">
                        <?php foreach ($systemData['pattern_history'] as $point): ?>
                        <div class="mini-chart-bar"
                             style="height: <?php echo $point['score']; ?>%"
                             data-label="<?php echo $point['date']; ?>"></div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="system-card">
                    <div class="system-card-header">
                        <span class="system-card-icon">⚠️</span>
                        <span class="system-card-label">위험 요소</span>
                    </div>
                    <ul class="system-list">
                        <?php foreach ($systemData['risk_factors'] as $factor): ?>
                        <li><?php echo htmlspecialchars($factor); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="system-card">
                    <div class="system-card-header">
                        <span class="system-card-icon">🎯</span>
                        <span class="system-card-label">개선 필요 영역</span>
                    </div>
                    <ul class="system-list">
                        <?php foreach ($systemData['improvement_areas'] as $area): ?>
                        <li><?php echo htmlspecialchars($area); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="system-card">
                    <div class="system-card-header">
                        <span class="system-card-icon">💡</span>
                        <span class="system-card-label">추천 개입 방법</span>
                    </div>
                    <ul class="system-list">
                        <?php foreach ($systemData['recommended_interventions'] as $intervention): ?>
                        <li><?php echo htmlspecialchars($intervention); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- 파일 전환 드랍업 메뉴 -->
    <div class="file-switcher">
        <div class="file-switcher-menu" id="fileSwitcherMenu">
            <div class="file-switcher-menu-header">📂 Chat Files</div>
            <?php
            $currentFile = basename(__FILE__, '.php'); // chat01
            $currentTab = $activeTab;
            for ($i = 1; $i <= 7; $i++):
                $fileNum = str_pad($i, 2, '0', STR_PAD_LEFT);
                $fileName = "chat{$fileNum}";
                $isCurrent = ($fileName === $currentFile);
            ?>
            <a href="<?php echo $fileName; ?>.php?tab=<?php echo $currentTab; ?>"
               class="file-switcher-menu-item <?php echo $isCurrent ? 'current' : ''; ?>">
                <span class="num"><?php echo $i; ?></span>
                <span class="label">Chat <?php echo $fileNum; ?></span>
            </a>
            <?php endfor; ?>
        </div>
        <button class="file-switcher-btn" id="fileSwitcherBtn" onclick="toggleFileSwitcher()" title="파일 전환">
            <span id="fileSwitcherIcon">📁</span>
        </button>
    </div>

    <script>
        // 파일 전환 메뉴 토글
        function toggleFileSwitcher() {
            const menu = document.getElementById('fileSwitcherMenu');
            const btn = document.getElementById('fileSwitcherBtn');
            const icon = document.getElementById('fileSwitcherIcon');

            menu.classList.toggle('open');
            btn.classList.toggle('active');

            if (menu.classList.contains('open')) {
                icon.textContent = '✕';
            } else {
                icon.textContent = '📁';
            }
        }

        // 외부 클릭시 메뉴 닫기
        document.addEventListener('click', function(e) {
            const switcher = document.querySelector('.file-switcher');
            if (!switcher.contains(e.target)) {
                const menu = document.getElementById('fileSwitcherMenu');
                const btn = document.getElementById('fileSwitcherBtn');
                const icon = document.getElementById('fileSwitcherIcon');

                menu.classList.remove('open');
                btn.classList.remove('active');
                icon.textContent = '📁';
            }
        });

        // 상태 관리
        const state = {
            student: {
                currentIndex: 0,
                answers: {},
                totalQuestions: <?php echo count(ConversationalQuestions::$studentQuestions); ?>
            },
            teacher: {
                currentIndex: 0,
                answers: {},
                totalQuestions: <?php echo count(ConversationalQuestions::$teacherQuestions); ?>
            },
            studentId: <?php echo $studentId; ?>,
            activeTab: '<?php echo $activeTab; ?>'
        };

        // 탭 전환
        function switchTab(tabName) {
            // URL 업데이트
            const url = new URL(window.location);
            url.searchParams.set('tab', tabName);
            window.history.pushState({}, '', url);

            // 탭 버튼 업데이트
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            event.target.closest('.tab-btn').classList.add('active');

            // 컨텐츠 전환
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            document.getElementById(tabName + 'Tab').classList.add('active');

            state.activeTab = tabName;

            // 첫 질문 타이핑 시작
            if (tabName !== 'system') {
                const container = document.getElementById(tabName + 'QuestionsContainer');
                if (container) {
                    const firstCard = container.querySelector('.question-card.active');
                    if (firstCard && !firstCard.classList.contains('completed')) {
                        startTyping(firstCard);
                    }
                }
            }
        }

        // 초기화
        document.addEventListener('DOMContentLoaded', () => {
            // 활성 탭의 첫 질문 타이핑 시작
            const activeTab = state.activeTab;
            if (activeTab !== 'system') {
                const container = document.getElementById(activeTab + 'QuestionsContainer');
                if (container) {
                    const firstCard = container.querySelector('.question-card.active');
                    if (firstCard) {
                        setTimeout(() => startTyping(firstCard), 500);
                    }
                }
            }
            initAnswerHandlers();
        });

        // 질문 토글
        function toggleQuestion(index, tab) {
            const container = document.getElementById(tab + 'QuestionsContainer');
            const card = container.querySelector(`[data-index="${index}"]`);

            if (card.classList.contains('locked') || card.classList.contains('completed')) {
                return;
            }

            // 다른 질문 닫기
            container.querySelectorAll('.question-card.active').forEach(c => {
                if (c !== card) {
                    c.classList.remove('active');
                }
            });

            // 현재 질문 열기
            if (!card.classList.contains('active')) {
                card.classList.add('active');
                startTyping(card);
            }
        }

        // 타이핑 효과
        function startTyping(card) {
            const typingText = card.querySelector('.typing-text');
            const answerOptions = card.querySelector('.answer-options');
            const cursor = card.querySelector('.typing-cursor');
            const message = typingText.dataset.message;

            // 리셋
            typingText.textContent = '';
            answerOptions.classList.remove('visible');
            cursor.style.display = 'inline-block';

            let i = 0;
            const speed = 30;

            function type() {
                if (i < message.length) {
                    typingText.textContent += message.charAt(i);
                    i++;
                    setTimeout(type, speed);
                } else {
                    cursor.style.display = 'none';
                    setTimeout(() => {
                        answerOptions.classList.add('visible');
                    }, 300);
                }
            }

            type();
        }

        // 답변 핸들러 초기화
        function initAnswerHandlers() {
            // 단일 선택 버튼
            document.querySelectorAll('.answer-btn, .scale-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const card = this.closest('.question-card');
                    const options = this.closest('.answer-options');
                    const tab = card.dataset.tab;

                    options.querySelectorAll('.answer-btn, .scale-btn').forEach(b => b.classList.remove('selected'));
                    this.classList.add('selected');

                    const questionId = card.dataset.id;
                    const value = this.dataset.value;
                    saveAnswer(tab, questionId, value);

                    setTimeout(() => completeQuestion(card, tab), 400);
                });
            });

            // 멀티셀렉트
            document.querySelectorAll('.multi-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    this.classList.toggle('selected');
                });
            });

            document.querySelectorAll('.multi-submit').forEach(btn => {
                btn.addEventListener('click', function() {
                    const card = this.closest('.question-card');
                    const tab = card.dataset.tab;
                    const selected = Array.from(card.querySelectorAll('.multi-btn.selected'))
                        .map(b => b.dataset.value);

                    if (selected.length > 0) {
                        saveAnswer(tab, card.dataset.id, selected);
                        completeQuestion(card, tab);
                    }
                });
            });

            // 텍스트 입력
            document.querySelectorAll('.text-submit').forEach(btn => {
                btn.addEventListener('click', function() {
                    const card = this.closest('.question-card');
                    const tab = card.dataset.tab;
                    const input = card.querySelector('.text-input');
                    const value = input.value.trim();

                    if (value) {
                        saveAnswer(tab, card.dataset.id, value);
                        completeQuestion(card, tab);
                    }
                });
            });

            // 셀렉트
            document.querySelectorAll('.select-submit').forEach(btn => {
                btn.addEventListener('click', function() {
                    const card = this.closest('.question-card');
                    const tab = card.dataset.tab;
                    const select = card.querySelector('.select-input');
                    const value = select.value;

                    if (value) {
                        saveAnswer(tab, card.dataset.id, value);
                        completeQuestion(card, tab);
                    }
                });
            });
        }

        // 답변 저장
        function saveAnswer(tab, questionId, value) {
            state[tab].answers[questionId] = value;
        }

        // 질문 완료 처리
        function completeQuestion(card, tab) {
            const index = parseInt(card.dataset.index);
            const container = document.getElementById(tab + 'QuestionsContainer');

            card.classList.remove('active');
            card.classList.add('completed');

            const completedCount = container.querySelectorAll('.question-card.completed').length;
            updateProgress(tab, completedCount);

            const nextIndex = index + 1;
            const nextCard = container.querySelector(`[data-index="${nextIndex}"]`);

            if (nextCard) {
                nextCard.classList.remove('locked');
                setTimeout(() => {
                    nextCard.classList.add('active');
                    startTyping(nextCard);
                    nextCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 300);
            } else {
                saveAllAnswers(tab);
            }
        }

        // 진행률 업데이트
        function updateProgress(tab, completed) {
            const total = state[tab].totalQuestions;
            const percent = (completed / total) * 100;
            document.getElementById(tab + 'ProgressFill').style.width = percent + '%';
            document.getElementById(tab + 'ProgressText').textContent = `${completed} / ${total} 완료`;
        }

        // 전체 저장
        function saveAllAnswers(tab) {
            const dataType = tab === 'teacher' ? 'teacher_input' : 'student_chat';

            fetch('?action=save', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    type: dataType,
                    student_id: state.studentId,
                    answers: state[tab].answers
                })
            })
            .then(res => res.json())
            .then(data => {
                document.getElementById(tab + 'QuestionsContainer').style.display = 'none';
                document.getElementById(tab + 'CompletionScreen').classList.add('show');
            })
            .catch(err => {
                console.error('저장 오류:', err);
                alert('저장 중 오류가 발생했습니다.');
            });
        }

        // 다시 시작
        function restartChat(tab) {
            const url = new URL(window.location);
            url.searchParams.set('tab', tab);
            window.location.href = url.toString();
        }
    </script>
</body>
</html>
<?php
/**
 * =============================================================================
 * 관련 DB 테이블 정보
 * =============================================================================
 *
 * 테이블명: mdl_agent04_chat_data
 *
 * | 필드명      | 데이터 타입      | 설명                                    |
 * |------------|-----------------|----------------------------------------|
 * | id         | BIGINT(10)      | Primary Key, Auto Increment            |
 * | userid     | BIGINT(10)      | 학생 ID (FK: mdl_user.id)               |
 * | data_type  | VARCHAR(50)     | 'student_chat', 'teacher_input', 'system_data' |
 * | data_json  | LONGTEXT        | JSON 형태의 데이터                        |
 * | created_at | BIGINT(10)      | 생성 시간 (Unix timestamp)               |
 * | created_by | BIGINT(10)      | 생성자 ID (FK: mdl_user.id)             |
 *
 * =============================================================================
 */
?>
