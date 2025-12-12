<?php
/**
 * CalmnessRuleParser - Agent08 전용 규칙 파서
 *
 * BaseRuleParser를 확장하여 침착성(Calmness) 관련 규칙을 파싱합니다.
 * 침착성 레벨, 개입 전략, 호흡/그라운딩 운동 설정 등을 지원합니다.
 *
 * @package AugmentedTeacher\Agent08\PersonaSystem
 * @version 1.0
 * @author Claude Code
 */

// 기본 규칙 파서 로드
require_once(__DIR__ . '/../../../../ontology_engineering/persona_engine/impl/BaseRuleParser.php');

class CalmnessRuleParser extends BaseRuleParser {

    /** @var string 현재 파일 경로 (에러 로깅용) */
    protected $currentFile = __FILE__;

    /** @var array 유효한 침착성 레벨 */
    private $validCalmnessLevels = ['C95', 'C90', 'C85', 'C80', 'C75', 'C_crisis'];

    /** @var array 유효한 개입 전략 */
    private $validInterventions = [
        'CrisisIntervention',
        'CrisisSupport',
        'SafetyNet',
        'CalmnessCoaching',
        'EmotionalSupport',
        'FocusGuidance',
        'MindfulnessSupport',
        'InformationProvision',
        'SkillBuilding',
        'ProgressRecognition'
    ];

    /** @var array 유효한 호흡 운동 타입 */
    private $validBreathingExercises = [
        '4-7-8',          // 4초 흡입, 7초 유지, 8초 배출
        'box',            // 박스 호흡 (4-4-4-4)
        'deep',           // 깊은 복식 호흡
        'calming',        // 진정 호흡
        'energizing',     // 활력 호흡
        'coherent'        // 일관성 호흡 (5-5)
    ];

    /** @var array 유효한 그라운딩 운동 타입 */
    private $validGroundingExercises = [
        '5-4-3-2-1',      // 감각 그라운딩
        'body_scan',      // 신체 스캔
        'safe_place',     // 안전한 장소 상상
        'object_focus',   // 물체 집중
        'counting',       // 카운팅 기법
        'anchoring'       // 앵커링 기법
    ];

    /** @var array 유효한 톤 설정 */
    private $validTones = [
        'Calming',        // 진정시키는
        'Supportive',     // 지지적인
        'Gentle',         // 부드러운
        'Reassuring',     // 안심시키는
        'Empathetic',     // 공감적인
        'Encouraging',    // 격려하는
        'Professional',   // 전문적인
        'Warm',           // 따뜻한
        'Patient'         // 인내심 있는
    ];

    /** @var array 유효한 페이스 설정 */
    private $validPaces = [
        'Very_Slow',      // 매우 느림 (위기 상황)
        'Slow',           // 느림 (높은 불안)
        'Moderate',       // 보통
        'Normal',         // 일반
        'Adaptive'        // 적응적
    ];

    /** @var array 위기 심각도 레벨 */
    private $crisisSeverityLevels = ['critical', 'high', 'moderate', 'low'];

    /**
     * 생성자
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * 침착성 규칙 파싱 (확장)
     *
     * @param string $filePath rules.yaml 경로
     * @return array 파싱된 규칙
     */
    public function parseRules(string $filePath): array {
        // 부모 클래스의 기본 파싱
        $rules = parent::parseRules($filePath);

        // 침착성 특화 처리
        if (isset($rules['rules'])) {
            $rules['rules'] = array_map([$this, 'processCalmnessRule'], $rules['rules']);
        }

        // 호흡 운동 설정 파싱
        if (isset($rules['breathing_exercises'])) {
            $rules['breathing_exercises'] = $this->parseBreathingExercises($rules['breathing_exercises']);
        }

        // 그라운딩 운동 설정 파싱
        if (isset($rules['grounding_exercises'])) {
            $rules['grounding_exercises'] = $this->parseGroundingExercises($rules['grounding_exercises']);
        }

        // 위기 대응 프로토콜 파싱
        if (isset($rules['crisis_protocols'])) {
            $rules['crisis_protocols'] = $this->parseCrisisProtocols($rules['crisis_protocols']);
        }

        return $rules;
    }

    /**
     * 침착성 규칙 처리
     *
     * @param array $rule 규칙 데이터
     * @return array 처리된 규칙
     */
    protected function processCalmnessRule(array $rule): array {
        // 침착성 레벨 정규화
        if (isset($rule['calmness_level'])) {
            $rule['calmness_level'] = $this->normalizeCalmnessLevel($rule['calmness_level']);
        }

        // 개입 전략 정규화
        if (isset($rule['intervention'])) {
            $rule['intervention'] = $this->normalizeIntervention($rule['intervention']);
        }

        // 톤 정규화
        if (isset($rule['tone'])) {
            $rule['tone'] = $this->normalizeTone($rule['tone']);
        }

        // 페이스 정규화
        if (isset($rule['pace'])) {
            $rule['pace'] = $this->normalizePace($rule['pace']);
        }

        // 기본값 설정
        $rule = $this->applyCalmnessDefaults($rule);

        return $rule;
    }

    /**
     * 침착성 레벨 정규화
     *
     * @param string $level 침착성 레벨
     * @return string 정규화된 레벨
     */
    protected function normalizeCalmnessLevel(string $level): string {
        $level = strtoupper(trim($level));

        // C 접두사 없으면 추가
        if (is_numeric($level)) {
            if ((int)$level >= 95) return 'C95';
            if ((int)$level >= 90) return 'C90';
            if ((int)$level >= 85) return 'C85';
            if ((int)$level >= 80) return 'C80';
            if ((int)$level >= 75) return 'C75';
            return 'C_crisis';
        }

        // 유효한 레벨인지 확인
        if (!in_array($level, $this->validCalmnessLevels)) {
            error_log("[CalmnessRuleParser] {$this->currentFile}:" . __LINE__ .
                " - 알 수 없는 침착성 레벨: {$level}, 기본값 C85 적용");
            return 'C85';
        }

        return $level;
    }

    /**
     * 개입 전략 정규화
     *
     * @param mixed $intervention 개입 전략
     * @return array 정규화된 개입 전략
     */
    protected function normalizeIntervention($intervention): array {
        if (is_string($intervention)) {
            $intervention = [$intervention];
        }

        if (!is_array($intervention)) {
            return ['InformationProvision'];
        }

        $normalized = [];
        foreach ($intervention as $item) {
            $item = trim($item);

            // 유효한 개입인지 확인
            if (in_array($item, $this->validInterventions)) {
                $normalized[] = $item;
            } else {
                // 유사한 개입 찾기
                $matched = $this->findSimilarIntervention($item);
                if ($matched) {
                    $normalized[] = $matched;
                } else {
                    error_log("[CalmnessRuleParser] {$this->currentFile}:" . __LINE__ .
                        " - 알 수 없는 개입 전략: {$item}");
                }
            }
        }

        return empty($normalized) ? ['InformationProvision'] : $normalized;
    }

    /**
     * 유사한 개입 전략 찾기
     *
     * @param string $input 입력 문자열
     * @return string|null 찾은 개입 전략
     */
    protected function findSimilarIntervention(string $input): ?string {
        $input = strtolower($input);

        $mappings = [
            'crisis' => 'CrisisIntervention',
            'emergency' => 'CrisisIntervention',
            'support' => 'EmotionalSupport',
            'emotional' => 'EmotionalSupport',
            'calm' => 'CalmnessCoaching',
            'coaching' => 'CalmnessCoaching',
            'focus' => 'FocusGuidance',
            'mindful' => 'MindfulnessSupport',
            'meditation' => 'MindfulnessSupport',
            'info' => 'InformationProvision',
            'skill' => 'SkillBuilding',
            'progress' => 'ProgressRecognition',
            'safety' => 'SafetyNet'
        ];

        foreach ($mappings as $keyword => $intervention) {
            if (strpos($input, $keyword) !== false) {
                return $intervention;
            }
        }

        return null;
    }

    /**
     * 톤 정규화
     *
     * @param string $tone 톤 설정
     * @return string 정규화된 톤
     */
    protected function normalizeTone(string $tone): string {
        $tone = ucfirst(strtolower(trim($tone)));

        if (in_array($tone, $this->validTones)) {
            return $tone;
        }

        // 유사 톤 매핑
        $mappings = [
            'soothing' => 'Calming',
            'kind' => 'Gentle',
            'understanding' => 'Empathetic',
            'positive' => 'Encouraging',
            'caring' => 'Supportive'
        ];

        $lowerTone = strtolower($tone);
        if (isset($mappings[$lowerTone])) {
            return $mappings[$lowerTone];
        }

        return 'Supportive'; // 기본값
    }

    /**
     * 페이스 정규화
     *
     * @param string $pace 페이스 설정
     * @return string 정규화된 페이스
     */
    protected function normalizePace(string $pace): string {
        // 공백을 언더스코어로 변환
        $pace = str_replace(' ', '_', ucwords(strtolower(trim($pace))));

        if (in_array($pace, $this->validPaces)) {
            return $pace;
        }

        // 유사 페이스 매핑
        $mappings = [
            'very slow' => 'Very_Slow',
            'very_slow' => 'Very_Slow',
            'slow' => 'Slow',
            'medium' => 'Moderate',
            'moderate' => 'Moderate',
            'normal' => 'Normal',
            'regular' => 'Normal',
            'adaptive' => 'Adaptive',
            'dynamic' => 'Adaptive'
        ];

        $lowerPace = strtolower(str_replace('_', ' ', $pace));
        if (isset($mappings[$lowerPace])) {
            return $mappings[$lowerPace];
        }

        return 'Moderate'; // 기본값
    }

    /**
     * 침착성 기본값 적용
     *
     * @param array $rule 규칙 데이터
     * @return array 기본값이 적용된 규칙
     */
    protected function applyCalmnessDefaults(array $rule): array {
        // 기본 침착성 레벨
        if (!isset($rule['calmness_level'])) {
            $rule['calmness_level'] = 'C85';
        }

        // 침착성 레벨에 따른 기본값 설정
        $levelDefaults = $this->getCalmnessLevelDefaults($rule['calmness_level']);

        // 개입 전략 기본값
        if (!isset($rule['intervention'])) {
            $rule['intervention'] = $levelDefaults['intervention'];
        }

        // 톤 기본값
        if (!isset($rule['tone'])) {
            $rule['tone'] = $levelDefaults['tone'];
        }

        // 페이스 기본값
        if (!isset($rule['pace'])) {
            $rule['pace'] = $levelDefaults['pace'];
        }

        // 우선순위 기본값
        if (!isset($rule['priority'])) {
            $rule['priority'] = $levelDefaults['priority'];
        }

        return $rule;
    }

    /**
     * 침착성 레벨별 기본값 반환
     *
     * @param string $level 침착성 레벨
     * @return array 기본값
     */
    protected function getCalmnessLevelDefaults(string $level): array {
        $defaults = [
            'C95' => [
                'intervention' => ['MindfulnessSupport', 'SkillBuilding'],
                'tone' => 'Encouraging',
                'pace' => 'Normal',
                'priority' => 30
            ],
            'C90' => [
                'intervention' => ['FocusGuidance', 'InformationProvision'],
                'tone' => 'Supportive',
                'pace' => 'Normal',
                'priority' => 40
            ],
            'C85' => [
                'intervention' => ['InformationProvision', 'EmotionalSupport'],
                'tone' => 'Supportive',
                'pace' => 'Moderate',
                'priority' => 50
            ],
            'C80' => [
                'intervention' => ['CalmnessCoaching', 'EmotionalSupport'],
                'tone' => 'Calming',
                'pace' => 'Slow',
                'priority' => 60
            ],
            'C75' => [
                'intervention' => ['EmotionalSupport', 'CalmnessCoaching'],
                'tone' => 'Gentle',
                'pace' => 'Slow',
                'priority' => 70
            ],
            'C_crisis' => [
                'intervention' => ['CrisisIntervention', 'SafetyNet'],
                'tone' => 'Calming',
                'pace' => 'Very_Slow',
                'priority' => 100
            ]
        ];

        return $defaults[$level] ?? $defaults['C85'];
    }

    /**
     * 호흡 운동 설정 파싱
     *
     * @param array $exercises 호흡 운동 설정
     * @return array 파싱된 설정
     */
    protected function parseBreathingExercises(array $exercises): array {
        $parsed = [];

        foreach ($exercises as $name => $config) {
            if (is_string($config)) {
                // 간단한 형식: type만 지정
                $parsed[$name] = [
                    'type' => in_array($config, $this->validBreathingExercises) ? $config : '4-7-8',
                    'duration' => 60,
                    'calmness_levels' => ['C80', 'C75', 'C_crisis']
                ];
            } elseif (is_array($config)) {
                // 상세 형식
                $parsed[$name] = [
                    'type' => isset($config['type']) && in_array($config['type'], $this->validBreathingExercises)
                        ? $config['type'] : '4-7-8',
                    'duration' => $config['duration'] ?? 60,
                    'repetitions' => $config['repetitions'] ?? 4,
                    'calmness_levels' => $config['calmness_levels'] ?? ['C80', 'C75', 'C_crisis'],
                    'instructions' => $config['instructions'] ?? null,
                    'audio_guide' => $config['audio_guide'] ?? null
                ];
            }
        }

        return $parsed;
    }

    /**
     * 그라운딩 운동 설정 파싱
     *
     * @param array $exercises 그라운딩 운동 설정
     * @return array 파싱된 설정
     */
    protected function parseGroundingExercises(array $exercises): array {
        $parsed = [];

        foreach ($exercises as $name => $config) {
            if (is_string($config)) {
                $parsed[$name] = [
                    'type' => in_array($config, $this->validGroundingExercises) ? $config : '5-4-3-2-1',
                    'duration' => 120,
                    'calmness_levels' => ['C75', 'C_crisis']
                ];
            } elseif (is_array($config)) {
                $parsed[$name] = [
                    'type' => isset($config['type']) && in_array($config['type'], $this->validGroundingExercises)
                        ? $config['type'] : '5-4-3-2-1',
                    'duration' => $config['duration'] ?? 120,
                    'steps' => $config['steps'] ?? [],
                    'calmness_levels' => $config['calmness_levels'] ?? ['C75', 'C_crisis'],
                    'instructions' => $config['instructions'] ?? null,
                    'visual_guide' => $config['visual_guide'] ?? null
                ];
            }
        }

        return $parsed;
    }

    /**
     * 위기 대응 프로토콜 파싱
     *
     * @param array $protocols 위기 프로토콜 설정
     * @return array 파싱된 설정
     */
    protected function parseCrisisProtocols(array $protocols): array {
        $parsed = [];

        foreach ($protocols as $name => $config) {
            if (!is_array($config)) {
                continue;
            }

            $parsed[$name] = [
                'severity' => isset($config['severity']) && in_array($config['severity'], $this->crisisSeverityLevels)
                    ? $config['severity'] : 'moderate',
                'triggers' => $config['triggers'] ?? [],
                'immediate_actions' => $config['immediate_actions'] ?? [],
                'escalation' => $config['escalation'] ?? null,
                'resources' => $config['resources'] ?? [],
                'follow_up' => $config['follow_up'] ?? null,
                'notification' => $config['notification'] ?? null
            ];
        }

        return $parsed;
    }

    /**
     * 규칙 유효성 검증 (확장)
     *
     * @param array $rule 검증할 규칙
     * @return array 검증 결과
     */
    public function validateRule(array $rule): array {
        // 부모 클래스의 기본 검증
        $result = parent::validateRule($rule);

        // 침착성 특화 검증
        $calmnessErrors = [];

        // 침착성 레벨 검증
        if (isset($rule['calmness_level'])) {
            if (!in_array($rule['calmness_level'], $this->validCalmnessLevels)) {
                $calmnessErrors[] = "유효하지 않은 침착성 레벨: {$rule['calmness_level']}";
            }
        }

        // 개입 전략 검증
        if (isset($rule['intervention']) && is_array($rule['intervention'])) {
            foreach ($rule['intervention'] as $intervention) {
                if (!in_array($intervention, $this->validInterventions)) {
                    $calmnessErrors[] = "유효하지 않은 개입 전략: {$intervention}";
                }
            }
        }

        // 위기 규칙의 추가 검증
        if (isset($rule['calmness_level']) && $rule['calmness_level'] === 'C_crisis') {
            if (!isset($rule['escalation_path'])) {
                $calmnessErrors[] = "위기 레벨 규칙은 escalation_path가 필요합니다";
            }
        }

        // 결과 병합
        $result['errors'] = array_merge($result['errors'], $calmnessErrors);
        $result['valid'] = empty($result['errors']);

        return $result;
    }

    /**
     * 침착성 레벨로 규칙 필터링
     *
     * @param array $rules 규칙 배열
     * @param string $calmnessLevel 침착성 레벨
     * @return array 필터링된 규칙
     */
    public function filterByCalmnessLevel(array $rules, string $calmnessLevel): array {
        $calmnessLevel = $this->normalizeCalmnessLevel($calmnessLevel);

        return array_filter($rules, function($rule) use ($calmnessLevel) {
            return isset($rule['calmness_level']) && $rule['calmness_level'] === $calmnessLevel;
        });
    }

    /**
     * 상황에 맞는 규칙 찾기
     *
     * @param array $rules 규칙 배열
     * @param string $situation 상황 코드
     * @param string|null $calmnessLevel 침착성 레벨 (선택)
     * @return array 매칭된 규칙들
     */
    public function findMatchingRules(array $rules, string $situation, ?string $calmnessLevel = null): array {
        $matched = array_filter($rules, function($rule) use ($situation, $calmnessLevel) {
            // 상황 코드 매칭
            if (isset($rule['situation']) && $rule['situation'] !== $situation) {
                return false;
            }

            // 침착성 레벨 매칭 (지정된 경우)
            if ($calmnessLevel !== null && isset($rule['calmness_level'])) {
                if ($rule['calmness_level'] !== $this->normalizeCalmnessLevel($calmnessLevel)) {
                    return false;
                }
            }

            return true;
        });

        // 우선순위로 정렬
        return $this->sortByPriority($matched);
    }

    /**
     * 규칙 템플릿 생성
     *
     * @param string $calmnessLevel 침착성 레벨
     * @param string $situation 상황 코드
     * @return array 규칙 템플릿
     */
    public function createRuleTemplate(string $calmnessLevel, string $situation): array {
        $calmnessLevel = $this->normalizeCalmnessLevel($calmnessLevel);
        $defaults = $this->getCalmnessLevelDefaults($calmnessLevel);

        return [
            'id' => 'rule_' . strtolower($calmnessLevel) . '_' . $situation . '_' . time(),
            'situation' => $situation,
            'calmness_level' => $calmnessLevel,
            'conditions' => [
                ['field' => 'calmness_score', 'operator' => 'between', 'value' => $this->getScoreRange($calmnessLevel)]
            ],
            'intervention' => $defaults['intervention'],
            'tone' => $defaults['tone'],
            'pace' => $defaults['pace'],
            'priority' => $defaults['priority'],
            'persona' => 'calmness_' . strtolower($calmnessLevel),
            'actions' => [
                ['type' => 'set_tone', 'params' => ['tone' => $defaults['tone']]],
                ['type' => 'set_pace', 'params' => ['pace' => $defaults['pace']]],
                ['type' => 'prioritize_intervention', 'params' => ['interventions' => $defaults['intervention']]]
            ],
            'enabled' => true,
            'created_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * 침착성 레벨의 점수 범위 반환
     *
     * @param string $level 침착성 레벨
     * @return array [min, max] 점수 범위
     */
    protected function getScoreRange(string $level): array {
        $ranges = [
            'C95' => [95, 100],
            'C90' => [90, 94],
            'C85' => [85, 89],
            'C80' => [80, 84],
            'C75' => [75, 79],
            'C_crisis' => [0, 74]
        ];

        return $ranges[$level] ?? [85, 89];
    }

    /**
     * 유효한 침착성 레벨 목록 반환
     *
     * @return array 침착성 레벨 목록
     */
    public function getValidCalmnessLevels(): array {
        return $this->validCalmnessLevels;
    }

    /**
     * 유효한 개입 전략 목록 반환
     *
     * @return array 개입 전략 목록
     */
    public function getValidInterventions(): array {
        return $this->validInterventions;
    }

    /**
     * 유효한 호흡 운동 타입 반환
     *
     * @return array 호흡 운동 타입 목록
     */
    public function getValidBreathingExercises(): array {
        return $this->validBreathingExercises;
    }

    /**
     * 유효한 그라운딩 운동 타입 반환
     *
     * @return array 그라운딩 운동 타입 목록
     */
    public function getValidGroundingExercises(): array {
        return $this->validGroundingExercises;
    }
}

/*
 * 관련 DB 테이블: 없음 (파일 기반 규칙 파싱)
 *
 * 참조 파일:
 * - ontology_engineering/persona_engine/impl/BaseRuleParser.php (부모 클래스)
 * - ontology_engineering/persona_engine/core/IRuleParser.php (인터페이스)
 *
 * 지원하는 규칙 형식:
 * - 침착성 레벨: C95, C90, C85, C80, C75, C_crisis
 * - 개입 전략: CrisisIntervention, CalmnessCoaching, EmotionalSupport 등
 * - 호흡 운동: 4-7-8, box, deep, calming, energizing, coherent
 * - 그라운딩: 5-4-3-2-1, body_scan, safe_place, object_focus, counting, anchoring
 * - 톤: Calming, Supportive, Gentle, Reassuring, Empathetic 등
 * - 페이스: Very_Slow, Slow, Moderate, Normal, Adaptive
 */
