<?php
/**
 * 개입 활동 매니저
 * AlphaTutor42 개입 시스템 관리
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 * @version    1.0
 */

require_once(__DIR__ . '/db_manager.php');

class InterventionManager {
    private $dbManager;
    
    public function __construct() {
        $this->dbManager = new DBManager();
        $this->initializeInterventions();
    }
    
    /**
     * 개입 활동 초기화
     */
    private function initializeInterventions() {
        $db = $this->dbManager->getDB();
        
        // 개입 활동 테이블 생성
        if (!$db->tableExists('intervention_activities')) {
            $db->createTable('intervention_activities', [
                'activity_id' => 'string',
                'category' => 'string',
                'name' => 'string',
                'description' => 'string',
                'trigger_signals' => 'array',
                'persona_mapping' => 'array',
                'metadata' => 'object'
            ]);
            $db->createIndex('intervention_activities', 'activity_id');
            $db->createIndex('intervention_activities', 'category');
            
            // 기본 개입 활동 데이터 로드
            $this->loadDefaultInterventions();
        }
    }
    
    /**
     * 기본 개입 활동 데이터 로드
     */
    private function loadDefaultInterventions() {
        $interventions = $this->getDefaultInterventions();
        
        foreach ($interventions as $intervention) {
            // 이미 존재하는지 확인
            $existing = $this->dbManager->getDB()->findAll('intervention_activities', [
                'activity_id' => $intervention['activity_id']
            ]);
            
            if (empty($existing)) {
                $this->dbManager->getDB()->insert('intervention_activities', $intervention);
            }
        }
    }
    
    /**
     * 기본 개입 활동 데이터 정의
     */
    private function getDefaultInterventions() {
        return [
            // 1. 멈춤/대기 (Pause & Wait) — 5개
            [
                'activity_id' => 'INT_1_1',
                'category' => 'pause_wait',
                'name' => '인지 부하 대기',
                'description' => '설명을 멈추고 3~5초 침묵, 처리 시간 확보',
                'trigger_signals' => ['눈 깜빡임 증가', '시선 고정', '멍한 표정'],
                'persona_mapping' => ['P001', 'P005', 'P009'],
                'metadata' => [
                    'duration' => '3-5초',
                    'priority' => 1
                ]
            ],
            [
                'activity_id' => 'INT_1_2',
                'category' => 'pause_wait',
                'name' => '필기 동기화 대기',
                'description' => '학생이 적을 때까지 말을 멈추고 기다림',
                'trigger_signals' => ['고개 숙임', '펜 움직임', '화면/종이 응시'],
                'persona_mapping' => ['P002', 'P008'],
                'metadata' => [
                    'duration' => '필기 완료까지',
                    'priority' => 2
                ]
            ],
            [
                'activity_id' => 'INT_1_3',
                'category' => 'pause_wait',
                'name' => '사고 여백 제공',
                'description' => '"한번 생각해봐" 후 10초 이상 기다림',
                'trigger_signals' => ['질문 직후', '어려운 개념 제시 직후'],
                'persona_mapping' => ['P001', 'P006', 'P012'],
                'metadata' => [
                    'duration' => '10초 이상',
                    'priority' => 1
                ]
            ],
            [
                'activity_id' => 'INT_1_4',
                'category' => 'pause_wait',
                'name' => '감정 진정 대기',
                'description' => '좌절/혼란 시 다그치지 않고 잠시 쉼',
                'trigger_signals' => ['한숨', '펜 내려놓음', '고개 떨굼'],
                'persona_mapping' => ['P003', 'P011'],
                'metadata' => [
                    'duration' => '5-10초',
                    'priority' => 1
                ]
            ],
            [
                'activity_id' => 'INT_1_5',
                'category' => 'pause_wait',
                'name' => '자기 수정 대기',
                'description' => '학생이 스스로 오류 인식할 시간 제공',
                'trigger_signals' => ['말하다 멈춤', '"아 잠깐..."', '표정 변화'],
                'persona_mapping' => ['P004', 'P012'],
                'metadata' => [
                    'duration' => '5-10초',
                    'priority' => 2
                ]
            ],
            
            // 2. 재설명 (Repeat & Rephrase) — 6개
            [
                'activity_id' => 'INT_2_1',
                'category' => 'repeat_rephrase',
                'name' => '동일 반복',
                'description' => '같은 내용을 천천히, 또박또박 다시',
                'trigger_signals' => ['"네?"', '"다시요?"', '되묻기'],
                'persona_mapping' => ['P002', 'P010'],
                'metadata' => [
                    'speed' => '천천히',
                    'priority' => 2
                ]
            ],
            [
                'activity_id' => 'INT_2_2',
                'category' => 'repeat_rephrase',
                'name' => '강조점 이동 반복',
                'description' => '같은 문장에서 강조 위치를 바꿔 반복',
                'trigger_signals' => ['부분적 이해 표현', '"앞부분은 알겠는데..."'],
                'persona_mapping' => ['P005', 'P009'],
                'metadata' => [
                    'method' => '강조점 변경',
                    'priority' => 2
                ]
            ],
            [
                'activity_id' => 'INT_2_3',
                'category' => 'repeat_rephrase',
                'name' => '단계 분해',
                'description' => '한 덩어리를 2~3개 미니 스텝으로 쪼갬',
                'trigger_signals' => ['복합 과정에서 중간에 막힘'],
                'persona_mapping' => ['P001', 'P005', 'P009'],
                'metadata' => [
                    'steps' => '2-3개',
                    'priority' => 1
                ]
            ],
            [
                'activity_id' => 'INT_2_4',
                'category' => 'repeat_rephrase',
                'name' => '역순 재구성',
                'description' => '결론 → 중간 → 시작 순으로 거꾸로 설명',
                'trigger_signals' => ['"왜 이렇게 되는지 모르겠어요"'],
                'persona_mapping' => ['P006', 'P012'],
                'metadata' => [
                    'order' => '역순',
                    'priority' => 3
                ]
            ],
            [
                'activity_id' => 'INT_2_5',
                'category' => 'repeat_rephrase',
                'name' => '연결고리 명시',
                'description' => '"A이기 때문에 B, B이기 때문에 C" 인과 강조',
                'trigger_signals' => ['단계는 따라오나 연결을 못 느낌'],
                'persona_mapping' => ['P006', 'P007'],
                'metadata' => [
                    'focus' => '인과관계',
                    'priority' => 2
                ]
            ],
            [
                'activity_id' => 'INT_2_6',
                'category' => 'repeat_rephrase',
                'name' => '요약 압축',
                'description' => '긴 설명을 한 문장으로 핵심만 재진술',
                'trigger_signals' => ['정보 과다로 혼란', '"그래서 뭐가 중요한 거예요?"'],
                'persona_mapping' => ['P004', 'P007'],
                'metadata' => [
                    'format' => '한 문장',
                    'priority' => 2
                ]
            ],
            
            // 3. 전환 설명 (Alternative Explanation) — 7개
            [
                'activity_id' => 'INT_3_1',
                'category' => 'alternative_explanation',
                'name' => '일상 비유',
                'description' => '추상 개념을 일상 경험에 빗대어 설명',
                'trigger_signals' => ['수학 용어에서 막힘', '개념 자체 이해 불가'],
                'persona_mapping' => ['P009', 'P011'],
                'metadata' => [
                    'method' => '비유',
                    'priority' => 1
                ]
            ],
            [
                'activity_id' => 'INT_3_2',
                'category' => 'alternative_explanation',
                'name' => '시각화 전환',
                'description' => '말 → 그림/도표/그래프로 표현 방식 변경',
                'trigger_signals' => ['언어적 설명에 반응 없음', '청각 처리 한계'],
                'persona_mapping' => ['P005', 'P009'],
                'metadata' => [
                    'method' => '시각화',
                    'priority' => 1
                ]
            ],
            [
                'activity_id' => 'INT_3_3',
                'category' => 'alternative_explanation',
                'name' => '구체적 수 대입',
                'description' => '문자식을 특정 숫자로 바꿔 계산 흐름 시연',
                'trigger_signals' => ['변수/문자에 대한 두려움', '"x가 뭔데요"'],
                'persona_mapping' => ['P001', 'P009'],
                'metadata' => [
                    'method' => '수치 대입',
                    'priority' => 1
                ]
            ],
            [
                'activity_id' => 'INT_3_4',
                'category' => 'alternative_explanation',
                'name' => '극단적 예시',
                'description' => '0, 1, 무한대 등 극단값으로 직관 형성',
                'trigger_signals' => ['일반적 설명으로 감 못 잡음'],
                'persona_mapping' => ['P006', 'P012'],
                'metadata' => [
                    'method' => '극단값',
                    'priority' => 3
                ]
            ],
            [
                'activity_id' => 'INT_3_5',
                'category' => 'alternative_explanation',
                'name' => '반례 제시',
                'description' => '"만약 이렇게 하면 왜 안 되는지 볼까?"',
                'trigger_signals' => ['잘못된 방법을 확신함', '오개념 고착'],
                'persona_mapping' => ['P004', 'P008'],
                'metadata' => [
                    'method' => '반례',
                    'priority' => 2
                ]
            ],
            [
                'activity_id' => 'INT_3_6',
                'category' => 'alternative_explanation',
                'name' => '학생 언어 번역',
                'description' => '학생이 쓰는 표현/용어로 재설명',
                'trigger_signals' => ['교과서 용어에 거부감', '자기 말로 표현 시도'],
                'persona_mapping' => ['P009', 'P011'],
                'metadata' => [
                    'method' => '언어 번역',
                    'priority' => 1
                ]
            ],
            [
                'activity_id' => 'INT_3_7',
                'category' => 'alternative_explanation',
                'name' => '신체/동작 비유',
                'description' => '손동작, 움직임으로 개념 체화',
                'trigger_signals' => ['정적 설명에 집중 못함', '운동감각형 학습자'],
                'persona_mapping' => ['P005', 'P010'],
                'metadata' => [
                    'method' => '신체 동작',
                    'priority' => 2
                ]
            ],
            
            // 4. 강조/주의환기 (Emphasis & Alerting) — 5개
            [
                'activity_id' => 'INT_4_1',
                'category' => 'emphasis_alerting',
                'name' => '핵심 반복 강조',
                'description' => '"이게 제일 중요해" 동일 포인트 2~3회',
                'trigger_signals' => ['핵심을 지나치고 지엽적인 것에 집중'],
                'persona_mapping' => ['P004', 'P005'],
                'metadata' => [
                    'repetition' => '2-3회',
                    'priority' => 2
                ]
            ],
            [
                'activity_id' => 'INT_4_2',
                'category' => 'emphasis_alerting',
                'name' => '대비 강조',
                'description' => '"A가 아니라 B야" 오개념과 정개념 병렬',
                'trigger_signals' => ['흔한 오류 패턴 감지', '헷갈리는 개념'],
                'persona_mapping' => ['P004', 'P008'],
                'metadata' => [
                    'method' => '대비',
                    'priority' => 2
                ]
            ],
            [
                'activity_id' => 'INT_4_3',
                'category' => 'emphasis_alerting',
                'name' => '톤/속도 변화',
                'description' => '갑자기 천천히, 또는 높은 톤으로 전환',
                'trigger_signals' => ['주의력 저하', '멍한 상태', '습관적 고개 끄덕임'],
                'persona_mapping' => ['P005', 'P011'],
                'metadata' => [
                    'method' => '톤/속도',
                    'priority' => 1
                ]
            ],
            [
                'activity_id' => 'INT_4_4',
                'category' => 'emphasis_alerting',
                'name' => '시각적 마킹',
                'description' => '밑줄, 동그라미, 색깔로 주의 집중 유도',
                'trigger_signals' => ['시각 자료에서 핵심 못 찾음'],
                'persona_mapping' => ['P005', 'P009'],
                'metadata' => [
                    'method' => '시각 마킹',
                    'priority' => 2
                ]
            ],
            [
                'activity_id' => 'INT_4_5',
                'category' => 'emphasis_alerting',
                'name' => '예고 신호',
                'description' => '"지금부터 말하는 거 시험에 나와" 경고',
                'trigger_signals' => ['전반적 이완 상태', '중요도 인식 부족'],
                'persona_mapping' => ['P007', 'P011'],
                'metadata' => [
                    'method' => '예고',
                    'priority' => 3
                ]
            ],
            
            // 5. 질문/탐색 (Questioning & Probing) — 7개
            [
                'activity_id' => 'INT_5_1',
                'category' => 'questioning_probing',
                'name' => '확인 질문',
                'description' => '"여기까지 이해됐어?" 단순 예/아니오',
                'trigger_signals' => ['설명 구간 완료 시점', '표정 불확실'],
                'persona_mapping' => ['P002', 'P010'],
                'metadata' => [
                    'type' => '확인',
                    'priority' => 2
                ]
            ],
            [
                'activity_id' => 'INT_5_2',
                'category' => 'questioning_probing',
                'name' => '예측 질문',
                'description' => '"다음엔 뭘 해야 할 것 같아?"',
                'trigger_signals' => ['수동적 청취 지속', '능동 사고 유도 필요'],
                'persona_mapping' => ['P010', 'P011'],
                'metadata' => [
                    'type' => '예측',
                    'priority' => 2
                ]
            ],
            [
                'activity_id' => 'INT_5_3',
                'category' => 'questioning_probing',
                'name' => '역질문',
                'description' => '"왜 그렇게 생각했어?" 사고과정 탐색',
                'trigger_signals' => ['답은 맞으나 과정 불명확', '찍기 의심'],
                'persona_mapping' => ['P004', 'P012'],
                'metadata' => [
                    'type' => '역질문',
                    'priority' => 2
                ]
            ],
            [
                'activity_id' => 'INT_5_4',
                'category' => 'questioning_probing',
                'name' => '선택지 질문',
                'description' => '"A일까 B일까?" 이지선다로 부담 경감',
                'trigger_signals' => ['열린 질문에 대답 못함', '막막해함'],
                'persona_mapping' => ['P001', 'P002', 'P011'],
                'metadata' => [
                    'type' => '선택지',
                    'priority' => 1
                ]
            ],
            [
                'activity_id' => 'INT_5_5',
                'category' => 'questioning_probing',
                'name' => '힌트 질문',
                'description' => '"만약 여기가 0이면?" 방향 유도',
                'trigger_signals' => ['시작점을 못 잡음', '백지 상태'],
                'persona_mapping' => ['P001', 'P011'],
                'metadata' => [
                    'type' => '힌트',
                    'priority' => 1
                ]
            ],
            [
                'activity_id' => 'INT_5_6',
                'category' => 'questioning_probing',
                'name' => '연결 질문',
                'description' => '"이거 저번에 한 거랑 뭐가 비슷해?"',
                'trigger_signals' => ['새 개념에 고립감', '기존 지식 활성화 필요'],
                'persona_mapping' => ['P006', 'P009'],
                'metadata' => [
                    'type' => '연결',
                    'priority' => 2
                ]
            ],
            [
                'activity_id' => 'INT_5_7',
                'category' => 'questioning_probing',
                'name' => '메타인지 질문',
                'description' => '"지금 어디가 헷갈려?" 자기 상태 인식 유도',
                'trigger_signals' => ['막연한 "모르겠어요"', '구체화 필요'],
                'persona_mapping' => ['P001', 'P011', 'P012'],
                'metadata' => [
                    'type' => '메타인지',
                    'priority' => 1
                ]
            ],
            
            // 6. 즉시 개입 (Immediate Intervention) — 6개
            [
                'activity_id' => 'INT_6_1',
                'category' => 'immediate_intervention',
                'name' => '즉시 교정',
                'description' => '오류 순간 "잠깐!" 바로 멈추고 수정',
                'trigger_signals' => ['계산 실수', '부호 오류', '공식 오적용'],
                'persona_mapping' => ['P004', 'P008'],
                'metadata' => [
                    'timing' => '즉시',
                    'priority' => 1
                ]
            ],
            [
                'activity_id' => 'INT_6_2',
                'category' => 'immediate_intervention',
                'name' => '부분 인정 확장',
                'description' => '"거기까진 맞아, 근데..." 긍정 후 보완',
                'trigger_signals' => ['방향은 맞으나 불완전한 답변'],
                'persona_mapping' => ['P002', 'P003'],
                'metadata' => [
                    'method' => '인정 후 확장',
                    'priority' => 2
                ]
            ],
            [
                'activity_id' => 'INT_6_3',
                'category' => 'immediate_intervention',
                'name' => '함께 완성',
                'description' => '막힌 부분부터 같이 써가며 이끌기',
                'trigger_signals' => ['말/글이 중간에 끊김', '다음 진행 불가'],
                'persona_mapping' => ['P001', 'P010'],
                'metadata' => [
                    'method' => '협력',
                    'priority' => 1
                ]
            ],
            [
                'activity_id' => 'INT_6_4',
                'category' => 'immediate_intervention',
                'name' => '되물어 확인',
                'description' => '"네 말은 ~라는 거지?" 재구성 확인',
                'trigger_signals' => ['답변이 모호하거나 문장이 불완전'],
                'persona_mapping' => ['P002', 'P009'],
                'metadata' => [
                    'method' => '재구성',
                    'priority' => 2
                ]
            ],
            [
                'activity_id' => 'INT_6_5',
                'category' => 'immediate_intervention',
                'name' => '오개념 즉시 분리',
                'description' => '"그건 다른 거야" 혼동 요소 명확 분리',
                'trigger_signals' => ['두 개념 혼합 사용', '용어 혼란'],
                'persona_mapping' => ['P004', 'P008'],
                'metadata' => [
                    'method' => '분리',
                    'priority' => 1
                ]
            ],
            [
                'activity_id' => 'INT_6_6',
                'category' => 'immediate_intervention',
                'name' => '실시간 시범',
                'description' => '학생 시도 옆에서 바로 올바른 과정 시연',
                'trigger_signals' => ['같은 실수 반복', '말로 교정 안 됨'],
                'persona_mapping' => ['P004', 'P010'],
                'metadata' => [
                    'method' => '시범',
                    'priority' => 1
                ]
            ],
            
            // 7. 정서 조절 (Emotional Regulation) — 6개
            [
                'activity_id' => 'INT_7_1',
                'category' => 'emotional_regulation',
                'name' => '노력 인정',
                'description' => '"열심히 생각했네" 과정 자체 칭찬',
                'trigger_signals' => ['오답이지만 시도함', '좌절 직전'],
                'persona_mapping' => ['P003', 'P011'],
                'metadata' => [
                    'focus' => '과정',
                    'priority' => 1
                ]
            ],
            [
                'activity_id' => 'INT_7_2',
                'category' => 'emotional_regulation',
                'name' => '정상화',
                'description' => '"이거 다 어려워해" 혼자가 아님 전달',
                'trigger_signals' => ['자책', '"나만 못해요" 표현'],
                'persona_mapping' => ['P003', 'P011'],
                'metadata' => [
                    'focus' => '정상화',
                    'priority' => 1
                ]
            ],
            [
                'activity_id' => 'INT_7_3',
                'category' => 'emotional_regulation',
                'name' => '난이도 조정 예고',
                'description' => '"이건 어려운 거야, 천천히 가자"',
                'trigger_signals' => ['불안 상승', '조급함', '빨리 끝내려 함'],
                'persona_mapping' => ['P003', 'P008'],
                'metadata' => [
                    'focus' => '난이도',
                    'priority' => 1
                ]
            ],
            [
                'activity_id' => 'INT_7_4',
                'category' => 'emotional_regulation',
                'name' => '작은 성공 만들기',
                'description' => '일부러 쉬운 질문으로 성취감 제공',
                'trigger_signals' => ['연속 오답', '자신감 저하'],
                'persona_mapping' => ['P003', 'P011'],
                'metadata' => [
                    'focus' => '성공 경험',
                    'priority' => 1
                ]
            ],
            [
                'activity_id' => 'INT_7_5',
                'category' => 'emotional_regulation',
                'name' => '유머/가벼운 전환',
                'description' => '잠깐 긴장 풀어주는 가벼운 말',
                'trigger_signals' => ['과도한 긴장', '어깨 경직', '호흡 얕음'],
                'persona_mapping' => ['P003', 'P008'],
                'metadata' => [
                    'focus' => '긴장 완화',
                    'priority' => 2
                ]
            ],
            [
                'activity_id' => 'INT_7_6',
                'category' => 'emotional_regulation',
                'name' => '선택권 부여',
                'description' => '"이거 먼저 할까, 저거 먼저 할까?"',
                'trigger_signals' => ['통제감 상실', '무기력 신호'],
                'persona_mapping' => ['P011', 'P010'],
                'metadata' => [
                    'focus' => '선택권',
                    'priority' => 1
                ]
            ]
        ];
    }
    
    /**
     * 모든 개입 활동 조회
     */
    public function getAllInterventions() {
        return $this->dbManager->getDB()->findAll('intervention_activities', [], ['activity_id' => 'ASC']);
    }
    
    /**
     * 카테고리별 개입 활동 조회
     */
    public function getInterventionsByCategory($category) {
        return $this->dbManager->getDB()->findAll('intervention_activities', ['category' => $category]);
    }
    
    /**
     * 페르소나별 개입 활동 조회
     */
    public function getInterventionsByPersona($personaId) {
        return $this->dbManager->getDB()->query('intervention_activities', [
            'where' => [
                'persona_mapping' => ['$in' => [$personaId]]
            ]
        ]);
    }
    
    /**
     * 트리거 신호 기반 개입 활동 선택
     */
    public function selectInterventionBySignals($signals, $personaId = null) {
        $allInterventions = $this->getAllInterventions();
        $scores = [];
        
        foreach ($allInterventions as $intervention) {
            $score = 0;
            
            // 트리거 신호 매칭
            foreach ($signals as $signal) {
                foreach ($intervention['trigger_signals'] as $trigger) {
                    if (stripos($trigger, $signal) !== false || stripos($signal, $trigger) !== false) {
                        $score += 3;
                    }
                }
            }
            
            // 페르소나 매칭
            if ($personaId && in_array($personaId, $intervention['persona_mapping'])) {
                $score += 5;
            }
            
            // 우선순위 반영
            $priority = $intervention['metadata']['priority'] ?? 3;
            $score += (4 - $priority); // 높은 우선순위일수록 높은 점수
            
            if ($score > 0) {
                $scores[] = [
                    'intervention' => $intervention,
                    'score' => $score
                ];
            }
        }
        
        // 점수 순으로 정렬
        usort($scores, function($a, $b) {
            return $b['score'] - $a['score'];
        });
        
        return $scores;
    }
    
    /**
     * 개입 활동 ID로 조회
     */
    public function getIntervention($activityId) {
        $interventions = $this->dbManager->getDB()->findAll('intervention_activities', ['activity_id' => $activityId]);
        return !empty($interventions) ? $interventions[0] : null;
    }
    
    /**
     * 개입 활동 실행 기록 저장
     */
    public function logInterventionExecution($activityId, $studentId, $context) {
        $db = $this->dbManager->getDB();
        
        if (!$db->tableExists('intervention_executions')) {
            $db->createTable('intervention_executions', [
                'activity_id' => 'string',
                'student_id' => 'integer',
                'executed_at' => 'datetime',
                'context' => 'object',
                'effectiveness' => 'float',
                'metadata' => 'object'
            ]);
            $db->createIndex('intervention_executions', 'activity_id');
            $db->createIndex('intervention_executions', 'student_id');
        }
        
        return $db->insert('intervention_executions', [
            'activity_id' => $activityId,
            'student_id' => $studentId,
            'executed_at' => date('Y-m-d H:i:s'),
            'context' => $context,
            'effectiveness' => 0.5, // 기본값, 나중에 업데이트
            'metadata' => []
        ]);
    }
}

