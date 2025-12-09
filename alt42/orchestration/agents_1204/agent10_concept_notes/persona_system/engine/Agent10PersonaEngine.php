<?php
/**
 * Agent10PersonaEngine - 개념 노트 에이전트 페르소나 엔진 (독립형)
 *
 * Agent10(개념 노트)의 페르소나 식별 및 응답 생성 엔진
 * 외부 의존성 없이 독립적으로 동작하는 독립형(standalone) 구조
 *
 * @package AugmentedTeacher\Agents\Agent10\PersonaSystem
 * @version 1.1
 * @created 2025-12-02
 * @modified 2025-12-03
 */

// 현재 파일 정보 (에러 로깅용)
define('AGENT10_ENGINE_FILE', __FILE__);
define('AGENT10_ENGINE_DIR', __DIR__);

// Moodle 환경 로드
if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}
global $DB, $USER;

/**
 * Agent10PersonaEngine - 개념 노트 페르소나 엔진 메인 클래스
 */
class Agent10PersonaEngine {

    /** @var string 에이전트 ID */
    private $agentId = 'agent10';

    /** @var string 에이전트 기본 경로 */
    private $agentBasePath;

    /** @var array 로드된 규칙 */
    private $rules = [];

    /** @var array 로드된 페르소나 */
    private $personas = [];

    /** @var array 설정 */
    private $config = [
        'debug_mode' => false,
        'log_enabled' => true,
        'cache_enabled' => true,
        'cache_ttl' => 3600,
        'ai_enabled' => false,
        'ai_threshold' => 0.7
    ];

    /** @var array 도메인 설정 (개념 노트 특화) */
    private $domainConfig = [
        'note_metrics' => [
            'stroke_threshold_high' => 100,    // 높은 필기량 기준
            'stroke_threshold_low' => 20,       // 낮은 필기량 기준
            'recency_days_recent' => 7,         // 최근 기준 (일)
            'recency_days_old' => 30,           // 오래된 기준 (일)
            'usedtime_threshold_short' => 300,  // 짧은 사용시간 (초)
            'usedtime_threshold_long' => 1800   // 긴 사용시간 (초)
        ]
    ];

    /** @var array 상황 코드 정의 */
    private $situationCodes = [
        'N1' => '노트 탐색 시작',
        'N2' => '개념 이해도 분석',
        'N3' => '학습 흐름 해석',
        'N4' => '복습 권장 판단',
        'N5' => '노트 활용 전략'
    ];

    /**
     * 생성자
     *
     * @param array $config 추가 설정
     */
    public function __construct(array $config = []) {
        $this->agentBasePath = dirname(__DIR__);
        $this->config = array_merge($this->config, $config);

        // 도메인 설정 병합
        if (isset($config['domain_config'])) {
            $this->domainConfig = array_merge($this->domainConfig, $config['domain_config']);
        }

        // 페르소나 로드
        $this->loadPersonas();
    }

    /**
     * 규칙 파일 로드
     *
     * @param string $rulesPath rules.yaml 파일 경로
     * @return bool
     */
    public function loadRules(string $rulesPath): bool {
        if (!file_exists($rulesPath)) {
            $this->log("규칙 파일을 찾을 수 없음: {$rulesPath}", 'error');
            return false;
        }

        try {
            $content = file_get_contents($rulesPath);
            $this->rules = $this->parseYaml($content);
            $this->log("규칙 로드 완료: " . count($this->rules) . " 규칙");
            return true;
        } catch (Exception $e) {
            $this->log("규칙 로드 실패: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * 메시지 처리 및 응답 생성
     *
     * @param int $userId 사용자 ID
     * @param string $message 사용자 메시지
     * @param array $sessionData 세션 데이터
     * @return array 처리 결과
     */
    public function process(int $userId, string $message, array $sessionData = []): array {
        $startTime = microtime(true);

        try {
            // 1. 상황 판단
            $situation = $sessionData['current_situation'] ?? 'N1';

            // 2. 데이터 컨텍스트 수집
            $context = $this->buildContext($userId, $message, $sessionData);

            // 3. 페르소나 결정
            $persona = $this->determinePersona($situation, $context);

            // 4. 규칙 평가 및 액션 결정
            $actions = $this->evaluateRules($situation, $context);

            // 5. 응답 생성
            $response = $this->generateResponse($persona, $context, $actions);

            // 6. 상태 저장
            $this->savePersonaState($userId, $persona);

            $processingTime = (microtime(true) - $startTime) * 1000;

            return [
                'success' => true,
                'user_id' => $userId,
                'agent_id' => $this->agentId,
                'persona' => [
                    'persona_id' => $persona['id'],
                    'persona_name' => $persona['name'],
                    'confidence' => $persona['confidence'] ?? 0.8,
                    'tone' => $persona['tone'] ?? 'Neutral',
                    'intervention' => $persona['intervention'] ?? 'Standard'
                ],
                'response' => [
                    'text' => $response['text'],
                    'template_id' => $response['template_id'] ?? null,
                    'tone' => $response['tone'] ?? 'Neutral'
                ],
                'actions' => $actions,
                'context' => [
                    'situation' => $situation,
                    'situation_name' => $this->situationCodes[$situation] ?? '알 수 없음'
                ],
                'meta' => [
                    'processing_time_ms' => round($processingTime, 2),
                    'timestamp' => time(),
                    'debug_mode' => $this->config['debug_mode']
                ]
            ];

        } catch (Exception $e) {
            $this->log("처리 오류: " . $e->getMessage(), 'error');
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_location' => AGENT10_ENGINE_FILE . ':' . __LINE__
            ];
        }
    }

    /**
     * 컨텍스트 구성
     */
    private function buildContext(int $userId, string $message, array $sessionData): array {
        return [
            'user_id' => $userId,
            'message' => $message,
            'message_length' => mb_strlen($message),
            'message_intent' => $this->analyzeIntent($message),
            'situation' => $sessionData['current_situation'] ?? 'N1',
            'note_id' => $sessionData['note_id'] ?? null,
            'note_metrics' => $sessionData['note_metrics'] ?? null,
            'user_note_stats' => $sessionData['user_note_stats'] ?? null,
            'timestamp' => time()
        ];
    }

    /**
     * 메시지 의도 분석 (간단 버전)
     */
    private function analyzeIntent(string $message): string {
        $keywords = [
            'exploration' => ['현황', '보여', '알려', '어떤', '목록'],
            'understanding' => ['이해', '뭐야', '설명', '개념', '의미'],
            'flow' => ['패턴', '흐름', '습관', '언제', '얼마나'],
            'review' => ['복습', '다시', '오래된', '잊어버', '기억'],
            'strategy' => ['전략', '방법', '활용', '효과적', '추천']
        ];

        foreach ($keywords as $intent => $words) {
            foreach ($words as $word) {
                if (mb_strpos($message, $word) !== false) {
                    return $intent;
                }
            }
        }

        return 'general';
    }

    /**
     * 페르소나 결정
     */
    private function determinePersona(string $situation, array $context): array {
        // 상황에 맞는 페르소나 후보 선택
        $candidates = array_filter($this->personas, function($p) use ($situation) {
            return strpos($p['id'], $situation) === 0;
        });

        if (empty($candidates)) {
            // 기본 페르소나 반환
            return [
                'id' => $situation . '_P1',
                'name' => '기본 가이드',
                'confidence' => 0.5,
                'tone' => 'Neutral',
                'intervention' => 'Standard'
            ];
        }

        // 컨텍스트 기반 점수 계산
        $scored = [];
        foreach ($candidates as $persona) {
            $score = $this->scorePersona($persona, $context);
            $scored[] = array_merge($persona, ['confidence' => $score]);
        }

        // 최고 점수 페르소나 선택
        usort($scored, function($a, $b) {
            return $b['confidence'] <=> $a['confidence'];
        });

        return $scored[0];
    }

    /**
     * 페르소나 점수 계산
     */
    private function scorePersona(array $persona, array $context): float {
        $score = 0.5; // 기본 점수

        // 노트 메트릭 기반 점수 조정
        $noteMetrics = $context['note_metrics'] ?? null;
        $userStats = $context['user_note_stats'] ?? null;

        if ($noteMetrics) {
            $strokeCount = $noteMetrics['stroke_count'] ?? 0;
            $daysSinceStroke = $noteMetrics['days_since_last_stroke'] ?? 0;

            // 필기량 기반
            if ($strokeCount > $this->domainConfig['note_metrics']['stroke_threshold_high']) {
                $score += 0.1; // 활발한 학습자
            } elseif ($strokeCount < $this->domainConfig['note_metrics']['stroke_threshold_low']) {
                $score += 0.05; // 격려 필요
            }

            // 최신성 기반
            if ($daysSinceStroke > $this->domainConfig['note_metrics']['recency_days_old']) {
                $score += 0.15; // 복습 필요
            }
        }

        if ($userStats) {
            $totalNotes = $userStats['total_notes'] ?? 0;
            if ($totalNotes > 10) {
                $score += 0.1; // 경험 있는 사용자
            }
        }

        // 메시지 의도와 페르소나 특성 매칭
        $intent = $context['message_intent'] ?? 'general';
        $personaTraits = $persona['traits'] ?? [];

        if (in_array($intent, $personaTraits)) {
            $score += 0.2;
        }

        return min(1.0, $score);
    }

    /**
     * 규칙 평가
     */
    private function evaluateRules(string $situation, array $context): array {
        $matchedActions = [];

        if (empty($this->rules)) {
            return ['recommend_exploration'];
        }

        // 상황별 규칙 필터링
        $situationRules = $this->rules[$situation] ?? [];

        foreach ($situationRules as $rule) {
            if ($this->evaluateCondition($rule['condition'] ?? [], $context)) {
                $matchedActions = array_merge($matchedActions, $rule['actions'] ?? []);
            }
        }

        return array_unique($matchedActions);
    }

    /**
     * 조건 평가
     */
    private function evaluateCondition(array $condition, array $context): bool {
        if (empty($condition)) {
            return true;
        }

        foreach ($condition as $key => $expected) {
            $actual = $context[$key] ?? null;

            if (is_array($expected)) {
                if (isset($expected['gt']) && !($actual > $expected['gt'])) return false;
                if (isset($expected['lt']) && !($actual < $expected['lt'])) return false;
                if (isset($expected['gte']) && !($actual >= $expected['gte'])) return false;
                if (isset($expected['lte']) && !($actual <= $expected['lte'])) return false;
                if (isset($expected['in']) && !in_array($actual, $expected['in'])) return false;
            } else {
                if ($actual !== $expected) return false;
            }
        }

        return true;
    }

    /**
     * 응답 생성
     */
    private function generateResponse(array $persona, array $context, array $actions): array {
        $situation = $context['situation'];
        $templatePath = $this->agentBasePath . '/templates/' . $situation . '/';

        // 상황별 템플릿 선택
        $templateMap = [
            'N1' => 'note_exploration_start.txt',
            'N2' => 'concept_understanding.txt',
            'N3' => 'learning_flow.txt',
            'N4' => 'review_recommendation.txt',
            'N5' => 'note_strategy.txt'
        ];

        $templateFile = $templatePath . ($templateMap[$situation] ?? 'default.txt');

        // 템플릿 로드
        if (file_exists($templateFile)) {
            $template = file_get_contents($templateFile);
            $text = $this->renderTemplate($template, $persona, $context);
        } else {
            // 기본 응답
            $text = $this->generateDefaultResponse($persona, $context);
        }

        return [
            'text' => $text,
            'template_id' => $templateMap[$situation] ?? 'default',
            'tone' => $persona['tone'] ?? 'Neutral'
        ];
    }

    /**
     * 템플릿 렌더링
     */
    private function renderTemplate(string $template, array $persona, array $context): string {
        $userStats = $context['user_note_stats'] ?? [];
        $noteMetrics = $context['note_metrics'] ?? [];

        // 변수 매핑
        $variables = [
            '{{student_name}}' => $this->getStudentName($context['user_id']),
            '{{total_notes}}' => $userStats['total_notes'] ?? 0,
            '{{recent_notes_count}}' => $userStats['recent_notes_count'] ?? 0,
            '{{old_notes_count}}' => $userStats['old_notes_count'] ?? 0,
            '{{last_note_date}}' => $userStats['last_note_date'] ?? '없음',
            '{{top_topic}}' => $userStats['top_topic'] ?? '미정',
            '{{avg_strokes}}' => round($userStats['avg_strokes'] ?? 0, 1),
            '{{total_strokes}}' => $userStats['total_strokes'] ?? 0,
            '{{stroke_count}}' => $noteMetrics['stroke_count'] ?? 0,
            '{{used_time}}' => round(($noteMetrics['used_time'] ?? 0) / 60, 1) . '분',
            '{{days_since_last_stroke}}' => $noteMetrics['days_since_last_stroke'] ?? '알 수 없음',
            '{{persona_name}}' => $persona['name'] ?? '코칭 가이드',
            '{{persona_specific_guidance}}' => $this->getPersonaGuidance($persona, $context),
            '{{recommendation}}' => $this->getRecommendation($context),
            '{{encouragement_message}}' => $this->getEncouragement($persona)
        ];

        // 기본 섹션 처리 (빈 값 제거)
        $text = str_replace(array_keys($variables), array_values($variables), $template);

        // [SECTION] 마커 제거
        $text = preg_replace('/\[([A-Z_]+)\]\s*/', '', $text);

        // 빈 줄 정리
        $text = preg_replace('/\n{3,}/', "\n\n", trim($text));

        return $text;
    }

    /**
     * 기본 응답 생성
     */
    private function generateDefaultResponse(array $persona, array $context): string {
        $studentName = $this->getStudentName($context['user_id']);
        $situation = $context['situation'];
        $situationName = $this->situationCodes[$situation] ?? '학습';

        return "{$studentName}님, 안녕하세요! 개념노트 코칭 {$persona['name']}입니다.\n\n" .
               "현재 '{$situationName}' 단계에 있습니다.\n" .
               "질문이 있으시면 편하게 말씀해 주세요.";
    }

    /**
     * 학생 이름 조회
     */
    private function getStudentName(int $userId): string {
        global $DB;

        try {
            $user = $DB->get_record('user', ['id' => $userId], 'firstname, lastname');
            if ($user) {
                return trim($user->firstname . ' ' . $user->lastname);
            }
        } catch (Exception $e) {
            $this->log("사용자 조회 실패: " . $e->getMessage(), 'warning');
        }

        return '학생';
    }

    /**
     * 페르소나별 가이던스 생성
     */
    private function getPersonaGuidance(array $persona, array $context): string {
        $guidanceMap = [
            'N1_P1' => '함께 노트 현황을 살펴보고, 어디서부터 시작하면 좋을지 찾아볼까요?',
            'N1_P2' => '노트 데이터를 분석해서 학습 효율을 높일 방법을 찾아드릴게요.',
            'N1_P3' => '이미 열심히 노력해오셨네요! 그 노력이 좋은 결과로 이어질 거예요.',
            'N1_P4' => '체계적인 노트 관리 전략을 세워봐요.',
            'N2_P1' => '개념을 깊이 이해하면 응용력이 높아져요.',
            'N2_P2' => '여러 개념들 사이의 연결고리를 찾아볼까요?',
            'N2_P3' => '스스로 질문을 던지는 것이 이해의 시작이에요.',
            'N2_P4' => '개념의 구조를 파악하면 기억에 오래 남아요.',
            'N3_P1' => '학습 패턴을 분석해서 최적의 루틴을 찾아드릴게요.',
            'N3_P2' => '일정한 리듬으로 학습하면 효과가 배가 돼요.',
            'N3_P3' => '집중력이 높은 시간대를 활용해봐요.',
            'N3_P4' => '시간 관리가 학습 효율의 핵심이에요.',
            'N4_P1' => '잊어버리기 전에 복습하면 기억이 강화돼요.',
            'N4_P2' => '중요한 것부터 우선순위를 정해서 복습해봐요.',
            'N4_P3' => '복습은 귀찮지만, 확실히 효과가 있어요!',
            'N4_P4' => '간격 반복 학습으로 장기 기억을 만들어봐요.',
            'N5_P1' => '노트를 최대한 활용하는 방법을 알려드릴게요.',
            'N5_P2' => '여러 노트를 연결하면 통찰력이 생겨요.',
            'N5_P3' => '노트를 확장하고 발전시켜봐요.',
            'N5_P4' => '체계적인 노트 시스템을 구축해봐요.'
        ];

        return $guidanceMap[$persona['id']] ?? '함께 학습을 진행해봐요.';
    }

    /**
     * 추천 메시지 생성
     */
    private function getRecommendation(array $context): string {
        $noteStats = $context['user_note_stats'] ?? [];
        $oldNotes = $noteStats['old_notes_count'] ?? 0;

        if ($oldNotes > 5) {
            return "복습이 필요한 노트가 {$oldNotes}개 있어요. 함께 복습 계획을 세워볼까요?";
        }

        $totalNotes = $noteStats['total_notes'] ?? 0;
        if ($totalNotes < 3) {
            return "아직 노트가 적네요. 새로운 개념을 정리해보는 건 어떨까요?";
        }

        return "꾸준히 학습하고 계시네요! 현재 페이스를 유지해봐요.";
    }

    /**
     * 격려 메시지 생성
     */
    private function getEncouragement(array $persona): string {
        $messages = [
            '작은 노력이 쌓여 큰 성장이 됩니다.',
            '오늘도 한 걸음 더 나아갔어요!',
            '꾸준함이 실력입니다.',
            '포기하지 않는 것이 가장 중요해요.',
            '노트 정리는 미래의 나에게 주는 선물이에요.'
        ];

        return $messages[array_rand($messages)];
    }

    /**
     * 페르소나 상태 저장
     */
    private function savePersonaState(int $userId, array $persona): void {
        global $DB;

        try {
            $existing = $DB->get_record('at_agent_persona_state', [
                'userid' => $userId,
                'agent_id' => $this->agentId
            ]);

            $stateData = json_encode([
                'persona_id' => $persona['id'],
                'persona_name' => $persona['name'],
                'last_interaction' => time()
            ], JSON_UNESCAPED_UNICODE);

            if ($existing) {
                $DB->update_record('at_agent_persona_state', [
                    'id' => $existing->id,
                    'persona_id' => $persona['id'],
                    'state_data' => $stateData,
                    'timemodified' => time()
                ]);
            } else {
                $DB->insert_record('at_agent_persona_state', [
                    'userid' => $userId,
                    'agent_id' => $this->agentId,
                    'persona_id' => $persona['id'],
                    'state_data' => $stateData,
                    'timecreated' => time(),
                    'timemodified' => time()
                ]);
            }
        } catch (Exception $e) {
            $this->log("상태 저장 실패: " . $e->getMessage(), 'warning');
        }
    }

    /**
     * 페르소나 로드
     */
    private function loadPersonas(): void {
        $personasPath = $this->agentBasePath . '/personas.md';

        if (file_exists($personasPath)) {
            $content = file_get_contents($personasPath);
            $this->personas = $this->parsePersonasFromMarkdown($content);
        } else {
            $this->personas = $this->getDefaultPersonas();
        }
    }

    /**
     * 마크다운에서 페르소나 파싱
     */
    private function parsePersonasFromMarkdown(string $content): array {
        $personas = [];

        // ### N1_P1: 친근한 가이드 형식 파싱
        preg_match_all('/###\s+(N\d_P\d):\s*(.+?)(?=###|\z)/s', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $id = trim($match[1]);
            $block = $match[2];

            // 이름 추출
            $name = trim(strtok($block, "\n"));

            // 톤 추출
            preg_match('/톤:\s*(.+)/u', $block, $toneMatch);
            $tone = isset($toneMatch[1]) ? trim($toneMatch[1]) : 'Neutral';

            // 개입 방식 추출
            preg_match('/개입\s*방식:\s*(.+)/u', $block, $interventionMatch);
            $intervention = isset($interventionMatch[1]) ? trim($interventionMatch[1]) : 'Standard';

            // 특성 추출
            preg_match('/특성:\s*(.+)/u', $block, $traitsMatch);
            $traits = isset($traitsMatch[1]) ? array_map('trim', explode(',', $traitsMatch[1])) : [];

            $personas[$id] = [
                'id' => $id,
                'name' => $name,
                'tone' => $tone,
                'intervention' => $intervention,
                'traits' => $traits
            ];
        }

        return $personas;
    }

    /**
     * 기본 페르소나 정의
     */
    private function getDefaultPersonas(): array {
        return [
            'N1_P1' => ['id' => 'N1_P1', 'name' => '친근한 가이드', 'tone' => 'Friendly', 'intervention' => 'Proactive', 'traits' => ['exploration']],
            'N1_P2' => ['id' => 'N1_P2', 'name' => '효율 분석가', 'tone' => 'Analytical', 'intervention' => 'Data-driven', 'traits' => ['exploration']],
            'N1_P3' => ['id' => 'N1_P3', 'name' => '격려 전문가', 'tone' => 'Encouraging', 'intervention' => 'Supportive', 'traits' => ['exploration']],
            'N1_P4' => ['id' => 'N1_P4', 'name' => '전략가', 'tone' => 'Strategic', 'intervention' => 'Advisory', 'traits' => ['strategy']],
            'N2_P1' => ['id' => 'N2_P1', 'name' => '깊이 있는 이해자', 'tone' => 'Analytical', 'intervention' => 'InformationProvision', 'traits' => ['understanding']],
            'N2_P2' => ['id' => 'N2_P2', 'name' => '연결 해석자', 'tone' => 'Insightful', 'intervention' => 'ConnectionBuilding', 'traits' => ['understanding']],
            'N2_P3' => ['id' => 'N2_P3', 'name' => '질문 유도자', 'tone' => 'Curious', 'intervention' => 'QuestionGuided', 'traits' => ['understanding']],
            'N2_P4' => ['id' => 'N2_P4', 'name' => '구조 설계자', 'tone' => 'Systematic', 'intervention' => 'StructureBuilding', 'traits' => ['strategy']],
            'N3_P1' => ['id' => 'N3_P1', 'name' => '패턴 분석가', 'tone' => 'Analytical', 'intervention' => 'PatternRecognition', 'traits' => ['flow']],
            'N3_P2' => ['id' => 'N3_P2', 'name' => '리듬 코치', 'tone' => 'Motivating', 'intervention' => 'RhythmBuilding', 'traits' => ['flow']],
            'N3_P3' => ['id' => 'N3_P3', 'name' => '집중 전문가', 'tone' => 'Focused', 'intervention' => 'FocusEnhancement', 'traits' => ['flow']],
            'N3_P4' => ['id' => 'N3_P4', 'name' => '시간 설계자', 'tone' => 'Practical', 'intervention' => 'TimeManagement', 'traits' => ['strategy']],
            'N4_P1' => ['id' => 'N4_P1', 'name' => '기억 보조자', 'tone' => 'Supportive', 'intervention' => 'MemoryEnhancement', 'traits' => ['review']],
            'N4_P2' => ['id' => 'N4_P2', 'name' => '우선순위 전문가', 'tone' => 'Organized', 'intervention' => 'Prioritization', 'traits' => ['review']],
            'N4_P3' => ['id' => 'N4_P3', 'name' => '복습 동기부여자', 'tone' => 'Encouraging', 'intervention' => 'MotivationBoost', 'traits' => ['review']],
            'N4_P4' => ['id' => 'N4_P4', 'name' => '간격 반복 코치', 'tone' => 'Methodical', 'intervention' => 'SpacedRepetition', 'traits' => ['strategy']],
            'N5_P1' => ['id' => 'N5_P1', 'name' => '활용 최적화자', 'tone' => 'Practical', 'intervention' => 'Optimization', 'traits' => ['strategy']],
            'N5_P2' => ['id' => 'N5_P2', 'name' => '연결 전문가', 'tone' => 'Insightful', 'intervention' => 'ConnectionBuilding', 'traits' => ['strategy']],
            'N5_P3' => ['id' => 'N5_P3', 'name' => '확장 안내자', 'tone' => 'Exploratory', 'intervention' => 'ExpansionGuide', 'traits' => ['strategy']],
            'N5_P4' => ['id' => 'N5_P4', 'name' => '통합 설계자', 'tone' => 'Comprehensive', 'intervention' => 'SystemIntegration', 'traits' => ['strategy']]
        ];
    }

    /**
     * YAML 간이 파서 (spyc 없이)
     */
    private function parseYaml(string $content): array {
        // 간단한 YAML 파싱 (기본 구조만)
        $result = [];
        $lines = explode("\n", $content);
        $currentSection = null;
        $currentRule = null;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // 주석이나 빈 줄 무시
            if (empty($trimmed) || strpos($trimmed, '#') === 0) {
                continue;
            }

            // 섹션 헤더 (N1:, N2: 등)
            if (preg_match('/^(N\d):/', $trimmed, $match)) {
                $currentSection = $match[1];
                $result[$currentSection] = [];
            }
            // 규칙 이름
            elseif (preg_match('/^\s*-\s*name:\s*(.+)/', $line, $match)) {
                $currentRule = ['name' => trim($match[1]), 'condition' => [], 'actions' => []];
                if ($currentSection) {
                    $result[$currentSection][] = &$currentRule;
                }
            }
            // 액션
            elseif (preg_match('/^\s*-\s*(action|recommend|trigger):\s*(.+)/', $line, $match)) {
                if ($currentRule !== null) {
                    $currentRule['actions'][] = trim($match[2]);
                }
            }
        }

        return $result;
    }

    /**
     * 로그 기록
     */
    private function log(string $message, string $level = 'info'): void {
        if (!$this->config['log_enabled']) {
            return;
        }

        $logMessage = "[Agent10PersonaEngine][{$level}] {$message} - " . AGENT10_ENGINE_FILE . ":" . __LINE__;

        if ($level === 'error') {
            error_log($logMessage);
        } elseif ($this->config['debug_mode']) {
            error_log($logMessage);
        }
    }
}

/*
 * 파일: agent10_concept_notes/persona_system/engine/Agent10PersonaEngine.php
 * 버전: 1.1 (독립형 구조)
 *
 * 관련 DB 테이블:
 * - local_augteacher_notes
 *   - id: bigint(10) PRIMARY KEY
 *   - userid: bigint(10) NOT NULL
 *   - nstroke: int(10) - 필기 획 수
 *   - tlaststroke: bigint(10) - 마지막 필기 시간
 *   - usedtime: int(10) - 사용 시간 (초)
 *
 * - at_agent_persona_state
 *   - id: bigint(10) PRIMARY KEY
 *   - userid: bigint(10) NOT NULL
 *   - agent_id: varchar(50) NOT NULL
 *   - persona_id: varchar(50) NOT NULL
 *   - state_data: longtext
 *   - timecreated: bigint(10)
 *   - timemodified: bigint(10)
 */
