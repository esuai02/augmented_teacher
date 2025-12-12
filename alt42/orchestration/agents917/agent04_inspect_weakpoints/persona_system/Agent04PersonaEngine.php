<?php
/**
 * Agent04PersonaEngine - 인지관성 분석 페르소나 엔진
 *
 * 수학 문제 풀이 시 발생하는 60가지 인지관성(Cognitive Inertia) 패턴을
 * 탐지하고 맞춤형 해결 전략을 제시하는 엔진
 *
 * @package AugmentedTeacher\Agent04\PersonaSystem
 * @version 2.0.0
 * @since 2025-12-03
 */

// Moodle 환경 로드
if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}
global $DB, $USER;

class Agent04PersonaEngine
{
    /** @var string 에이전트 ID */
    private $agentId = 'agent04';

    /** @var string 버전 */
    private $version = '2.0.0';

    /** @var array 설정 데이터 */
    private $config;

    /** @var array 페르소나 목록 (id => persona) */
    private $personas = [];

    /** @var array 카테고리 목록 */
    private $categories = [];

    /** @var array 우선순위 레벨 */
    private $priorityLevels = [];

    /** @var array 키워드 매핑 (keyword => persona_ids) */
    private $keywordMap = [];

    /** @var bool 초기화 상태 */
    private $initialized = false;

    /** @var string 현재 파일 경로 */
    private $currentFile = __FILE__;

    /**
     * 생성자
     *
     * @param array $customConfig 커스텀 설정 (선택)
     */
    public function __construct(array $customConfig = [])
    {
        $this->loadConfig($customConfig);
        $this->initialize();
    }

    /**
     * 설정 로드
     */
    private function loadConfig(array $customConfig): void
    {
        $configPath = __DIR__ . '/config.php';

        if (!file_exists($configPath)) {
            throw new Exception("[Agent04PersonaEngine] config.php not found at: {$configPath}");
        }

        $this->config = require $configPath;

        // 커스텀 설정 병합
        if (!empty($customConfig)) {
            $this->config = array_replace_recursive($this->config, $customConfig);
        }
    }

    /**
     * 초기화
     */
    private function initialize(): void
    {
        // 카테고리 로드
        $this->categories = $this->config['categories'] ?? [];

        // 우선순위 레벨 로드
        $this->priorityLevels = $this->config['priority_levels'] ?? [];

        // 페르소나 로드 및 인덱싱
        $this->loadPersonas();

        // 키워드 매핑 구축
        $this->buildKeywordMap();

        $this->initialized = true;
    }

    /**
     * 페르소나 로드
     */
    private function loadPersonas(): void
    {
        $personasData = $this->config['personas'] ?? [];

        foreach ($personasData as $id => $persona) {
            $this->personas[$id] = $persona;
        }
    }

    /**
     * 키워드 매핑 구축
     * 각 페르소나의 name, desc에서 키워드를 추출하여 매핑
     */
    private function buildKeywordMap(): void
    {
        // 각 페르소나에서 키워드 추출
        foreach ($this->personas as $id => $persona) {
            // 이름에서 키워드 추출
            $nameKeywords = $this->extractKeywords($persona['name'] ?? '');
            foreach ($nameKeywords as $keyword) {
                $this->keywordMap[$keyword][] = $id;
            }

            // 설명에서 키워드 추출
            $descKeywords = $this->extractKeywords($persona['desc'] ?? '');
            foreach ($descKeywords as $keyword) {
                $this->keywordMap[$keyword][] = $id;
            }
        }

        // 중복 제거
        foreach ($this->keywordMap as $keyword => $ids) {
            $this->keywordMap[$keyword] = array_unique($ids);
        }
    }

    /**
     * 텍스트에서 의미있는 키워드 추출
     */
    private function extractKeywords(string $text): array
    {
        // 불용어 목록
        $stopwords = ['이', '가', '을', '를', '은', '는', '의', '에', '와', '과', '로', '으로',
                      '하는', '하다', '한다', '하고', '해서', '하면', '되는', '되다', '된다',
                      '있는', '있다', '없는', '없다', '같은', '같다', '때문', '대한', '위한',
                      '및', '등', '또', '더', '그', '이런', '저런', '그런', '아주', '매우'];

        // 특수문자 제거 및 공백 기준 분리
        $words = preg_split('/[\s,\.]+/', preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text));

        $keywords = [];
        foreach ($words as $word) {
            $word = trim($word);
            // 2글자 이상, 불용어 아닌 것만
            if (mb_strlen($word) >= 2 && !in_array($word, $stopwords)) {
                $keywords[] = $word;
            }
        }

        return array_unique($keywords);
    }

    // ==========================================
    // 메인 분석 기능
    // ==========================================

    /**
     * 사용자 메시지 분석 및 인지관성 패턴 감지
     *
     * @param int $userId 사용자 ID
     * @param string $message 분석할 메시지
     * @param array $context 추가 컨텍스트 (선택)
     * @return array 분석 결과
     */
    public function analyze(int $userId, string $message, array $context = []): array
    {
        $startTime = microtime(true);

        try {
            // 1. 메시지에서 인지관성 패턴 매칭
            $matchedPersonas = $this->matchPersonas($message);

            // 2. 우선순위 기반 정렬 및 Top 매칭 선정
            $topMatches = $this->rankMatches($matchedPersonas);

            // 3. 주요 매칭 결과 (가장 높은 점수)
            $primaryMatch = !empty($topMatches) ? $topMatches[0] : null;

            // 4. 솔루션 데이터 준비
            $solution = null;
            $audioUrl = null;
            if ($primaryMatch) {
                $persona = $this->personas[$primaryMatch['persona_id']];
                $solution = $persona['solution'] ?? null;
                $audioUrl = $this->getAudioUrl($primaryMatch['persona_id']);
            }

            // 5. 결과 구성
            $result = [
                'success' => true,
                'user_id' => $userId,
                'message' => $message,
                'timestamp' => date('Y-m-d H:i:s'),
                'analysis' => [
                    'detected' => !empty($topMatches),
                    'match_count' => count($topMatches),
                    'primary_match' => $primaryMatch,
                    'all_matches' => $topMatches,
                    'confidence' => $primaryMatch['score'] ?? 0,
                ],
                'persona' => $primaryMatch ? [
                    'id' => $primaryMatch['persona_id'],
                    'name' => $primaryMatch['name'],
                    'desc' => $primaryMatch['desc'],
                    'category' => $primaryMatch['category'],
                    'category_name' => $primaryMatch['category_name'],
                    'icon' => $primaryMatch['icon'],
                    'priority' => $primaryMatch['priority'],
                ] : null,
                'solution' => $solution,
                'audio' => [
                    'enabled' => $this->config['engine']['enable_audio_feedback'] ?? true,
                    'url' => $audioUrl,
                ],
                'processing_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ];

            // 6. 로그 저장
            $this->logAnalysis($userId, $message, $result);

            return $result;

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_location' => $this->currentFile . ':' . $e->getLine(),
                'user_id' => $userId,
                'timestamp' => date('Y-m-d H:i:s'),
            ];
        }
    }

    /**
     * 메시지에서 페르소나 매칭
     */
    private function matchPersonas(string $message): array
    {
        $matches = [];
        $messageKeywords = $this->extractKeywords($message);

        // 각 페르소나별 매칭 점수 계산
        foreach ($this->personas as $id => $persona) {
            $score = $this->calculateMatchScore($message, $messageKeywords, $persona);

            if ($score > 0) {
                $matches[] = [
                    'persona_id' => $id,
                    'name' => $persona['name'],
                    'desc' => $persona['desc'],
                    'category' => $persona['category'],
                    'category_name' => $persona['category_name'] ?? $this->categories[$persona['category']]['name'] ?? '',
                    'icon' => $persona['icon'],
                    'priority' => $persona['priority'],
                    'score' => $score,
                    'matched_keywords' => $this->getMatchedKeywords($messageKeywords, $persona),
                ];
            }
        }

        return $matches;
    }

    /**
     * 매칭 점수 계산
     */
    private function calculateMatchScore(string $message, array $messageKeywords, array $persona): float
    {
        $score = 0.0;

        // 1. 키워드 매칭 (기본 점수)
        $personaText = ($persona['name'] ?? '') . ' ' . ($persona['desc'] ?? '');
        $personaKeywords = $this->extractKeywords($personaText);

        $commonKeywords = array_intersect($messageKeywords, $personaKeywords);
        $keywordScore = count($commonKeywords) * 0.15;
        $score += min($keywordScore, 0.5); // 최대 0.5

        // 2. 특징 패턴 직접 매칭 (정확도 높음)
        $patterns = $this->getPersonaPatterns($persona['id'] ?? 0);
        foreach ($patterns as $pattern) {
            if (mb_stripos($message, $pattern) !== false) {
                $score += 0.25;
            }
        }

        // 3. 우선순위 가중치 적용
        $priorityWeight = $this->priorityLevels[$persona['priority']]['weight'] ?? 0.5;
        $categoryWeight = $this->categories[$persona['category']]['priority_weight'] ?? 0.5;

        $score *= ($priorityWeight * 0.3 + $categoryWeight * 0.2 + 0.5);

        return min(round($score, 3), 1.0);
    }

    /**
     * 페르소나별 특징 패턴 반환
     * (인지관성 유형별 대표적인 표현들)
     */
    private function getPersonaPatterns(int $personaId): array
    {
        // 인지관성 ID별 감지 패턴 (일부 주요 패턴)
        $patterns = [
            // 인지 과부하 (1-9)
            1 => ['떠오르면', '바로 써', '검증 없이', '생각나자마자'],
            2 => ['너무 많', '다 해야', '동시에', '정리가 안'],
            3 => ['말로 설명', '입으로', '중얼중얼'],
            4 => ['조건 분해', '분해하면', '쪼개서'],
            5 => ['메타인지', '모르는지도 몰라', '어디서 막혔는지'],
            6 => ['우선순위', '뭐부터', '순서'],
            7 => ['숫자 바꿔', '비슷한 유형', '유형별'],
            8 => ['짧은 집중', '25분', '포모도로', '뽀모도로'],
            9 => ['큰소리', '소리 내서', '따라 읽'],

            // 자신감 왜곡 (10-17)
            10 => ['쉬워 보여', '할 수 있어', '자만'],
            11 => ['어려워 보여', '못 할 것 같', '무리'],
            12 => ['운으로', '찍어서', '우연히'],
            13 => ['완벽하게', '틀리면 안 돼', '실수하면'],
            14 => ['비교', '다른 애들', '남들은'],
            15 => ['선생님 도움', '혼자서 못', '의존'],
            16 => ['감으로', '느낌으로', '직관'],
            17 => ['자신감이', '자존감', '나는 못해'],

            // 실수 패턴 (18-25)
            18 => ['사칙연산', '더하기', '빼기', '계산 실수'],
            19 => ['부호', '플러스', '마이너스', '+', '-'],
            20 => ['단위', 'cm', 'm', '환산'],
            21 => ['공식', '공식 헷갈', '공식이'],
            22 => ['베껴 쓰', '옮겨 적', '잘못 옮'],
            23 => ['문자', '숫자', '헷갈려', 'x', 'y'],
            24 => ['빠뜨', '놓쳐', '빼먹'],
            25 => ['반복 실수', '또 틀려', '같은 실수'],

            // 접근 전략 오류 (26-33)
            26 => ['공식 대입', '공식부터', '무작정 공식'],
            27 => ['그림', '도형', '시각화'],
            28 => ['거꾸로', '역추적', '답에서부터'],
            29 => ['특수값', '대입', '0', '1', '넣어보'],
            30 => ['짧은 풀이', '생략', '과정 건너뛰'],
            31 => ['어려운 방법', '복잡하게', '쉬운 방법'],
            32 => ['조건 무시', '문제 조건', '주어진 조건'],
            33 => ['막히면', '다른 방법', '새 전략'],

            // 학습 습관 (34-41)
            34 => ['대충', '넘어가', '모른 채'],
            35 => ['암기', '외우기만', '이해 없이'],
            36 => ['해설', '정답 먼저', '답지'],
            37 => ['풀었던 문제', '반복 학습', '다시 안'],
            38 => ['메모', '기록', '오답노트'],
            39 => ['피드백', '결과 확인', '분석 안'],
            40 => ['학습 루틴', '규칙적', '계획'],
            41 => ['질문', '모르는 거', '그냥 넘어'],

            // 시간/압박 관리 (42-49)
            42 => ['시간 배분', '시간이 없', '시간 부족'],
            43 => ['앞 문제', '시간 쏟', '한 문제에'],
            44 => ['긴장', '떨려', '불안'],
            45 => ['시험 압박', '평가', '성적'],
            46 => ['쉬는 시간', '휴식 없이', '계속'],
            47 => ['마감', '시간 관리', '시간 재'],
            48 => ['멘탈', '포기', '무너져'],
            49 => ['감정 조절', '화나', '짜증'],

            // 검증/확인 부재 (50-56)
            50 => ['검산', '다시 확인', '확인 안'],
            51 => ['구하는 것', '뭘 구하는지', '질문'],
            52 => ['답 형식', '단위 안', '형식'],
            53 => ['범위', '정의역', '조건 확인'],
            54 => ['경계값', '등호', '부등호'],
            55 => ['특이 케이스', '예외', '반례'],
            56 => ['논리 흐름', '논리적', '비약'],

            // 기타 장애 (57-60)
            57 => ['피곤', '졸려', '컨디션'],
            58 => ['집중', '딴 생각', '산만'],
            59 => ['환경', '소음', '방해'],
            60 => ['동기', '왜 해야', '의미 없'],
        ];

        return $patterns[$personaId] ?? [];
    }

    /**
     * 매칭된 키워드 반환
     */
    private function getMatchedKeywords(array $messageKeywords, array $persona): array
    {
        $personaText = ($persona['name'] ?? '') . ' ' . ($persona['desc'] ?? '');
        $personaKeywords = $this->extractKeywords($personaText);
        return array_values(array_intersect($messageKeywords, $personaKeywords));
    }

    /**
     * 매칭 결과 순위 정렬
     */
    private function rankMatches(array $matches): array
    {
        if (empty($matches)) {
            return [];
        }

        // 점수 기준 내림차순 정렬
        usort($matches, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        // 최소 신뢰도 필터링
        $threshold = $this->config['engine']['confidence_threshold'] ?? 0.3;
        $filtered = array_filter($matches, fn($m) => $m['score'] >= $threshold);

        // 상위 5개만 반환
        return array_slice(array_values($filtered), 0, 5);
    }

    /**
     * 오디오 URL 생성
     */
    private function getAudioUrl(int $personaId): ?string
    {
        if (!($this->config['engine']['enable_audio_feedback'] ?? true)) {
            return null;
        }

        $baseUrl = $this->config['engine']['audio_base_url'] ?? '';
        $format = $this->config['engine']['audio_format'] ?? 'wav';

        if (empty($baseUrl)) {
            return null;
        }

        return rtrim($baseUrl, '/') . '/' . $personaId . '.' . $format;
    }

    // ==========================================
    // 솔루션 제공
    // ==========================================

    /**
     * 특정 페르소나의 솔루션 반환
     */
    public function getSolution(int $personaId): ?array
    {
        if (!isset($this->personas[$personaId])) {
            return null;
        }

        $persona = $this->personas[$personaId];

        return [
            'persona_id' => $personaId,
            'persona_name' => $persona['name'],
            'category' => $persona['category'],
            'priority' => $persona['priority'],
            'solution' => $persona['solution'] ?? null,
            'audio_url' => $this->getAudioUrl($personaId),
        ];
    }

    /**
     * 카테고리별 솔루션 목록
     */
    public function getSolutionsByCategory(string $categoryKey): array
    {
        $solutions = [];

        foreach ($this->personas as $id => $persona) {
            if ($persona['category'] === $categoryKey) {
                $solutions[] = $this->getSolution($id);
            }
        }

        return $solutions;
    }

    // ==========================================
    // 페르소나 조회
    // ==========================================

    /**
     * 전체 페르소나 목록 반환
     */
    public function getAllPersonas(): array
    {
        return $this->personas;
    }

    /**
     * 특정 페르소나 반환
     */
    public function getPersona(int $id): ?array
    {
        return $this->personas[$id] ?? null;
    }

    /**
     * 카테고리별 페르소나 목록
     */
    public function getPersonasByCategory(string $categoryKey): array
    {
        return array_filter($this->personas, fn($p) => $p['category'] === $categoryKey);
    }

    /**
     * 우선순위별 페르소나 목록
     */
    public function getPersonasByPriority(string $priority): array
    {
        return array_filter($this->personas, fn($p) => $p['priority'] === $priority);
    }

    /**
     * 전체 카테고리 목록
     */
    public function getCategories(): array
    {
        return $this->categories;
    }

    /**
     * 추천 정복 순서 반환
     */
    public function getConquestOrder(): array
    {
        return $this->config['conquest_order'] ?? [];
    }

    // ==========================================
    // 사용자 히스토리
    // ==========================================

    /**
     * 사용자의 인지관성 패턴 히스토리 조회
     */
    public function getUserHistory(int $userId, int $limit = 10): array
    {
        global $DB;

        try {
            $sql = "SELECT * FROM {at_persona_log}
                    WHERE user_id = ? AND agent_id = ?
                    ORDER BY created_at DESC
                    LIMIT ?";

            $records = $DB->get_records_sql($sql, [$userId, $this->agentId, $limit]);

            $history = [];
            foreach ($records as $record) {
                $outputData = json_decode($record->output_data, true);
                $history[] = [
                    'id' => $record->id,
                    'created_at' => $record->created_at,
                    'message' => $record->input_data,
                    'persona_id' => $outputData['persona']['id'] ?? null,
                    'persona_name' => $outputData['persona']['name'] ?? null,
                    'confidence' => $outputData['analysis']['confidence'] ?? 0,
                ];
            }

            return $history;

        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * 사용자의 자주 발생하는 인지관성 패턴 통계
     */
    public function getUserPatternStats(int $userId): array
    {
        global $DB;

        try {
            $sql = "SELECT output_data FROM {at_persona_log}
                    WHERE user_id = ? AND agent_id = ?
                    ORDER BY created_at DESC
                    LIMIT 50";

            $records = $DB->get_records_sql($sql, [$userId, $this->agentId]);

            $patternCount = [];
            $categoryCount = [];

            foreach ($records as $record) {
                $data = json_decode($record->output_data, true);
                $personaId = $data['persona']['id'] ?? null;
                $category = $data['persona']['category'] ?? null;

                if ($personaId) {
                    $patternCount[$personaId] = ($patternCount[$personaId] ?? 0) + 1;
                }
                if ($category) {
                    $categoryCount[$category] = ($categoryCount[$category] ?? 0) + 1;
                }
            }

            arsort($patternCount);
            arsort($categoryCount);

            return [
                'total_analyses' => count($records),
                'top_patterns' => array_slice($patternCount, 0, 5, true),
                'category_distribution' => $categoryCount,
            ];

        } catch (Exception $e) {
            return ['total_analyses' => 0, 'top_patterns' => [], 'category_distribution' => []];
        }
    }

    // ==========================================
    // 로깅
    // ==========================================

    /**
     * 분석 결과 로그 저장
     */
    private function logAnalysis(int $userId, string $message, array $result): void
    {
        global $DB;

        if (!($this->config['system']['log_enabled'] ?? true)) {
            return;
        }

        try {
            $logData = new stdClass();
            $logData->agent_id = $this->agentId;
            $logData->user_id = $userId;
            $logData->session_id = session_id() ?: 'no-session';
            $logData->input_data = $message;
            $logData->output_data = json_encode($result, JSON_UNESCAPED_UNICODE);
            $logData->processing_time = $result['processing_time_ms'] ?? 0;
            $logData->created_at = time();

            $DB->insert_record('at_persona_log', $logData);

        } catch (Exception $e) {
            // 로그 저장 실패는 무시
            if ($this->config['system']['debug_mode'] ?? false) {
                error_log("[Agent04PersonaEngine] Log save failed: " . $e->getMessage());
            }
        }
    }

    // ==========================================
    // 유틸리티
    // ==========================================

    /**
     * 엔진 정보 반환
     */
    public function getEngineInfo(): array
    {
        return [
            'agent_id' => $this->agentId,
            'version' => $this->version,
            'initialized' => $this->initialized,
            'total_personas' => count($this->personas),
            'total_categories' => count($this->categories),
            'audio_enabled' => $this->config['engine']['enable_audio_feedback'] ?? true,
            'confidence_threshold' => $this->config['engine']['confidence_threshold'] ?? 0.3,
        ];
    }

    /**
     * 디버그 정보 반환
     */
    public function getDebugInfo(): array
    {
        return [
            'engine_info' => $this->getEngineInfo(),
            'categories' => array_keys($this->categories),
            'priority_levels' => array_keys($this->priorityLevels),
            'keyword_map_size' => count($this->keywordMap),
            'config_keys' => array_keys($this->config),
        ];
    }

    /**
     * 빠른 테스트용 분석
     */
    public function quickTest(string $message): array
    {
        return $this->analyze(0, $message);
    }
}

/**
 * 관련 DB 테이블:
 * - mdl_at_persona_log: 페르소나 분석 로그
 *   - id (int): PK
 *   - agent_id (varchar): 에이전트 ID
 *   - user_id (int): 사용자 ID
 *   - session_id (varchar): 세션 ID
 *   - input_data (text): 입력 메시지
 *   - output_data (longtext): 분석 결과 JSON
 *   - processing_time (float): 처리 시간 (ms)
 *   - created_at (int): 생성 시간
 */
