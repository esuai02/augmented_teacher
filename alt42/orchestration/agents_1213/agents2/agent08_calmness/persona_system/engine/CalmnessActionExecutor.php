<?php
/**
 * CalmnessActionExecutor - Agent08 전용 액션 실행기
 *
 * BaseActionExecutor를 확장하여 침착성(Calmness) 관련 액션을 실행합니다.
 * 호흡 운동, 그라운딩, 위기 대응, 침착성 점수 관리 등을 지원합니다.
 *
 * @package AugmentedTeacher\Agent08\PersonaSystem
 * @version 1.0
 * @author Claude Code
 */

// Moodle 환경 로드
if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}
global $DB, $USER;

// 기본 액션 실행기 로드
require_once(__DIR__ . '/../../../../ontology_engineering/persona_engine/impl/BaseActionExecutor.php');

use AugmentedTeacher\PersonaEngine\Impl\BaseActionExecutor;

class CalmnessActionExecutor extends BaseActionExecutor {

    /** @var string 현재 파일 경로 (에러 로깅용) */
    protected $currentFile = __FILE__;

    /** @var string 에이전트 ID */
    protected $agentId = 'agent08';

    /** @var array 호흡 운동 설정 */
    private $breathingExercises = [
        '4-7-8' => [
            'name' => '4-7-8 호흡법',
            'description' => '4초 들이쉬고, 7초 참고, 8초 내쉬기',
            'steps' => [
                ['action' => 'inhale', 'duration' => 4, 'instruction' => '코로 천천히 숨을 들이쉬세요'],
                ['action' => 'hold', 'duration' => 7, 'instruction' => '숨을 참으세요'],
                ['action' => 'exhale', 'duration' => 8, 'instruction' => '입으로 천천히 숨을 내쉬세요']
            ],
            'repetitions' => 4,
            'total_duration' => 76
        ],
        'box' => [
            'name' => '박스 호흡법',
            'description' => '4초씩 들이쉬고, 참고, 내쉬고, 참기',
            'steps' => [
                ['action' => 'inhale', 'duration' => 4, 'instruction' => '천천히 숨을 들이쉬세요'],
                ['action' => 'hold', 'duration' => 4, 'instruction' => '숨을 참으세요'],
                ['action' => 'exhale', 'duration' => 4, 'instruction' => '천천히 숨을 내쉬세요'],
                ['action' => 'hold', 'duration' => 4, 'instruction' => '잠시 멈추세요']
            ],
            'repetitions' => 4,
            'total_duration' => 64
        ],
        'deep' => [
            'name' => '깊은 복식 호흡',
            'description' => '배를 부풀리며 깊게 호흡하기',
            'steps' => [
                ['action' => 'inhale', 'duration' => 5, 'instruction' => '배를 부풀리며 깊게 들이쉬세요'],
                ['action' => 'exhale', 'duration' => 5, 'instruction' => '배를 당기며 천천히 내쉬세요']
            ],
            'repetitions' => 6,
            'total_duration' => 60
        ],
        'calming' => [
            'name' => '진정 호흡',
            'description' => '내쉬는 시간을 늘려 부교감신경 활성화',
            'steps' => [
                ['action' => 'inhale', 'duration' => 4, 'instruction' => '편하게 숨을 들이쉬세요'],
                ['action' => 'exhale', 'duration' => 6, 'instruction' => '더 길게 숨을 내쉬세요']
            ],
            'repetitions' => 6,
            'total_duration' => 60
        ]
    ];

    /** @var array 그라운딩 운동 설정 */
    private $groundingExercises = [
        '5-4-3-2-1' => [
            'name' => '5-4-3-2-1 감각 그라운딩',
            'description' => '다섯 가지 감각을 활용한 현재 순간 집중',
            'steps' => [
                ['sense' => 'sight', 'count' => 5, 'instruction' => '주변에서 볼 수 있는 5가지를 찾아보세요'],
                ['sense' => 'touch', 'count' => 4, 'instruction' => '만질 수 있는 4가지를 느껴보세요'],
                ['sense' => 'sound', 'count' => 3, 'instruction' => '들리는 3가지 소리에 집중해보세요'],
                ['sense' => 'smell', 'count' => 2, 'instruction' => '맡을 수 있는 2가지 냄새를 찾아보세요'],
                ['sense' => 'taste', 'count' => 1, 'instruction' => '입안에서 느껴지는 1가지 맛에 집중해보세요']
            ],
            'total_duration' => 180
        ],
        'body_scan' => [
            'name' => '신체 스캔',
            'description' => '발끝부터 머리까지 천천히 신체 감각 인식',
            'steps' => [
                ['part' => 'feet', 'instruction' => '발가락과 발바닥의 감각을 느껴보세요'],
                ['part' => 'legs', 'instruction' => '다리의 무게와 온기를 느껴보세요'],
                ['part' => 'abdomen', 'instruction' => '배의 움직임을 관찰해보세요'],
                ['part' => 'chest', 'instruction' => '가슴의 호흡 움직임을 느껴보세요'],
                ['part' => 'hands', 'instruction' => '손의 감각과 온도를 느껴보세요'],
                ['part' => 'shoulders', 'instruction' => '어깨의 긴장을 느끼고 풀어보세요'],
                ['part' => 'head', 'instruction' => '얼굴과 머리의 감각을 인식해보세요']
            ],
            'total_duration' => 300
        ],
        'safe_place' => [
            'name' => '안전한 장소 시각화',
            'description' => '마음속 안전하고 평화로운 장소 상상하기',
            'steps' => [
                ['phase' => 'recall', 'instruction' => '안전하고 평화롭게 느껴지는 장소를 떠올려보세요'],
                ['phase' => 'visualize', 'instruction' => '그 장소의 색깔과 모양을 상상해보세요'],
                ['phase' => 'feel', 'instruction' => '그 장소의 온도와 공기를 느껴보세요'],
                ['phase' => 'sound', 'instruction' => '그 장소에서 들리는 소리를 상상해보세요'],
                ['phase' => 'comfort', 'instruction' => '그 장소에서 느끼는 평화로움에 머물러보세요']
            ],
            'total_duration' => 240
        ],
        'object_focus' => [
            'name' => '물체 집중',
            'description' => '손에 쥔 물체에 온전히 집중하기',
            'steps' => [
                ['action' => 'hold', 'instruction' => '손에 작은 물체(열쇠, 돌, 펜 등)를 잡으세요'],
                ['action' => 'observe', 'instruction' => '물체의 모양과 색을 자세히 관찰하세요'],
                ['action' => 'feel', 'instruction' => '물체의 질감과 무게를 느껴보세요'],
                ['action' => 'temperature', 'instruction' => '물체의 온도 변화를 느껴보세요']
            ],
            'total_duration' => 120
        ]
    ];

    /** @var array 위기 대응 리소스 */
    private $crisisResources = [
        'korea' => [
            'emergency' => '119',
            'suicide_hotline' => '1393',
            'mental_health' => '1577-0199',
            'counseling' => '02-722-0199'
        ]
    ];

    /**
     * 생성자 - 침착성 특화 핸들러 등록
     *
     * @param bool $debugMode 디버그 모드
     */
    public function __construct(bool $debugMode = false) {
        parent::__construct($debugMode);
        $this->registerCalmnessHandlers();
    }

    /**
     * 침착성 특화 핸들러 등록
     */
    private function registerCalmnessHandlers(): void {

        // ========================================
        // 호흡 운동 핸들러
        // ========================================

        $this->registerHandler('start_breathing_exercise', function(array $params, array $context): array {
            $type = $params['type'] ?? '4-7-8';
            $customRepetitions = $params['repetitions'] ?? null;

            if (!isset($this->breathingExercises[$type])) {
                return [
                    'success' => false,
                    'error' => "알 수 없는 호흡 운동 타입: {$type}"
                ];
            }

            $exercise = $this->breathingExercises[$type];
            if ($customRepetitions) {
                $exercise['repetitions'] = (int)$customRepetitions;
            }

            // 운동 시작 로그
            $this->logCalmnessAction('breathing_started', [
                'type' => $type,
                'user_id' => $context['user_id'] ?? 0
            ]);

            return [
                'success' => true,
                'exercise_type' => 'breathing',
                'exercise_name' => $exercise['name'],
                'description' => $exercise['description'],
                'steps' => $exercise['steps'],
                'repetitions' => $exercise['repetitions'],
                'total_duration' => $exercise['total_duration'],
                'started_at' => date('Y-m-d H:i:s')
            ];
        });

        // ========================================
        // 그라운딩 운동 핸들러
        // ========================================

        $this->registerHandler('start_grounding_exercise', function(array $params, array $context): array {
            $type = $params['type'] ?? '5-4-3-2-1';

            if (!isset($this->groundingExercises[$type])) {
                return [
                    'success' => false,
                    'error' => "알 수 없는 그라운딩 운동 타입: {$type}"
                ];
            }

            $exercise = $this->groundingExercises[$type];

            // 운동 시작 로그
            $this->logCalmnessAction('grounding_started', [
                'type' => $type,
                'user_id' => $context['user_id'] ?? 0
            ]);

            return [
                'success' => true,
                'exercise_type' => 'grounding',
                'exercise_name' => $exercise['name'],
                'description' => $exercise['description'],
                'steps' => $exercise['steps'],
                'total_duration' => $exercise['total_duration'],
                'started_at' => date('Y-m-d H:i:s')
            ];
        });

        // ========================================
        // 침착성 점수 관리 핸들러
        // ========================================

        $this->registerHandler('save_calmness_score', function(array $params, array $context): array {
            global $DB;

            $userId = $params['user_id'] ?? $context['user_id'] ?? 0;
            $score = $params['score'] ?? 85;
            $level = $this->calculateCalmnessLevel($score);

            try {
                $record = new \stdClass();
                $record->user_id = $userId;
                $record->agent_id = $this->agentId;
                $record->score = $score;
                $record->level = $level;
                $record->metadata = json_encode($params['metadata'] ?? []);
                $record->created_at = date('Y-m-d H:i:s');

                $DB->insert_record('at_calmness_scores', $record);

                return [
                    'success' => true,
                    'saved' => true,
                    'score' => $score,
                    'level' => $level
                ];
            } catch (\Exception $e) {
                error_log("[CalmnessActionExecutor] {$this->currentFile}:" . __LINE__ .
                    " - 침착성 점수 저장 실패: " . $e->getMessage());
                return [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        });

        $this->registerHandler('update_calmness_level', function(array $params, array $context): array {
            $level = $params['level'] ?? 'C85';
            $validLevels = ['C95', 'C90', 'C85', 'C80', 'C75', 'C_crisis'];

            if (!in_array($level, $validLevels)) {
                $level = 'C85';
            }

            return [
                'success' => true,
                'calmness_level' => $level,
                'level_description' => $this->getCalmnessLevelDescription($level)
            ];
        });

        // ========================================
        // 위기 대응 핸들러
        // ========================================

        $this->registerHandler('trigger_crisis_protocol', function(array $params, array $context): array {
            $severity = $params['severity'] ?? 'moderate';
            $crisisType = $params['type'] ?? 'general';

            // 위기 로그 기록
            $this->logCrisisEvent($context['user_id'] ?? 0, $severity, $crisisType, $params);

            // 심각도에 따른 응답
            $response = [
                'success' => true,
                'crisis_activated' => true,
                'severity' => $severity,
                'type' => $crisisType,
                'timestamp' => date('Y-m-d H:i:s')
            ];

            if ($severity === 'critical') {
                $response['immediate_action'] = 'professional_referral';
                $response['resources'] = $this->crisisResources['korea'];
                $response['message'] = '당신의 안전이 가장 중요합니다. 전문 상담사와 연결해 드릴까요?';
            } elseif ($severity === 'high') {
                $response['immediate_action'] = 'safety_check';
                $response['resources'] = $this->crisisResources['korea'];
                $response['message'] = '지금 많이 힘드시군요. 잠시 함께 이야기해볼까요?';
            } else {
                $response['immediate_action'] = 'support_conversation';
                $response['message'] = '당신의 마음을 이해합니다. 천천히 이야기해주세요.';
            }

            return $response;
        });

        $this->registerHandler('provide_crisis_resources', function(array $params, array $context): array {
            $region = $params['region'] ?? 'korea';

            return [
                'success' => true,
                'resources' => $this->crisisResources[$region] ?? $this->crisisResources['korea'],
                'message' => '도움이 필요하시면 아래 연락처로 연락해 주세요.'
            ];
        });

        $this->registerHandler('escalate_to_professional', function(array $params, array $context): array {
            $reason = $params['reason'] ?? 'crisis_detected';
            $userId = $context['user_id'] ?? 0;

            // 에스컬레이션 로그
            $this->logCalmnessAction('professional_escalation', [
                'user_id' => $userId,
                'reason' => $reason,
                'params' => $params
            ]);

            return [
                'success' => true,
                'escalated' => true,
                'reason' => $reason,
                'next_steps' => [
                    '전문 상담사에게 연결',
                    '보호자 알림 (필요시)',
                    '후속 모니터링 설정'
                ],
                'resources' => $this->crisisResources['korea']
            ];
        });

        // ========================================
        // 개입 전략 핸들러
        // ========================================

        $this->registerHandler('set_intervention_strategy', function(array $params, array $context): array {
            $primary = $params['primary'] ?? 'InformationProvision';
            $secondary = $params['secondary'] ?? null;
            $urgency = $params['urgency'] ?? 'normal';

            return [
                'success' => true,
                'intervention' => [
                    'primary' => $primary,
                    'secondary' => $secondary,
                    'urgency' => $urgency
                ]
            ];
        });

        $this->registerHandler('apply_calmness_coaching', function(array $params, array $context): array {
            $technique = $params['technique'] ?? 'general';
            $duration = $params['duration'] ?? 5;

            $coachingTechniques = [
                'general' => [
                    'name' => '일반 침착성 코칭',
                    'tips' => [
                        '천천히 깊게 숨을 쉬어보세요',
                        '지금 이 순간에 집중해보세요',
                        '모든 것은 지나갑니다'
                    ]
                ],
                'anxiety' => [
                    'name' => '불안 완화 코칭',
                    'tips' => [
                        '불안한 감정을 인정해주세요',
                        '최악의 상황은 거의 일어나지 않아요',
                        '지금 할 수 있는 작은 것에 집중해보세요'
                    ]
                ],
                'stress' => [
                    'name' => '스트레스 관리 코칭',
                    'tips' => [
                        '잠시 멈추고 쉬어가도 괜찮아요',
                        '한 번에 하나씩 처리해보세요',
                        '완벽하지 않아도 괜찮습니다'
                    ]
                ],
                'overwhelm' => [
                    'name' => '압도감 대처 코칭',
                    'tips' => [
                        '가장 작은 것부터 시작해보세요',
                        '모든 것을 한 번에 해결할 필요 없어요',
                        '도움을 요청하는 것은 강함의 표시입니다'
                    ]
                ]
            ];

            $coaching = $coachingTechniques[$technique] ?? $coachingTechniques['general'];

            return [
                'success' => true,
                'coaching_type' => $technique,
                'coaching_name' => $coaching['name'],
                'tips' => $coaching['tips'],
                'duration_minutes' => $duration
            ];
        });

        // ========================================
        // 톤 및 페이스 설정 핸들러 (오버라이드)
        // ========================================

        $this->registerHandler('set_calmness_tone', function(array $params, array $context): array {
            $tone = $params['tone'] ?? 'Supportive';
            $validTones = ['Calming', 'Supportive', 'Gentle', 'Reassuring', 'Empathetic',
                          'Encouraging', 'Professional', 'Warm', 'Patient'];

            if (!in_array($tone, $validTones)) {
                $tone = 'Supportive';
            }

            return [
                'success' => true,
                'tone' => $tone,
                'tone_characteristics' => $this->getToneCharacteristics($tone)
            ];
        });

        $this->registerHandler('set_calmness_pace', function(array $params, array $context): array {
            $pace = $params['pace'] ?? 'Moderate';
            $validPaces = ['Very_Slow', 'Slow', 'Moderate', 'Normal', 'Adaptive'];

            if (!in_array($pace, $validPaces)) {
                $pace = 'Moderate';
            }

            return [
                'success' => true,
                'pace' => $pace,
                'pace_characteristics' => $this->getPaceCharacteristics($pace)
            ];
        });

        // ========================================
        // 확인 및 격려 메시지 핸들러
        // ========================================

        $this->registerHandler('send_reassurance', function(array $params, array $context): array {
            $type = $params['type'] ?? 'general';
            $calmnessLevel = $context['calmness_level'] ?? 'C85';

            $reassurances = $this->getReassuranceMessages($type, $calmnessLevel);

            return [
                'success' => true,
                'reassurance_type' => $type,
                'messages' => $reassurances,
                'calmness_level' => $calmnessLevel
            ];
        });

        $this->registerHandler('acknowledge_progress', function(array $params, array $context): array {
            $previousLevel = $params['previous_level'] ?? 'C75';
            $currentLevel = $params['current_level'] ?? 'C85';

            $improvement = $this->calculateImprovement($previousLevel, $currentLevel);

            return [
                'success' => true,
                'progress_acknowledged' => true,
                'previous_level' => $previousLevel,
                'current_level' => $currentLevel,
                'improvement' => $improvement,
                'message' => $this->getProgressMessage($improvement)
            ];
        });

        // ========================================
        // 운동 완료 핸들러
        // ========================================

        $this->registerHandler('complete_exercise', function(array $params, array $context): array {
            $exerciseType = $params['exercise_type'] ?? 'breathing';
            $exerciseName = $params['exercise_name'] ?? '';
            $duration = $params['duration'] ?? 0;
            $completed = $params['completed'] ?? true;

            // 운동 완료 로그
            $this->logCalmnessAction('exercise_completed', [
                'user_id' => $context['user_id'] ?? 0,
                'exercise_type' => $exerciseType,
                'exercise_name' => $exerciseName,
                'duration' => $duration,
                'completed' => $completed
            ]);

            return [
                'success' => true,
                'exercise_completed' => $completed,
                'exercise_type' => $exerciseType,
                'duration' => $duration,
                'message' => $completed
                    ? '잘 하셨어요! 운동을 완료했습니다.'
                    : '괜찮아요. 다음에 다시 시도해볼까요?'
            ];
        });

        // ========================================
        // 모니터링 설정 핸들러
        // ========================================

        $this->registerHandler('set_monitoring', function(array $params, array $context): array {
            $enabled = $params['enabled'] ?? true;
            $interval = $params['interval'] ?? 'hourly';
            $alerts = $params['alerts'] ?? ['crisis', 'significant_decline'];

            return [
                'success' => true,
                'monitoring' => [
                    'enabled' => $enabled,
                    'interval' => $interval,
                    'alerts' => $alerts
                ]
            ];
        });
    }

    /**
     * 침착성 레벨 계산
     *
     * @param int $score 침착성 점수
     * @return string 침착성 레벨
     */
    private function calculateCalmnessLevel(int $score): string {
        if ($score >= 95) return 'C95';
        if ($score >= 90) return 'C90';
        if ($score >= 85) return 'C85';
        if ($score >= 80) return 'C80';
        if ($score >= 75) return 'C75';
        return 'C_crisis';
    }

    /**
     * 침착성 레벨 설명 반환
     *
     * @param string $level 침착성 레벨
     * @return string 설명
     */
    private function getCalmnessLevelDescription(string $level): string {
        $descriptions = [
            'C95' => '최적의 침착 상태 - 매우 평온하고 집중력이 좋음',
            'C90' => '양호한 침착 상태 - 안정적이고 여유로움',
            'C85' => '적정 침착 상태 - 일반적인 상태',
            'C80' => '경미한 불안 - 약간의 긴장감 있음',
            'C75' => '중간 불안 - 지원이 필요할 수 있음',
            'C_crisis' => '높은 불안/위기 - 즉각적인 지원 필요'
        ];

        return $descriptions[$level] ?? '알 수 없는 상태';
    }

    /**
     * 톤 특성 반환
     *
     * @param string $tone 톤
     * @return array 특성
     */
    private function getToneCharacteristics(string $tone): array {
        $characteristics = [
            'Calming' => ['차분한', '진정시키는', '느긋한'],
            'Supportive' => ['지지적인', '함께하는', '이해하는'],
            'Gentle' => ['부드러운', '온화한', '섬세한'],
            'Reassuring' => ['안심시키는', '확신을 주는', '믿음직한'],
            'Empathetic' => ['공감적인', '이해심 깊은', '수용적인'],
            'Encouraging' => ['격려하는', '긍정적인', '희망적인'],
            'Professional' => ['전문적인', '신뢰할 수 있는', '객관적인'],
            'Warm' => ['따뜻한', '친근한', '포근한'],
            'Patient' => ['인내심 있는', '기다려주는', '여유로운']
        ];

        return $characteristics[$tone] ?? ['일반적인'];
    }

    /**
     * 페이스 특성 반환
     *
     * @param string $pace 페이스
     * @return array 특성
     */
    private function getPaceCharacteristics(string $pace): array {
        $characteristics = [
            'Very_Slow' => ['매우 천천히', '충분한 시간', '압박 없음'],
            'Slow' => ['천천히', '여유있게', '기다려줌'],
            'Moderate' => ['적당한 속도', '균형잡힌', '안정적'],
            'Normal' => ['일반적인 속도', '자연스러운', '표준적'],
            'Adaptive' => ['상황에 맞춤', '유연한', '반응적']
        ];

        return $characteristics[$pace] ?? ['일반적인'];
    }

    /**
     * 안심 메시지 반환
     *
     * @param string $type 타입
     * @param string $calmnessLevel 침착성 레벨
     * @return array 메시지 배열
     */
    private function getReassuranceMessages(string $type, string $calmnessLevel): array {
        $messages = [
            'general' => [
                'C_crisis' => [
                    '당신은 혼자가 아닙니다.',
                    '이 순간도 지나갈 거예요.',
                    '도움을 요청하는 것은 용기 있는 일입니다.'
                ],
                'C75' => [
                    '힘든 시간을 보내고 계시군요.',
                    '당신의 감정은 유효합니다.',
                    '천천히 한 걸음씩 나아가면 됩니다.'
                ],
                'default' => [
                    '잘 하고 계세요.',
                    '당신은 충분히 노력하고 있습니다.',
                    '지금 이 순간에 집중해보세요.'
                ]
            ],
            'anxiety' => [
                '불안은 자연스러운 감정이에요.',
                '이 감정은 영원하지 않습니다.',
                '당신은 이전에도 어려움을 극복했어요.'
            ],
            'stress' => [
                '쉬어가도 괜찮아요.',
                '모든 것을 완벽하게 할 필요는 없어요.',
                '자신에게 친절하게 대해주세요.'
            ]
        ];

        if ($type === 'general') {
            return $messages['general'][$calmnessLevel] ?? $messages['general']['default'];
        }

        return $messages[$type] ?? $messages['general']['default'];
    }

    /**
     * 개선도 계산
     *
     * @param string $previousLevel 이전 레벨
     * @param string $currentLevel 현재 레벨
     * @return string 개선도
     */
    private function calculateImprovement(string $previousLevel, string $currentLevel): string {
        $levelOrder = ['C_crisis' => 0, 'C75' => 1, 'C80' => 2, 'C85' => 3, 'C90' => 4, 'C95' => 5];

        $previousIndex = $levelOrder[$previousLevel] ?? 3;
        $currentIndex = $levelOrder[$currentLevel] ?? 3;

        $diff = $currentIndex - $previousIndex;

        if ($diff >= 2) return 'significant_improvement';
        if ($diff === 1) return 'mild_improvement';
        if ($diff === 0) return 'stable';
        if ($diff === -1) return 'mild_decline';
        return 'significant_decline';
    }

    /**
     * 진행 상황 메시지 반환
     *
     * @param string $improvement 개선도
     * @return string 메시지
     */
    private function getProgressMessage(string $improvement): string {
        $messages = [
            'significant_improvement' => '정말 대단해요! 크게 안정되셨네요.',
            'mild_improvement' => '좋아지고 있어요. 잘 하고 계세요!',
            'stable' => '안정적인 상태를 유지하고 계시네요.',
            'mild_decline' => '조금 힘드시군요. 함께 해결해봐요.',
            'significant_decline' => '많이 힘드시죠. 제가 도와드릴게요.'
        ];

        return $messages[$improvement] ?? '함께 나아가요.';
    }

    /**
     * 침착성 액션 로그
     *
     * @param string $action 액션명
     * @param array $data 데이터
     */
    private function logCalmnessAction(string $action, array $data): void {
        error_log("[CalmnessAction] {$action}: " . json_encode($data));
    }

    /**
     * 위기 이벤트 로그
     *
     * @param int $userId 사용자 ID
     * @param string $severity 심각도
     * @param string $type 위기 타입
     * @param array $params 추가 파라미터
     */
    private function logCrisisEvent(int $userId, string $severity, string $type, array $params): void {
        global $DB;

        try {
            $record = new \stdClass();
            $record->user_id = $userId;
            $record->agent_id = $this->agentId;
            $record->severity = $severity;
            $record->crisis_type = $type;
            $record->details = json_encode($params);
            $record->created_at = date('Y-m-d H:i:s');

            $DB->insert_record('at_crisis_logs', $record);
        } catch (\Exception $e) {
            error_log("[CalmnessActionExecutor] {$this->currentFile}:" . __LINE__ .
                " - 위기 로그 저장 실패: " . $e->getMessage());
        }
    }

    /**
     * 지원되는 침착성 액션 목록 반환
     *
     * @return array 액션 목록
     */
    public function getCalmnessActions(): array {
        return [
            // 호흡/그라운딩
            'start_breathing_exercise',
            'start_grounding_exercise',
            'complete_exercise',
            // 점수 관리
            'save_calmness_score',
            'update_calmness_level',
            // 위기 대응
            'trigger_crisis_protocol',
            'provide_crisis_resources',
            'escalate_to_professional',
            // 개입 전략
            'set_intervention_strategy',
            'apply_calmness_coaching',
            // 톤/페이스
            'set_calmness_tone',
            'set_calmness_pace',
            // 확인/격려
            'send_reassurance',
            'acknowledge_progress',
            // 모니터링
            'set_monitoring'
        ];
    }

    /**
     * 호흡 운동 목록 반환
     *
     * @return array 호흡 운동 목록
     */
    public function getBreathingExercises(): array {
        return $this->breathingExercises;
    }

    /**
     * 그라운딩 운동 목록 반환
     *
     * @return array 그라운딩 운동 목록
     */
    public function getGroundingExercises(): array {
        return $this->groundingExercises;
    }
}

/*
 * 관련 DB 테이블:
 * - at_calmness_scores (침착성 점수 저장)
 *   - id: bigint(10), PRIMARY KEY
 *   - user_id: bigint(10), 사용자 ID
 *   - agent_id: varchar(50), 에이전트 ID
 *   - score: int(3), 침착성 점수 (0-100)
 *   - level: varchar(20), 레벨 코드
 *   - metadata: text, JSON 메타데이터
 *   - created_at: datetime
 *
 * - at_crisis_logs (위기 이벤트 로그)
 *   - id: bigint(10), PRIMARY KEY
 *   - user_id: bigint(10), 사용자 ID
 *   - agent_id: varchar(50), 에이전트 ID
 *   - severity: varchar(20), 심각도
 *   - crisis_type: varchar(50), 위기 타입
 *   - details: text, JSON 상세 정보
 *   - created_at: datetime
 *
 * 참조 파일:
 * - ontology_engineering/persona_engine/impl/BaseActionExecutor.php (부모 클래스)
 * - ontology_engineering/persona_engine/core/IActionExecutor.php (인터페이스)
 */
