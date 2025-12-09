<?php
/**
 * 페르소나 매니저
 * 학습자 페르소나 데이터 관리
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 * @version    1.0
 */

require_once(__DIR__ . '/db_manager.php');

class PersonaManager {
    private $dbManager;
    
    public function __construct() {
        $this->dbManager = new DBManager();
        $this->initializePersonas();
    }
    
    /**
     * 페르소나 초기화
     */
    private function initializePersonas() {
        $db = $this->dbManager->getDB();
        
        // 페르소나 테이블 생성
        if (!$db->tableExists('personas')) {
            $db->createTable('personas', [
                'persona_id' => 'string',
                'name' => 'string',
                'name_en' => 'string',
                'situation' => 'string',
                'behaviors' => 'array',
                'hidden_causes' => 'array',
                'interventions' => 'array',
                'metadata' => 'object'
            ]);
            $db->createIndex('personas', 'persona_id');
            $db->createIndex('personas', 'name_en');
            
            // 기본 페르소나 데이터 로드
            $this->loadDefaultPersonas();
        }
    }
    
    /**
     * 기본 페르소나 데이터 로드
     */
    private function loadDefaultPersonas() {
        $personas = $this->getDefaultPersonas();
        
        foreach ($personas as $persona) {
            // 이미 존재하는지 확인
            $existing = $this->dbManager->getDB()->findAll('personas', [
                'persona_id' => $persona['persona_id']
            ]);
            
            if (empty($existing)) {
                $this->dbManager->getDB()->insert('personas', $persona);
            }
        }
    }
    
    /**
     * 기본 페르소나 데이터 정의
     */
    private function getDefaultPersonas() {
        return [
            [
                'persona_id' => 'P001',
                'name' => '막힘-회피형',
                'name_en' => 'Avoider',
                'situation' => '문제 읽다 막히면 바로 연필 내려놓음',
                'behaviors' => [
                    '말하기: "몰라요…"',
                    '문제 안 읽고 다음으로 넘김',
                    '시도하지 않고 포기'
                ],
                'hidden_causes' => [
                    '실패 불안',
                    '작업기억 과부하'
                ],
                'interventions' => [
                    '1단계 청킹 (문제를 작은 단위로 분해)',
                    '초미세 단서 제공',
                    '시선추적 기반 "다시 주목 리드"'
                ],
                'metadata' => [
                    'category' => '인지적',
                    'difficulty_level' => 'high',
                    'intervention_priority' => 1
                ]
            ],
            [
                'persona_id' => 'P002',
                'name' => '확인요구형',
                'name_en' => 'Checker',
                'situation' => '맞는지 계속 물어봄',
                'behaviors' => [
                    '"이렇게 하면 되죠?" 반복',
                    '자신감 부족으로 확인 요구',
                    '진행 중단'
                ],
                'hidden_causes' => [
                    '낮은 학습 효능감',
                    '자기 확신 부족'
                ],
                'interventions' => [
                    '정답 확인 금지',
                    '진행도 피드백 강화',
                    '스몰 스텝 성공 경험 축적'
                ],
                'metadata' => [
                    'category' => '정서적',
                    'difficulty_level' => 'medium',
                    'intervention_priority' => 2
                ]
            ],
            [
                'persona_id' => 'P003',
                'name' => '감정출렁형',
                'name_en' => 'Emotion-driven',
                'situation' => '문제 한 개 틀리면 전체 기분 하락',
                'behaviors' => [
                    '표정 다운',
                    '속도 느려짐',
                    '학습 의욕 급락'
                ],
                'hidden_causes' => [
                    '정서 조절력 부족',
                    '과도한 완벽주의'
                ],
                'interventions' => [
                    '즉시 공감',
                    '정서 레이블링',
                    '난이도 미세조절로 안정감 확보'
                ],
                'metadata' => [
                    'category' => '정서적',
                    'difficulty_level' => 'high',
                    'intervention_priority' => 1
                ]
            ],
            [
                'persona_id' => 'P004',
                'name' => '빠른데 허술형',
                'name_en' => 'Speed-but-Miss',
                'situation' => '빨리 끝냈는데 실수 많음',
                'behaviors' => [
                    '계산 실수',
                    '단위 누락',
                    '문제 조건 놓침'
                ],
                'hidden_causes' => [
                    '과도한 자동화',
                    '검증 회로 부재'
                ],
                'interventions' => [
                    '마지막 10초 검증 루틴 도입',
                    '체크리스트 제공',
                    '속도 조절 훈련'
                ],
                'metadata' => [
                    'category' => '인지적',
                    'difficulty_level' => 'medium',
                    'intervention_priority' => 3
                ]
            ],
            [
                'persona_id' => 'P005',
                'name' => '집중 튐형',
                'name_en' => 'Attention Hopper',
                'situation' => '문제 읽다가 다른 줄로 눈이 튐',
                'behaviors' => [
                    '시선 불안정',
                    '방향성 없는 질문',
                    '집중력 단절'
                ],
                'hidden_causes' => [
                    '주의 지속시간 짧음',
                    'ADHD 가능성'
                ],
                'interventions' => [
                    '시선 리다이렉션',
                    '문장 단위로 OCR·하이라이트 가이드',
                    '단계별 주의 집중 훈련'
                ],
                'metadata' => [
                    'category' => '인지적',
                    'difficulty_level' => 'high',
                    'intervention_priority' => 1
                ]
            ],
            [
                'persona_id' => 'P006',
                'name' => '패턴추론형',
                'name_en' => 'Pattern Seeker',
                'situation' => '전체 구조 먼저 찾으려 함',
                'behaviors' => [
                    '"여기서 의도는…"',
                    '원리 탐색 선호',
                    '구조 우선 사고'
                ],
                'hidden_causes' => [
                    '고차원적 처리 선호',
                    '추상적 사고 능력'
                ],
                'interventions' => [
                    '구조 먼저 제시',
                    '사례→공식 순서로 제공',
                    '심화 문제 제공'
                ],
                'metadata' => [
                    'category' => '인지적',
                    'difficulty_level' => 'low',
                    'intervention_priority' => 4
                ]
            ],
            [
                'persona_id' => 'P007',
                'name' => '최대한 쉬운길 찾기형',
                'name_en' => 'Efficiency Maximizer',
                'situation' => '최소 노력으로 최대 결과 원함',
                'behaviors' => [
                    '지름길, 공략, 노하우 질문',
                    '효율성 추구',
                    '핵심만 학습'
                ],
                'hidden_causes' => [
                    '합리적 학습자',
                    '전략적 사고'
                ],
                'interventions' => [
                    '"핵심 규칙 20%" 먼저 제시',
                    '유형화 기반 반복',
                    '효율적 학습 경로 제공'
                ],
                'metadata' => [
                    'category' => '전략적',
                    'difficulty_level' => 'low',
                    'intervention_priority' => 4
                ]
            ],
            [
                'persona_id' => 'P008',
                'name' => '불안과몰입형',
                'name_en' => 'Over-focusing Worrier',
                'situation' => '쉬운 문제에도 오래 붙잡힘',
                'behaviors' => [
                    '확인·재확인 반복',
                    '시간 과다 소비',
                    '진행 속도 저하'
                ],
                'hidden_causes' => [
                    '실수에 대한 과도한 민감성',
                    '불안 장애 가능성'
                ],
                'interventions' => [
                    '시간제한 설정',
                    '"여기까지만 확인 규칙" 제공',
                    '완벽주의 완화 훈련'
                ],
                'metadata' => [
                    'category' => '정서적',
                    'difficulty_level' => 'medium',
                    'intervention_priority' => 2
                ]
            ],
            [
                'persona_id' => 'P009',
                'name' => '추상-언어 약함형',
                'name_en' => 'Concrete Learner',
                'situation' => '설명은 이해 못하지만 예시는 잘 따라옴',
                'behaviors' => [
                    '"예시 하나만 더요"',
                    '구체적 사례 선호',
                    '추상 설명 회피'
                ],
                'hidden_causes' => [
                    '추상처리능력 낮음',
                    '구체적 사고 선호'
                ],
                'interventions' => [
                    '하→상 구조 (예시 → 규칙 → 적용)',
                    '다양한 구체적 사례 제공',
                    '점진적 추상화'
                ],
                'metadata' => [
                    'category' => '인지적',
                    'difficulty_level' => 'medium',
                    'intervention_priority' => 2
                ]
            ],
            [
                'persona_id' => 'P010',
                'name' => '상호작용 의존형',
                'name_en' => 'Interactive Dependent',
                'situation' => '혼자 풀면 갑자기 정지',
                'behaviors' => [
                    '옆에서 질문해주면 폭발적으로 진행',
                    '외부 자극 필요',
                    '독립 학습 어려움'
                ],
                'hidden_causes' => [
                    '외부 자극 필요',
                    '자기 주도성 부족'
                ],
                'interventions' => [
                    '로봇/아바타의 음성 큐로 지속 자극',
                    '단계별 피드백 제공',
                    '자기 주도성 점진적 훈련'
                ],
                'metadata' => [
                    'category' => '행동적',
                    'difficulty_level' => 'high',
                    'intervention_priority' => 1
                ]
            ],
            [
                'persona_id' => 'P011',
                'name' => '무기력·저동기형',
                'name_en' => 'Low Drive',
                'situation' => '시작부터 에너지 없음',
                'behaviors' => [
                    '앉아 있지만 진도 안 나감',
                    '의욕 부족',
                    '수동적 태도'
                ],
                'hidden_causes' => [
                    '정서적 소진',
                    '성공경험 부족'
                ],
                'interventions' => [
                    '초단위 목표·즉각 강화',
                    '"지금 막힌 이유" 메타인지 질문',
                    '작은 성공 경험 제공'
                ],
                'metadata' => [
                    'category' => '정서적',
                    'difficulty_level' => 'high',
                    'intervention_priority' => 1
                ]
            ],
            [
                'persona_id' => 'P012',
                'name' => '메타인지 고수형',
                'name_en' => 'Meta-high',
                'situation' => '스스로 오류검출·전략수립',
                'behaviors' => [
                    '"이건 구조가 이래서…"',
                    '자기 조절 능력 높음',
                    '전략적 사고'
                ],
                'hidden_causes' => [
                    '높은 자기조절력',
                    '발달된 메타인지'
                ],
                'interventions' => [
                    '고난도 전략·심화 제공',
                    '풀이 비교·추론게임 제공',
                    '도전적 과제 제시'
                ],
                'metadata' => [
                    'category' => '전략적',
                    'difficulty_level' => 'low',
                    'intervention_priority' => 5
                ]
            ]
        ];
    }
    
    /**
     * 모든 페르소나 조회
     */
    public function getAllPersonas() {
        return $this->dbManager->getDB()->findAll('personas', [], ['persona_id' => 'ASC']);
    }
    
    /**
     * 페르소나 ID로 조회
     */
    public function getPersona($personaId) {
        $personas = $this->dbManager->getDB()->findAll('personas', ['persona_id' => $personaId]);
        return !empty($personas) ? $personas[0] : null;
    }
    
    /**
     * 페르소나 이름으로 조회
     */
    public function getPersonaByName($name) {
        $personas = $this->dbManager->getDB()->findAll('personas', ['name' => $name]);
        return !empty($personas) ? $personas[0] : null;
    }
    
    /**
     * 카테고리별 페르소나 조회
     */
    public function getPersonasByCategory($category) {
        return $this->dbManager->getDB()->query('personas', [
            'where' => [
                'metadata.category' => $category
            ]
        ]);
    }
    
    /**
     * 학생 페르소나 매칭
     */
    public function matchStudentPersona($studentId, $interactionData) {
        // 학생의 상호작용 히스토리 조회
        $db = $this->dbManager->getDB();
        $interactions = $db->query('interactions', [
            'where' => ['student_id' => $studentId],
            'orderBy' => ['timestamp' => 'DESC'],
            'limit' => 20
        ]);
        
        // 페르소나별 점수 계산
        $scores = [];
        $allPersonas = $this->getAllPersonas();
        
        foreach ($allPersonas as $persona) {
            $score = $this->calculatePersonaScore($persona, $interactions, $interactionData);
            $scores[$persona['persona_id']] = [
                'persona' => $persona,
                'score' => $score,
                'match_percentage' => min(100, ($score / 10) * 100)
            ];
        }
        
        // 점수 순으로 정렬
        usort($scores, function($a, $b) {
            return $b['score'] - $a['score'];
        });
        
        return $scores;
    }
    
    /**
     * 페르소나 점수 계산
     */
    private function calculatePersonaScore($persona, $interactions, $currentInteraction) {
        $score = 0;
        
        // 행동 패턴 매칭
        foreach ($interactions as $interaction) {
            $userInput = strtolower($interaction['user_input'] ?? '');
            
            foreach ($persona['behaviors'] as $behavior) {
                $behaviorLower = strtolower($behavior);
                if (strpos($userInput, $behaviorLower) !== false || 
                    strpos($behaviorLower, $userInput) !== false) {
                    $score += 2;
                }
            }
        }
        
        // 현재 상호작용 분석
        $currentInput = strtolower($currentInteraction['user_input'] ?? '');
        foreach ($persona['behaviors'] as $behavior) {
            $behaviorLower = strtolower($behavior);
            if (strpos($currentInput, $behaviorLower) !== false) {
                $score += 3;
            }
        }
        
        // 상황 매칭
        $situationLower = strtolower($persona['situation']);
        if (strpos($currentInput, $situationLower) !== false) {
            $score += 5;
        }
        
        return $score;
    }
    
    /**
     * 학생 페르소나 저장
     */
    public function saveStudentPersona($studentId, $personaId, $confidence = 0.8) {
        $db = $this->dbManager->getDB();
        
        // 학생 페르소나 테이블 확인
        if (!$db->tableExists('student_personas')) {
            $db->createTable('student_personas', [
                'student_id' => 'integer',
                'persona_id' => 'string',
                'confidence' => 'float',
                'matched_at' => 'datetime',
                'metadata' => 'object'
            ]);
            $db->createIndex('student_personas', 'student_id');
            $db->createIndex('student_personas', 'persona_id');
        }
        
        // 기존 매칭 확인
        $existing = $db->findAll('student_personas', [
            'student_id' => $studentId,
            'persona_id' => $personaId
        ]);
        
        if (!empty($existing)) {
            // 업데이트
            $db->update('student_personas', $existing[0]['id'], [
                'confidence' => $confidence,
                'matched_at' => date('Y-m-d H:i:s')
            ]);
            return $existing[0]['id'];
        } else {
            // 새로 저장
            return $db->insert('student_personas', [
                'student_id' => $studentId,
                'persona_id' => $personaId,
                'confidence' => $confidence,
                'matched_at' => date('Y-m-d H:i:s'),
                'metadata' => []
            ]);
        }
    }
    
    /**
     * 학생의 페르소나 조회
     */
    public function getStudentPersonas($studentId) {
        $db = $this->dbManager->getDB();
        
        if (!$db->tableExists('student_personas')) {
            return [];
        }
        
        $matches = $db->query('student_personas', [
            'where' => ['student_id' => $studentId],
            'orderBy' => ['confidence' => 'DESC']
        ]);
        
        $result = [];
        foreach ($matches as $match) {
            $persona = $this->getPersona($match['persona_id']);
            if ($persona) {
                $result[] = [
                    'persona' => $persona,
                    'confidence' => $match['confidence'],
                    'matched_at' => $match['matched_at']
                ];
            }
        }
        
        return $result;
    }
    
    /**
     * 페르소나 기반 개입 전략 가져오기
     */
    public function getInterventionStrategy($personaId) {
        $persona = $this->getPersona($personaId);
        
        if (!$persona) {
            return null;
        }
        
        return [
            'persona' => $persona,
            'interventions' => $persona['interventions'],
            'priority' => $persona['metadata']['intervention_priority'] ?? 5,
            'recommended_approach' => $this->getRecommendedApproach($persona)
        ];
    }
    
    /**
     * 권장 접근 방식 가져오기
     */
    private function getRecommendedApproach($persona) {
        $category = $persona['metadata']['category'] ?? '';
        $difficulty = $persona['metadata']['difficulty_level'] ?? 'medium';
        
        $approaches = [
            '인지적' => [
                'high' => '단계별 분해, 시각적 가이드, 반복 훈련',
                'medium' => '구조화된 설명, 예시 제공, 점진적 난이도',
                'low' => '심화 문제, 추상적 사고, 전략적 접근'
            ],
            '정서적' => [
                'high' => '즉시 공감, 정서 지원, 안정감 확보',
                'medium' => '피드백 강화, 성공 경험, 동기 부여',
                'low' => '도전 과제, 자율성 부여, 성장 기회'
            ],
            '전략적' => [
                'high' => '구체적 전략 제시, 단계별 가이드',
                'medium' => '효율적 방법 안내, 핵심 강조',
                'low' => '고난도 전략, 심화 학습, 창의적 접근'
            ],
            '행동적' => [
                'high' => '외부 자극 제공, 단계별 피드백',
                'medium' => '자기 주도성 훈련, 점진적 독립',
                'low' => '자율 학습, 메타인지 활용'
            ]
        ];
        
        return $approaches[$category][$difficulty] ?? '맞춤형 접근 필요';
    }
    
    /**
     * DB 접근
     */
    public function getDB() {
        return $this->dbManager->getDB();
    }
}

