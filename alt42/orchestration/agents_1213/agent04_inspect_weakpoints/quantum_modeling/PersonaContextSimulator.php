<?php
/**
 * PersonaContextSimulator - 상황 코드 기반 페르소나 시뮬레이터
 *
 * Agent03의 persona_system(rules.yaml, personas.md, contextlist.md)을
 * Agent04의 양자 모델링과 통합하여 상황별로 정교하게 모델링합니다.
 *
 * @package AugmentedTeacher\Agent04\QuantumModeling
 * @version 1.0.0
 * @since 2025-12-06
 */

if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}
global $DB, $USER;

class PersonaContextSimulator
{
    /** @var string 현재 파일 경로 */
    private $currentFile = __FILE__;

    // ============================================================
    // SECTION 1: 상황 코드 정의 (contextlist.md 기반)
    // ============================================================

    /** @var array 상황 코드 정의 */
    const CONTEXT_CODES = [
        'G0' => [
            'name' => '목표 설정 단계',
            'name_en' => 'Goal Setting',
            'icon' => '🎯',
            'priority' => 'Normal',
            'description' => '학습자가 새로운 목표를 설정하거나 기존 목표를 검토하는 단계',
            'entry_conditions' => [
                '새 학기/새 단원 시작',
                '목표가 설정되지 않음 (goals_count == 0)',
                '목표 설정 요청 메시지 감지',
            ],
            'keywords' => ['목표', '계획', '하고 싶어', '해야 해', '목표를 세우', '뭘 해야'],
            'sub_contexts' => [
                'G0.1' => '첫 목표 설정',
                'G0.2' => '추가 목표 설정',
                'G0.3' => '목표 수정 요청',
                'G0.4' => '목표 검토',
            ],
        ],
        'G1' => [
            'name' => '목표 진행 단계',
            'name_en' => 'Goal Progress',
            'icon' => '📈',
            'priority' => 'Normal',
            'description' => '설정된 목표를 향해 진행 중인 단계',
            'entry_conditions' => [
                '유효한 목표 존재 AND 진행 중',
                'G0에서 전이 (목표 설정 완료)',
                '진행 상황 보고/질문',
            ],
            'keywords' => ['어떻게 되고 있', '진행', '달성', '했어', '하고 있어'],
            'sub_contexts' => [
                'G1.1' => '순조로운 진행 (≥80%)',
                'G1.2' => '보통 진행 (50~80%)',
                'G1.3' => '느린 진행 (20~50%)',
                'G1.4' => '진행 어려움 (<20%)',
                'G1.5' => '목표 달성 임박 (≥90%)',
            ],
        ],
        'G2' => [
            'name' => '정체/위기 단계',
            'name_en' => 'Stagnation/Crisis',
            'icon' => '⚠️',
            'priority' => 'High',
            'description' => '목표 진행이 멈추거나 심각한 어려움을 겪는 단계',
            'entry_conditions' => [
                '진행률 정체 (7일 이상 변화 없음)',
                '반복적인 실패 표현',
                'G1에서 전이 (진행률 < 20% 지속)',
            ],
            'keywords' => ['못 하겠', '포기', '안 돼', '어려워', '힘들어', '싫어'],
            'sub_contexts' => [
                'G2.1' => '일시적 정체',
                'G2.2' => '장기 정체 (≥2주)',
                'G2.3' => '포기 위기',
                'G2.4' => '번아웃',
            ],
        ],
        'G3' => [
            'name' => '목표 재설정 단계',
            'name_en' => 'Goal Reset',
            'icon' => '🔄',
            'priority' => 'Normal',
            'description' => '기존 목표를 수정, 대체, 또는 완전히 새로 설정하는 단계',
            'entry_conditions' => [
                '목표 달성 완료',
                '목표 재설정 요청',
                'G2에서 전이 (목표 재설정 권유 수락)',
            ],
            'keywords' => ['바꾸고 싶', '다시 세우', '새로운 목표', '수정하고 싶'],
            'sub_contexts' => [
                'G3.1' => '목표 달성 후 새 목표',
                'G3.2' => '목표 하향 조정',
                'G3.3' => '목표 상향 조정',
                'G3.4' => '목표 방향 전환',
            ],
        ],
        'CRISIS' => [
            'name' => '위기 개입',
            'name_en' => 'Crisis Intervention',
            'icon' => '🚨',
            'priority' => 'Critical',
            'description' => '즉각적인 개입이 필요한 심각한 정서적/학습적 위기 상황',
            'entry_conditions' => [
                '자해/자살 관련 언급',
                '극심한 정서적 고통',
                '학대/폭력 신호',
            ],
            'keywords' => ['죽고 싶', '사라지고 싶', '끝내고 싶', '살기 싫', '너무 힘들', '못 견디겠'],
            'sub_contexts' => [
                'Level_0' => '생명 위협 (즉시 전문가 연결)',
                'Level_1' => '심각한 위기 (감정 안정화)',
                'Level_2' => '중간 위기 (적극적 경청)',
                'Level_3' => '경미한 위기 (감정 인정)',
            ],
        ],
    ];

    // ============================================================
    // SECTION 2: 페르소나 정의 (personas.md 기반)
    // ============================================================

    /** @var array 상황별 페르소나 정의 */
    const PERSONAS = [
        // G0: 목표 설정 단계 페르소나
        'G0_P1' => [
            'name' => '야심찬 과목표 설정자',
            'name_en' => 'Ambitious Overreacher',
            'icon' => '🚀',
            'context' => 'G0',
            'description' => '매우 높은 목표를 다수 설정, 현실적 제약 고려 부족',
            'speech_patterns' => ['전교 1등', '100점', '완벽하게', '다 해내고 싶어'],
            'risk_factors' => ['초기 실패 의욕 상실', '번아웃 가능성', '자기효능감 저하'],
            'intervention' => [
                'tone' => 'Gentle',
                'pattern' => 'GapAnalysis',
                'strategy' => '중간 마일스톤 제안, 우선순위 설정 유도',
            ],
            'quantum_mapping' => ['S' => 0.4, 'D' => 0.1, 'G' => 0.4, 'A' => 0.1],
        ],
        'G0_P2' => [
            'name' => '모호한 목표 설정자',
            'name_en' => 'Vague Dreamer',
            'icon' => '☁️',
            'context' => 'G0',
            'description' => '추상적이고 불명확한 목표, 측정 가능 기준 부재',
            'speech_patterns' => ['그냥 잘하고 싶어', '어떻게든', '뭔가 달라지면', '좀 더 열심히'],
            'risk_factors' => ['진행 상황 측정 불가', '달성 여부 판단 어려움'],
            'intervention' => [
                'tone' => 'Professional',
                'pattern' => 'GoalSetting',
                'strategy' => 'SMART 기준 안내, 구체화 질문',
            ],
            'quantum_mapping' => ['S' => 0.2, 'D' => 0.3, 'G' => 0.2, 'A' => 0.3],
        ],
        'G0_P3' => [
            'name' => '외부 동기 기반 목표 설정자',
            'name_en' => 'External-Driven',
            'icon' => '👥',
            'context' => 'G0',
            'description' => '부모, 선생님 등 타인의 기대에 의한 목표',
            'speech_patterns' => ['부모님이 하래서', '선생님이 정해주셨어', '다들 이렇게 하니까'],
            'risk_factors' => ['외부 압력 제거 시 포기', '진정한 성취감 부재'],
            'intervention' => [
                'tone' => 'Collaborative',
                'pattern' => 'GoalSetting',
                'strategy' => '내재적 동기 탐색, 개인 가치 연결',
            ],
            'quantum_mapping' => ['S' => 0.2, 'D' => 0.2, 'G' => 0.2, 'A' => 0.4],
        ],
        'G0_P4' => [
            'name' => '현실적 계획자',
            'name_en' => 'Realistic Planner',
            'icon' => '📋',
            'context' => 'G0',
            'description' => '구체적이고 측정 가능한 목표, 현실적 자기 평가',
            'speech_patterns' => ['한 달 안에 80점 이상', '매일 30분씩', '시간이 부족하니까'],
            'risk_factors' => [],
            'intervention' => [
                'tone' => 'Professional',
                'pattern' => 'PlanDesign',
                'strategy' => '기존 계획 정교화, 효율성 향상',
            ],
            'quantum_mapping' => ['S' => 0.2, 'D' => 0.3, 'G' => 0.1, 'A' => 0.4],
        ],
        'G0_P5' => [
            'name' => '두려움 기반 목표 회피자',
            'name_en' => 'Fear-Based Avoider',
            'icon' => '😰',
            'context' => 'G0',
            'description' => '실패에 대한 강한 두려움, 도전 회피 경향',
            'speech_patterns' => ['못할 것 같아', '실패하면 어떡해', '자신 없어'],
            'risk_factors' => ['성장 기회 제한', '낮은 자존감 유지', '학습된 무기력'],
            'intervention' => [
                'tone' => 'Encouraging',
                'pattern' => 'EmotionalSupport',
                'strategy' => '자신감 구축, 작은 성공 경험 제공',
            ],
            'quantum_mapping' => ['S' => 0.1, 'D' => 0.2, 'G' => 0.1, 'A' => 0.6],
        ],
        'G0_P6' => [
            'name' => '전략적 최적화 추구자',
            'name_en' => 'Strategic Optimizer',
            'icon' => '📊',
            'context' => 'G0',
            'description' => '데이터와 분석 중시, 효율성과 최적화 추구',
            'speech_patterns' => ['가장 효율적인 방법', '데이터로 분석', '우선순위', '시간 대비 효과'],
            'risk_factors' => [],
            'intervention' => [
                'tone' => 'Professional',
                'pattern' => 'PlanDesign',
                'strategy' => '분석적 프레임워크 제공, 데이터 기반 전략',
            ],
            'quantum_mapping' => ['S' => 0.3, 'D' => 0.4, 'G' => 0.1, 'A' => 0.2],
        ],

        // G1: 목표 진행 중 페르소나
        'G1_P1' => [
            'name' => '꾸준한 달성자',
            'name_en' => 'Consistent Achiever',
            'icon' => '🏆',
            'context' => 'G1',
            'description' => '높은 목표 달성률 (70% 이상), 일관된 진행 패턴',
            'speech_patterns' => ['이번 목표도 달성했어', '매일 하니까 습관이 됐어', '다음 목표도 도전'],
            'risk_factors' => [],
            'intervention' => [
                'tone' => 'Professional',
                'pattern' => 'SkillBuilding',
                'strategy' => '스트레치 목표 제안, 성장 기회 확대',
            ],
            'quantum_mapping' => ['S' => 0.3, 'D' => 0.3, 'G' => 0.2, 'A' => 0.2],
        ],
        'G1_P2' => [
            'name' => '어려움 속 인내자',
            'name_en' => 'Struggling Perseverer',
            'icon' => '💪',
            'context' => 'G1',
            'description' => '낮은 달성률에도 불구하고 노력 지속',
            'speech_patterns' => ['열심히 하는데 잘 안 돼', '포기하고 싶지 않아', '뭐가 문제인지 모르겠어'],
            'risk_factors' => ['노력 당연시', '방법 개선 필요'],
            'intervention' => [
                'tone' => 'Warm',
                'pattern' => 'EmotionalSupport',
                'strategy' => '노력 인정, 장애물 분석, 방법 개선',
            ],
            'quantum_mapping' => ['S' => 0.2, 'D' => 0.3, 'G' => 0.1, 'A' => 0.4],
        ],
        'G1_P3' => [
            'name' => '일관성 없는 시작자',
            'name_en' => 'Inconsistent Starter',
            'icon' => '🔁',
            'context' => 'G1',
            'description' => '빈번한 시작과 중단 반복, 낮은 일관성',
            'speech_patterns' => ['또 못 했어', '다시 시작할게', '이번엔 정말 할 거야', '까먹었어'],
            'risk_factors' => ['습관 형성 어려움', '자기 효능감 저하'],
            'intervention' => [
                'tone' => 'Gentle',
                'pattern' => 'BehaviorModification',
                'strategy' => '목표 단순화, 습관 시스템 구축',
            ],
            'quantum_mapping' => ['S' => 0.4, 'D' => 0.1, 'G' => 0.4, 'A' => 0.1],
        ],
        'G1_P4' => [
            'name' => '과부하 멀티태스커',
            'name_en' => 'Overloaded Multitasker',
            'icon' => '🤯',
            'context' => 'G1',
            'description' => '8개 이상의 동시 진행 목표, 압도감',
            'speech_patterns' => ['다 하려니까 벅차', '시간이 부족해', '뭐부터 해야 할지 모르겠어'],
            'risk_factors' => ['분산된 집중력', '번아웃 위험'],
            'intervention' => [
                'tone' => 'Calm',
                'pattern' => 'GapAnalysis',
                'strategy' => '우선순위 설정, 목표 축소 제안',
            ],
            'quantum_mapping' => ['S' => 0.5, 'D' => 0.1, 'G' => 0.2, 'A' => 0.2],
        ],
        'G1_P5' => [
            'name' => '완료 직전 목표 보유자',
            'name_en' => 'Near Completion',
            'icon' => '🏁',
            'context' => 'G1',
            'description' => '80% 이상 진행된 목표 보유',
            'speech_patterns' => ['거의 다 했어', '마지막이 어려워', '조금만 더 하면 돼'],
            'risk_factors' => ['마지막 단계 긴장감'],
            'intervention' => [
                'tone' => 'Encouraging',
                'pattern' => 'SkillBuilding',
                'strategy' => '완료 축하 준비, 다음 목표 논의',
            ],
            'quantum_mapping' => ['S' => 0.3, 'D' => 0.2, 'G' => 0.3, 'A' => 0.2],
        ],

        // G2: 정체/위기 상황 페르소나
        'G2_P1' => [
            'name' => '정체 좌절형',
            'name_en' => 'Plateau Frustrated',
            'icon' => '😤',
            'context' => 'G2',
            'description' => '3주 이상 진행 정체, 좌절감',
            'speech_patterns' => ['왜 안 늘어', '제자리야', '답답해', '뭘 해도 안 돼'],
            'risk_factors' => ['동기 상실', '자신감 하락'],
            'intervention' => [
                'tone' => 'Warm',
                'pattern' => 'GapAnalysis',
                'strategy' => '정체 원인 분석, 전략 전환 제안',
            ],
            'quantum_mapping' => ['S' => 0.2, 'D' => 0.3, 'G' => 0.1, 'A' => 0.4],
        ],
        'G2_P2' => [
            'name' => '포기 직전형',
            'name_en' => 'Giving Up',
            'icon' => '🆘',
            'context' => 'G2',
            'description' => '명시적 포기 의사 표현, 즉각적 개입 필요',
            'speech_patterns' => ['포기할래', '더 이상 못하겠어', '소용없어', '그만둘래'],
            'risk_factors' => ['즉각적 개입 필요', '정서적 지원 우선'],
            'intervention' => [
                'tone' => 'Warm',
                'pace' => 'very_slow',
                'pattern' => 'EmotionalSupport',
                'strategy' => '압력 제거, 작은 성공에 집중',
            ],
            'quantum_mapping' => ['S' => 0.1, 'D' => 0.1, 'G' => 0.1, 'A' => 0.7],
        ],
        'G2_P3' => [
            'name' => '비교 고통형',
            'name_en' => 'Comparison Distressed',
            'icon' => '😢',
            'context' => 'G2',
            'description' => '타인과의 비교로 인한 고통, 자존감 저하',
            'speech_patterns' => ['친구는 벌써 했는데', '다른 애들은 다 하는데', '나만 뒤처져'],
            'risk_factors' => ['사회적 비교 집착', '자존감 저하'],
            'intervention' => [
                'tone' => 'Warm',
                'pattern' => 'EmotionalSupport',
                'strategy' => '개인 성장 관점 전환, 비교 중단 유도',
            ],
            'quantum_mapping' => ['S' => 0.1, 'D' => 0.2, 'G' => 0.2, 'A' => 0.5],
        ],
        'G2_P4' => [
            'name' => '외부 장애 직면형',
            'name_en' => 'External Blocker',
            'icon' => '🚧',
            'context' => 'G2',
            'description' => '시간, 환경 등 외부 제약으로 인한 장애',
            'speech_patterns' => ['시간이 없어서', '시험 기간이라', '학교 일정 때문에'],
            'risk_factors' => ['현실적 조정 필요'],
            'intervention' => [
                'tone' => 'Collaborative',
                'pattern' => 'PlanDesign',
                'strategy' => '현실적 일정 조정, 미시 기회 활용',
            ],
            'quantum_mapping' => ['S' => 0.2, 'D' => 0.2, 'G' => 0.1, 'A' => 0.5],
        ],

        // G3: 목표 재설정 페르소나
        'G3_P1' => [
            'name' => '성찰적 조정자',
            'name_en' => 'Reflective Adjuster',
            'icon' => '🪞',
            'context' => 'G3',
            'description' => '자발적 목표 조정 의사, 현실적 자기 평가',
            'speech_patterns' => ['다시 생각해보니', '조정해야 할 것 같아', '너무 무리였어'],
            'risk_factors' => [],
            'intervention' => [
                'tone' => 'Professional',
                'pattern' => 'GoalSetting',
                'strategy' => 'SMART 기준 재적용, 조정 지원',
            ],
            'quantum_mapping' => ['S' => 0.2, 'D' => 0.4, 'G' => 0.1, 'A' => 0.3],
        ],
        'G3_P2' => [
            'name' => '방어적 합리화형',
            'name_en' => 'Defensive Rationalizer',
            'icon' => '🛡️',
            'context' => 'G3',
            'description' => '실패 합리화 경향, 방어 기제 작동',
            'speech_patterns' => ['원래 그렇게 중요한 건 아니었어', '그냥 해본 거였어', '별로 신경 안 썼어'],
            'risk_factors' => ['자기 기만', '성장 기회 상실'],
            'intervention' => [
                'tone' => 'Gentle',
                'pattern' => 'EmotionalSupport',
                'strategy' => '노력 인정 우선, 부드러운 현실 점검',
            ],
            'quantum_mapping' => ['S' => 0.1, 'D' => 0.2, 'G' => 0.2, 'A' => 0.5],
        ],

        // CRISIS: 위기 페르소나
        'CRISIS_P1' => [
            'name' => '목표 불안 위기형',
            'name_en' => 'Goal Anxiety Crisis',
            'icon' => '😰',
            'context' => 'CRISIS',
            'description' => '목표로 인한 극심한 불안, 수면 장애',
            'speech_patterns' => ['목표 생각하면 불안해', '잠이 안 와', '스트레스 받아', '압박감이 너무 커'],
            'risk_factors' => ['즉각적 정서 안정 필요'],
            'intervention' => [
                'tone' => 'Warm',
                'pace' => 'very_slow',
                'pattern' => 'SafetyNet',
                'strategy' => '목표 논의 중단, 정서 안정 우선',
            ],
            'quantum_mapping' => ['S' => 0.1, 'D' => 0.1, 'G' => 0.1, 'A' => 0.7],
        ],
        'CRISIS_P2' => [
            'name' => '번아웃 징후형',
            'name_en' => 'Burnout Signs',
            'icon' => '🔥',
            'context' => 'CRISIS',
            'description' => '신체적, 정서적 탈진, 전반적 의욕 상실',
            'speech_patterns' => ['다 지쳤어', '아무것도 하기 싫어', '다 포기하고 싶어', '의욕이 없어'],
            'risk_factors' => ['장기적 회복 필요', '전문 상담 연계 검토'],
            'intervention' => [
                'tone' => 'Warm',
                'pace' => 'very_slow',
                'pattern' => 'CrisisIntervention',
                'strategy' => '모든 목표 축소/보류, 휴식 권고',
            ],
            'quantum_mapping' => ['S' => 0.05, 'D' => 0.05, 'G' => 0.05, 'A' => 0.85],
        ],
    ];

    // ============================================================
    // SECTION 3: 톤 및 페이스 정의
    // ============================================================

    /** @var array 톤 정의 */
    const TONES = [
        'Warm' => [
            'name' => '따뜻함',
            'icon' => '🌸',
            'description' => '공감적이고 지지적인 어조',
            'characteristics' => ['공감 표현', '정서적 지지', '비판 자제'],
            'example' => '힘들었겠다. 천천히 이야기해볼까요?',
        ],
        'Professional' => [
            'name' => '전문적',
            'icon' => '💼',
            'description' => '객관적이고 분석적인 어조',
            'characteristics' => ['데이터 기반', '논리적 설명', '구조화된 정보'],
            'example' => '현재 달성률은 65%입니다. 다음 단계를 살펴볼까요?',
        ],
        'Encouraging' => [
            'name' => '격려',
            'icon' => '🎉',
            'description' => '칭찬과 동기부여 중심 어조',
            'characteristics' => ['긍정적 피드백', '성취 인정', '도전 격려'],
            'example' => '대단해요! 여기까지 온 것만으로도 훌륭해요!',
        ],
        'Gentle' => [
            'name' => '부드러움',
            'icon' => '🕊️',
            'description' => '조심스럽고 배려하는 어조',
            'characteristics' => ['압박 최소화', '선택권 존중', '부드러운 제안'],
            'example' => '혹시 괜찮다면, 조금 조정해보는 건 어떨까요?',
        ],
        'Collaborative' => [
            'name' => '협력적',
            'icon' => '🤝',
            'description' => '함께 해결해나가는 어조',
            'characteristics' => ['공동 문제해결', '의견 존중', '대안 모색'],
            'example' => '함께 방법을 찾아볼까요? 어떤 생각이 있으세요?',
        ],
        'Calm' => [
            'name' => '차분함',
            'icon' => '🧘',
            'description' => '안정감을 주는 어조',
            'characteristics' => ['평온한 분위기', '침착함', '안심 유도'],
            'example' => '괜찮아요. 하나씩 천천히 정리해봐요.',
        ],
    ];

    /** @var array 페이스 정의 */
    const PACES = [
        'normal' => [
            'name' => '일반',
            'response_length' => 'medium',
            'question_frequency' => 'moderate',
            'wait_time' => 'standard',
        ],
        'slow' => [
            'name' => '느림',
            'response_length' => 'short',
            'question_frequency' => 'low',
            'wait_time' => 'extended',
        ],
        'very_slow' => [
            'name' => '매우 느림',
            'response_length' => 'minimal',
            'question_frequency' => 'very_low',
            'wait_time' => 'very_extended',
        ],
        'fast' => [
            'name' => '빠름',
            'response_length' => 'concise',
            'question_frequency' => 'high',
            'wait_time' => 'short',
        ],
    ];

    // ============================================================
    // SECTION 4: 상황 감지 및 분석
    // ============================================================

    /**
     * 메시지 기반 상황 감지
     *
     * @param string $message 사용자 메시지
     * @param array $sessionData 세션 데이터 (진행률, 목표 수 등)
     * @return array 감지 결과
     */
    public function detectContext(string $message, array $sessionData = []): array
    {
        try {
            $results = [];

            // 1. 위기 상황 우선 검사 (Critical Priority)
            $crisisResult = $this->checkCrisisSignals($message);
            if ($crisisResult['detected']) {
                return [
                    'context' => 'CRISIS',
                    'sub_context' => $crisisResult['level'],
                    'confidence' => $crisisResult['confidence'],
                    'immediate_action' => $crisisResult['immediate'],
                    'matched_keywords' => $crisisResult['matched_keywords'],
                ];
            }

            // 2. 각 상황 코드별 키워드 매칭
            foreach (self::CONTEXT_CODES as $code => $context) {
                if ($code === 'CRISIS') continue; // 이미 검사함

                $matches = $this->matchKeywords($message, $context['keywords'] ?? []);
                if (!empty($matches)) {
                    $results[$code] = [
                        'matches' => $matches,
                        'score' => count($matches),
                        'priority' => $context['priority'],
                    ];
                }
            }

            // 3. 세션 데이터 기반 상황 판단
            $dataBasedContext = $this->analyzeSessionData($sessionData);
            if ($dataBasedContext) {
                if (isset($results[$dataBasedContext['context']])) {
                    $results[$dataBasedContext['context']]['score'] += 2;
                } else {
                    $results[$dataBasedContext['context']] = [
                        'matches' => [],
                        'score' => 2,
                        'priority' => self::CONTEXT_CODES[$dataBasedContext['context']]['priority'] ?? 'Normal',
                        'data_based' => true,
                    ];
                }
            }

            // 4. 최고 점수 상황 선택
            if (empty($results)) {
                return [
                    'context' => 'G1',
                    'sub_context' => 'G1.2',
                    'confidence' => 0.5,
                    'message' => '기본 상황으로 설정됨',
                ];
            }

            // 우선순위 가중치 적용
            $priorityWeights = ['Critical' => 100, 'High' => 50, 'Normal' => 10];
            foreach ($results as $code => &$result) {
                $result['weighted_score'] = $result['score'] + ($priorityWeights[$result['priority']] ?? 0);
            }

            arsort($results);
            $topContext = key($results);
            $topResult = $results[$topContext];

            return [
                'context' => $topContext,
                'sub_context' => $this->determineSubContext($topContext, $sessionData),
                'confidence' => min(0.95, 0.5 + ($topResult['score'] * 0.1)),
                'matched_keywords' => $topResult['matches'] ?? [],
                'all_results' => $results,
            ];

        } catch (Exception $e) {
            error_log("[PersonaContextSimulator] detectContext error at {$this->currentFile}:" . $e->getLine() . " - " . $e->getMessage());
            return [
                'context' => 'G1',
                'sub_context' => 'G1.2',
                'confidence' => 0.5,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * 위기 신호 검사
     */
    private function checkCrisisSignals(string $message): array
    {
        $crisisKeywords = [
            'level_0' => ['죽고 싶', '자살', '자해', '사라지고 싶', '끝내고 싶', '살기 싫'],
            'level_1' => ['못 견디겠', '미치겠', '무너질 것 같', '너무 힘들'],
            'level_2' => ['아무도 없', '혼자', '외로워', '이해 못 해'],
            'level_3' => ['힘들어', '지쳤어', '스트레스', '우울해'],
        ];

        foreach ($crisisKeywords as $level => $keywords) {
            $matches = $this->matchKeywords($message, $keywords);
            if (!empty($matches)) {
                return [
                    'detected' => true,
                    'level' => $level,
                    'confidence' => $level === 'level_0' ? 0.95 : ($level === 'level_1' ? 0.9 : 0.8),
                    'immediate' => in_array($level, ['level_0', 'level_1']),
                    'matched_keywords' => $matches,
                ];
            }
        }

        return ['detected' => false];
    }

    /**
     * 키워드 매칭
     */
    private function matchKeywords(string $message, array $keywords): array
    {
        $matches = [];
        foreach ($keywords as $keyword) {
            if (mb_strpos($message, $keyword) !== false) {
                $matches[] = $keyword;
            }
        }
        return $matches;
    }

    /**
     * 세션 데이터 분석
     */
    private function analyzeSessionData(array $sessionData): ?array
    {
        $progressRate = $sessionData['goal_progress_rate'] ?? null;
        $goalCount = $sessionData['active_goal_count'] ?? null;

        if ($goalCount === 0) {
            return ['context' => 'G0', 'reason' => '목표 없음'];
        }

        if ($goalCount > 5) {
            return ['context' => 'G1', 'sub' => 'G1.4', 'reason' => '목표 과부하'];
        }

        if ($progressRate !== null) {
            if ($progressRate < 20) {
                return ['context' => 'G2', 'reason' => '진행률 매우 낮음'];
            }
            if ($progressRate >= 80) {
                return ['context' => 'G1', 'sub' => 'G1.1', 'reason' => '진행률 높음'];
            }
        }

        return null;
    }

    /**
     * 하위 상황 결정
     */
    private function determineSubContext(string $context, array $sessionData): string
    {
        $progressRate = $sessionData['goal_progress_rate'] ?? 50;
        $stagnationDays = $sessionData['stagnation_days'] ?? 0;

        switch ($context) {
            case 'G0':
                $goalHistory = $sessionData['goals_history_count'] ?? 0;
                return $goalHistory === 0 ? 'G0.1' : 'G0.2';

            case 'G1':
                if ($progressRate >= 90) return 'G1.5';
                if ($progressRate >= 80) return 'G1.1';
                if ($progressRate >= 50) return 'G1.2';
                if ($progressRate >= 20) return 'G1.3';
                return 'G1.4';

            case 'G2':
                if ($stagnationDays >= 14) return 'G2.2';
                if ($sessionData['shows_burnout'] ?? false) return 'G2.4';
                return 'G2.1';

            case 'G3':
                $previousCompleted = $sessionData['previous_goal_completed'] ?? false;
                return $previousCompleted ? 'G3.1' : 'G3.2';

            default:
                return $context . '.1';
        }
    }

    // ============================================================
    // SECTION 5: 페르소나 식별 (rules.yaml 기반)
    // ============================================================

    /**
     * rules.yaml 기반 페르소나 식별 규칙
     * 우선순위가 높을수록 먼저 평가됨
     */
    const IDENTIFICATION_RULES = [
        // G0: 목표 설정 단계 규칙
        'PI_G0_001' => [
            'target_persona' => 'G0_P1',
            'priority' => 92,
            'conditions' => [
                'situation' => 'G0',
                'keywords' => ['전교 1등', '올백', '100점', '전부 다', '완벽하게', '최고'],
                'goal_count_gte' => 10,
                'goal_difficulty_ratio_gte' => 0.8,
            ],
            'confidence' => 0.88,
        ],
        'PI_G0_002' => [
            'target_persona' => 'G0_P2',
            'priority' => 90,
            'conditions' => [
                'situation' => 'G0',
                'keywords' => ['그냥 잘하고 싶어', '어떻게든', '뭔가', '대충', '좀 나아지게'],
                'goal_specificity_lte' => 'low',
            ],
            'confidence' => 0.86,
        ],
        'PI_G0_003' => [
            'target_persona' => 'G0_P3',
            'priority' => 88,
            'conditions' => [
                'situation' => 'G0',
                'keywords' => ['부모님이', '선생님이', '친구가', '해야 해서', '시켜서', '다들'],
            ],
            'confidence' => 0.85,
        ],
        'PI_G0_005' => [
            'target_persona' => 'G0_P5',
            'priority' => 93,
            'conditions' => [
                'situation' => 'G0',
                'keywords' => ['못할 것 같', '무서워', '실패하면', '안 되면', '자신 없어'],
            ],
            'confidence' => 0.90,
        ],
        'PI_G0_006' => [
            'target_persona' => 'G0_P6',
            'priority' => 84,
            'conditions' => [
                'situation' => 'G0',
                'keywords' => ['효율적', '최적화', '우선순위', '전략', '분석', '데이터'],
            ],
            'confidence' => 0.86,
        ],

        // G1: 목표 진행 중 규칙
        'PI_G1_001' => [
            'target_persona' => 'G1_P1',
            'priority' => 85,
            'conditions' => [
                'situation' => 'G1',
                'goal_progress_rate_gte' => 70,
                'consistency_score_gte' => 'high',
            ],
            'confidence' => 0.88,
        ],
        'PI_G1_002' => [
            'target_persona' => 'G1_P2',
            'priority' => 91,
            'conditions' => [
                'situation' => 'G1',
                'goal_progress_rate_lt' => 50,
                'keywords' => ['열심히 하는데', '노력하는데', '계속 해보는데', '포기 안 해'],
            ],
            'confidence' => 0.89,
        ],
        'PI_G1_003' => [
            'target_persona' => 'G1_P3',
            'priority' => 88,
            'conditions' => [
                'situation' => 'G1',
                'keywords' => ['또 못 했어', '까먹었어', '다시 시작', '새로 해볼게'],
                'consistency_score_lte' => 'low',
            ],
            'confidence' => 0.87,
        ],
        'PI_G1_004' => [
            'target_persona' => 'G1_P4',
            'priority' => 94,
            'conditions' => [
                'situation' => 'G1',
                'keywords' => ['너무 많아', '감당이 안', '다 못 해', '시간이 없어', '벅차'],
                'active_goal_count_gte' => 8,
            ],
            'confidence' => 0.92,
        ],
        'PI_G1_005' => [
            'target_persona' => 'G1_P5',
            'priority' => 83,
            'conditions' => [
                'situation' => 'G1',
                'goal_progress_rate_gte' => 80,
            ],
            'confidence' => 0.85,
        ],

        // G2: 정체/위기 규칙
        'PI_G2_001' => [
            'target_persona' => 'G2_P1',
            'priority' => 90,
            'conditions' => [
                'situation' => 'G2',
                'keywords' => ['왜 안 늘어', '제자리', '변화가 없어', '답답해', '막혀'],
                'stagnation_weeks_gte' => 3,
            ],
            'confidence' => 0.89,
        ],
        'PI_G2_002' => [
            'target_persona' => 'G2_P2',
            'priority' => 97,
            'conditions' => [
                'situation' => 'G2',
                'keywords' => ['포기할래', '안 할래', '그만둘래', '소용없어', '못하겠어'],
            ],
            'confidence' => 0.95,
        ],
        'PI_G2_003' => [
            'target_persona' => 'G2_P3',
            'priority' => 89,
            'conditions' => [
                'situation' => 'G2',
                'keywords' => ['친구는 벌써', '다른 애들', '나만 못해', '뒤처져', '비교'],
            ],
            'confidence' => 0.88,
        ],
        'PI_G2_004' => [
            'target_persona' => 'G2_P4',
            'priority' => 86,
            'conditions' => [
                'situation' => 'G2',
                'keywords' => ['시간이 없어서', '학교 때문에', '시험 기간이라', '바빠서', '상황이'],
            ],
            'confidence' => 0.84,
        ],

        // G3: 목표 재설정 규칙
        'PI_G3_001' => [
            'target_persona' => 'G3_P1',
            'priority' => 85,
            'conditions' => [
                'situation' => 'G3',
                'keywords' => ['다시 생각해보니', '조정해야', '너무 무리였어', '현실적으로'],
            ],
            'confidence' => 0.87,
        ],
        'PI_G3_002' => [
            'target_persona' => 'G3_P2',
            'priority' => 88,
            'conditions' => [
                'situation' => 'G3',
                'keywords' => ['원래 목표가', '그건 아니었어', '그냥 해본', '별로 중요하지'],
            ],
            'confidence' => 0.86,
        ],

        // CRISIS: 위기 규칙
        'PI_CRISIS_001' => [
            'target_persona' => 'CRISIS_P1',
            'priority' => 100,
            'conditions' => [
                'situation' => 'CRISIS',
                'keywords' => ['압박감', '스트레스', '잠이 안 와', '불안해', '걱정돼', '무서워'],
            ],
            'confidence' => 0.96,
        ],
        'PI_CRISIS_002' => [
            'target_persona' => 'CRISIS_P2',
            'priority' => 100,
            'conditions' => [
                'situation' => 'CRISIS',
                'keywords' => ['지쳤어', '힘들어', '다 그만두고 싶어', '의욕이 없어', '무기력'],
            ],
            'confidence' => 0.97,
        ],
    ];

    /**
     * 상황과 메시지 기반 페르소나 식별 (rules.yaml 기반)
     *
     * @param string $context 상황 코드
     * @param string $message 사용자 메시지
     * @param array $sessionData 세션 데이터
     * @return array 식별된 페르소나들 (확률순 정렬)
     */
    public function identifyPersonas(string $context, string $message, array $sessionData = []): array
    {
        try {
            $results = [];
            $matchedRules = [];

            // 1. 규칙 기반 매칭 (rules.yaml 반영)
            foreach (self::IDENTIFICATION_RULES as $ruleId => $rule) {
                if ($rule['conditions']['situation'] !== $context) {
                    continue;
                }

                $ruleScore = $this->evaluateRule($rule, $message, $sessionData);
                if ($ruleScore > 0) {
                    $matchedRules[$rule['target_persona']] = [
                        'rule_id' => $ruleId,
                        'priority' => $rule['priority'],
                        'confidence' => $rule['confidence'],
                        'score' => $ruleScore,
                    ];
                }
            }

            // 2. 해당 상황의 모든 페르소나에 대해 점수 계산
            $contextPersonas = array_filter(self::PERSONAS, function ($p) use ($context) {
                return $p['context'] === $context;
            });

            foreach ($contextPersonas as $id => $persona) {
                // 규칙 매칭 점수
                $ruleMatch = $matchedRules[$id] ?? null;
                $ruleScore = $ruleMatch ? ($ruleMatch['priority'] / 100) * $ruleMatch['confidence'] * $ruleMatch['score'] : 0;

                // 발화 패턴 점수
                $patternScore = $this->calculatePatternScore($persona, $message);

                // 세션 데이터 점수
                $dataScore = $this->calculateDataScore($persona, $sessionData);

                // 종합 점수
                $totalScore = 0.1 + ($ruleScore * 0.5) + ($patternScore * 0.3) + ($dataScore * 0.2);

                $results[] = [
                    'id' => $id,
                    'persona' => $persona,
                    'score' => $totalScore,
                    'probability' => 0,
                    'matched_rule' => $ruleMatch['rule_id'] ?? null,
                    'rule_confidence' => $ruleMatch['confidence'] ?? null,
                    'pattern_score' => $patternScore,
                    'data_score' => $dataScore,
                ];
            }

            // 점수순 정렬
            usort($results, function ($a, $b) {
                return $b['score'] <=> $a['score'];
            });

            // 확률 정규화
            $totalScore = array_sum(array_column($results, 'score'));
            if ($totalScore > 0) {
                foreach ($results as &$result) {
                    $result['probability'] = round($result['score'] / $totalScore, 4);
                }
            }

            return $results;

        } catch (Exception $e) {
            error_log("[PersonaContextSimulator] identifyPersonas error at {$this->currentFile}:" . $e->getLine());
            return [];
        }
    }

    /**
     * 규칙 조건 평가
     */
    private function evaluateRule(array $rule, string $message, array $sessionData): float
    {
        $conditions = $rule['conditions'];
        $matchCount = 0;
        $totalConditions = 0;

        // 키워드 조건
        if (isset($conditions['keywords'])) {
            $totalConditions++;
            $matches = $this->matchKeywords($message, $conditions['keywords']);
            if (!empty($matches)) {
                $matchCount += min(count($matches) / 2, 1); // 최대 1점
            }
        }

        // 목표 진행률 조건
        if (isset($conditions['goal_progress_rate_gte'])) {
            $totalConditions++;
            $rate = $sessionData['goal_progress_rate'] ?? 0;
            if ($rate >= $conditions['goal_progress_rate_gte']) {
                $matchCount++;
            }
        }

        if (isset($conditions['goal_progress_rate_lt'])) {
            $totalConditions++;
            $rate = $sessionData['goal_progress_rate'] ?? 0;
            if ($rate < $conditions['goal_progress_rate_lt']) {
                $matchCount++;
            }
        }

        // 활성 목표 수 조건
        if (isset($conditions['active_goal_count_gte'])) {
            $totalConditions++;
            $count = $sessionData['active_goal_count'] ?? 0;
            if ($count >= $conditions['active_goal_count_gte']) {
                $matchCount++;
            }
        }

        if (isset($conditions['goal_count_gte'])) {
            $totalConditions++;
            $count = $sessionData['active_goal_count'] ?? 0;
            if ($count >= $conditions['goal_count_gte']) {
                $matchCount++;
            }
        }

        // 정체 주간 조건
        if (isset($conditions['stagnation_weeks_gte'])) {
            $totalConditions++;
            $days = $sessionData['stagnation_days'] ?? 0;
            $weeks = $days / 7;
            if ($weeks >= $conditions['stagnation_weeks_gte']) {
                $matchCount++;
            }
        }

        // 일관성 점수 조건
        if (isset($conditions['consistency_score_gte'])) {
            $totalConditions++;
            $consistency = $sessionData['consistency_score'] ?? 'medium';
            $levels = ['low' => 1, 'medium' => 2, 'high' => 3];
            if (($levels[$consistency] ?? 2) >= ($levels[$conditions['consistency_score_gte']] ?? 2)) {
                $matchCount++;
            }
        }

        if (isset($conditions['consistency_score_lte'])) {
            $totalConditions++;
            $consistency = $sessionData['consistency_score'] ?? 'medium';
            $levels = ['low' => 1, 'medium' => 2, 'high' => 3];
            if (($levels[$consistency] ?? 2) <= ($levels[$conditions['consistency_score_lte']] ?? 2)) {
                $matchCount++;
            }
        }

        // 목표 구체성 조건
        if (isset($conditions['goal_specificity_lte'])) {
            $totalConditions++;
            $specificity = $sessionData['goal_specificity'] ?? 'medium';
            $levels = ['low' => 1, 'medium' => 2, 'high' => 3];
            if (($levels[$specificity] ?? 2) <= ($levels[$conditions['goal_specificity_lte']] ?? 2)) {
                $matchCount++;
            }
        }

        // 조건이 없으면 0 반환
        if ($totalConditions === 0) {
            return 0;
        }

        // 매칭 비율 반환
        return $matchCount / $totalConditions;
    }

    /**
     * 발화 패턴 점수 계산
     */
    private function calculatePatternScore(array $persona, string $message): float
    {
        $patterns = $persona['speech_patterns'] ?? [];
        if (empty($patterns)) {
            return 0;
        }

        $matches = $this->matchKeywords($message, $patterns);
        return min(count($matches) / count($patterns), 1.0);
    }

    /**
     * 세션 데이터 기반 점수 계산
     */
    private function calculateDataScore(array $persona, array $sessionData): float
    {
        $score = 0;

        // 리스크 요인이 있고 부정적 감정 상태인 경우
        if (!empty($persona['risk_factors'])) {
            $emotion = $sessionData['emotional_state'] ?? 'neutral';
            if (in_array($emotion, ['negative', 'overwhelmed', 'frustrated'])) {
                $score += 0.3;
            }
        }

        // 개입 톤이 'Warm'이고 스트레스 상태인 경우
        $tone = $persona['intervention']['tone'] ?? '';
        if ($tone === 'Warm' && ($sessionData['stress_level'] ?? 0) > 0.6) {
            $score += 0.2;
        }

        // 진행률에 따른 가중치
        $progressRate = $sessionData['goal_progress_rate'] ?? 50;
        $context = $persona['context'];

        if ($context === 'G1' && $progressRate >= 80) {
            // 높은 진행률 → G1_P1, G1_P5 선호
            if (in_array($persona['name_en'] ?? '', ['Consistent Achiever', 'Near Completion'])) {
                $score += 0.3;
            }
        }

        if ($context === 'G2' && $progressRate < 20) {
            // 낮은 진행률 → G2_P2 (포기 직전) 가능성 증가
            if (($persona['name_en'] ?? '') === 'Giving Up') {
                $score += 0.4;
            }
        }

        return min($score, 1.0);
    }

    // ============================================================
    // SECTION 6: 양자 모델링 통합
    // ============================================================

    /**
     * 페르소나를 양자 상태 벡터로 변환
     *
     * @param array $identifiedPersonas 식별된 페르소나들
     * @return array 양자 상태 벡터 [S, D, G, A]
     */
    public function convertToQuantumState(array $identifiedPersonas): array
    {
        $stateVector = ['S' => 0, 'D' => 0, 'G' => 0, 'A' => 0];

        foreach ($identifiedPersonas as $result) {
            $mapping = $result['persona']['quantum_mapping'] ?? [];
            $prob = $result['probability'];

            foreach ($mapping as $key => $value) {
                $stateVector[$key] += $value * $prob;
            }
        }

        // 정규화
        $sum = 0;
        foreach ($stateVector as $v) {
            $sum += $v * $v;
        }
        $norm = sqrt($sum);
        if ($norm > 0) {
            foreach ($stateVector as $key => $v) {
                $stateVector[$key] = round($v / $norm, 4);
            }
        }

        return $stateVector;
    }

    // ============================================================
    // SECTION 7: 추천 생성
    // ============================================================

    /**
     * 종합 추천 생성
     *
     * @param string $context 상황 코드
     * @param array $dominantPersona 지배적 페르소나
     * @param array $dynamics 학습 역학 데이터
     * @return array 추천 사항
     */
    public function generateRecommendation(string $context, array $dominantPersona, array $dynamics = []): array
    {
        $intervention = $dominantPersona['intervention'] ?? [];
        $tone = $intervention['tone'] ?? 'Professional';
        $pace = $intervention['pace'] ?? 'normal';
        $pattern = $intervention['pattern'] ?? 'General';
        $strategy = $intervention['strategy'] ?? '';

        $urgency = self::CONTEXT_CODES[$context]['priority'] ?? 'Normal';
        if ($context === 'CRISIS' || ($dynamics['should_intervene'] ?? false)) {
            $urgency = 'Critical';
        }

        $toneInfo = self::TONES[$tone] ?? [];
        $paceInfo = self::PACES[$pace] ?? [];

        return [
            'context' => [
                'code' => $context,
                'name' => self::CONTEXT_CODES[$context]['name'] ?? $context,
                'icon' => self::CONTEXT_CODES[$context]['icon'] ?? '📌',
            ],
            'persona' => [
                'name' => $dominantPersona['name'] ?? 'Unknown',
                'icon' => $dominantPersona['icon'] ?? '👤',
            ],
            'intervention' => [
                'pattern' => $pattern,
                'strategy' => $strategy,
            ],
            'tone' => [
                'type' => $tone,
                'name' => $toneInfo['name'] ?? $tone,
                'icon' => $toneInfo['icon'] ?? '💬',
                'description' => $toneInfo['description'] ?? '',
                'example' => $toneInfo['example'] ?? '',
            ],
            'pace' => [
                'type' => $pace,
                'name' => $paceInfo['name'] ?? $pace,
            ],
            'urgency' => $urgency,
            'actions' => $this->generateActions($context, $dominantPersona, $dynamics),
        ];
    }

    /**
     * 구체적 행동 지침 생성
     */
    private function generateActions(string $context, array $persona, array $dynamics): array
    {
        $actions = [];

        // 위기 상황 특별 조치
        if ($context === 'CRISIS') {
            $actions[] = '🚨 목표 관련 논의 즉시 중단';
            $actions[] = '❤️ 정서적 안정을 최우선으로 합니다';
            $actions[] = '📞 필요시 전문 상담 연계 (1393, 1577-0199)';
            return $actions;
        }

        // 페르소나 기반 행동
        $intervention = $persona['intervention'] ?? [];
        if ($intervention['strategy'] ?? '') {
            $actions[] = "📋 " . $intervention['strategy'];
        }

        // 역학 기반 행동
        if ($dynamics['should_intervene'] ?? false) {
            $actions[] = "⏰ 골든 타임 임박 - {$dynamics['golden_time']}초 내 개입 권장";
        }

        if (($dynamics['backfire'] ?? 0) > 0.7) {
            $actions[] = '⚠️ 역효과 위험 높음 - 접근 방식 전환 고려';
        }

        // 기본 행동
        $toneActions = [
            'Warm' => '🌸 공감하며 경청하기',
            'Professional' => '📊 데이터 기반 분석 제공하기',
            'Encouraging' => '🎉 작은 성취도 축하하기',
            'Gentle' => '🕊️ 선택권을 존중하며 제안하기',
            'Calm' => '🧘 안정감 있는 분위기 유지하기',
        ];

        $tone = $intervention['tone'] ?? 'Professional';
        if (isset($toneActions[$tone])) {
            $actions[] = $toneActions[$tone];
        }

        return $actions;
    }

    // ============================================================
    // SECTION 8: 유틸리티
    // ============================================================

    /**
     * 모든 상황 코드 반환
     */
    public function getAllContextCodes(): array
    {
        return self::CONTEXT_CODES;
    }

    /**
     * 모든 페르소나 반환
     */
    public function getAllPersonas(): array
    {
        return self::PERSONAS;
    }

    /**
     * 특정 상황의 페르소나들 반환
     */
    public function getPersonasByContext(string $context): array
    {
        return array_filter(self::PERSONAS, function ($p) use ($context) {
            return $p['context'] === $context;
        });
    }

    /**
     * 모든 톤 반환
     */
    public function getAllTones(): array
    {
        return self::TONES;
    }

    /**
     * 전체 시뮬레이션 실행
     */
    public function runSimulation(string $message, array $sessionData = []): array
    {
        // 1. 상황 감지
        $contextResult = $this->detectContext($message, $sessionData);
        $context = $contextResult['context'];

        // 2. 페르소나 식별
        $personas = $this->identifyPersonas($context, $message, $sessionData);
        $dominantPersona = $personas[0]['persona'] ?? [];

        // 3. 양자 상태 변환
        $quantumState = $this->convertToQuantumState($personas);

        // 4. 추천 생성
        $recommendation = $this->generateRecommendation($context, $dominantPersona);

        return [
            'context' => $contextResult,
            'personas' => $personas,
            'dominant_persona' => $dominantPersona,
            'quantum_state' => $quantumState,
            'recommendation' => $recommendation,
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }
}

/**
 * 관련 파일:
 * - rules.yaml: 페르소나 식별 및 응답 생성 규칙
 * - personas.md: 페르소나 상세 정의
 * - contextlist.md: 상황 코드 정의
 *
 * 파일 위치:
 * /mnt/c/1 Project/augmented_teacher/alt42/orchestration/agents/agent04_inspect_weakpoints/quantum_modeling/PersonaContextSimulator.php
 */

