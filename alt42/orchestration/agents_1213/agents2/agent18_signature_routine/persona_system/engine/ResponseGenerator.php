<?php
/**
 * Agent18 Signature Routine - Response Generator
 *
 * 컨텍스트와 페르소나 기반 응답 생성.
 *
 * @package Agent18_SignatureRoutine
 * @version 1.0
 * @created 2025-12-02
 *
 * File: /alt42/orchestration/agents/agent18_signature_routine/persona_system/engine/ResponseGenerator.php
 */

class ResponseGenerator {

    /** @var string 템플릿 경로 */
    private $templatePath;

    /** @var array 톤 설정 */
    private $toneSettings = [];

    /** @var array 기본 템플릿 */
    private $defaultTemplates = [];

    /**
     * 생성자
     *
     * @param string $templatePath 템플릿 폴더 경로
     */
    public function __construct($templatePath) {
        $this->templatePath = rtrim($templatePath, '/') . '/';
        $this->initializeToneSettings();
        $this->initializeDefaultTemplates();
    }

    /**
     * 톤 설정 초기화
     */
    private function initializeToneSettings() {
        $this->toneSettings = [
            'friendly_exploratory' => [
                'greeting' => ['안녕!', '반가워요!', '오늘도 열심히!'],
                'endings' => ['어떻게 생각해?', '한번 해볼까?', '궁금하지 않아?'],
                'encouragement' => ['대단해요!', '잘하고 있어요!', '멋져요!'],
                'emoji' => true
            ],
            'supportive_warm' => [
                'greeting' => ['안녕하세요~', '오늘 하루는 어땠어요?'],
                'endings' => ['천천히 해봐요~', '언제든 물어봐요~'],
                'encouragement' => ['정말 잘하고 있어요~', '꾸준함이 빛나요~'],
                'emoji' => true
            ],
            'analytical_insightful' => [
                'greeting' => ['안녕하세요.', '분석 결과를 알려드릴게요.'],
                'endings' => ['데이터가 보여주고 있어요.', '추천드립니다.'],
                'encouragement' => ['효과적인 패턴입니다.', '최적화되어 있네요.'],
                'emoji' => false
            ],
            'motivational_energetic' => [
                'greeting' => ['화이팅!', '오늘도 달려볼까요!'],
                'endings' => ['해낼 수 있어요!', '목표를 향해!'],
                'encouragement' => ['대단해요! 👏', '최고예요! 🔥'],
                'emoji' => true
            ],
            'calm_reflective' => [
                'greeting' => ['안녕하세요.', '오늘 학습은 어떠셨나요?'],
                'endings' => ['천천히 생각해 보세요.', '자신만의 속도로 가면 돼요.'],
                'encouragement' => ['잘하고 계세요.', '의미 있는 시간이었네요.'],
                'emoji' => false
            ]
        ];
    }

    /**
     * 기본 템플릿 초기화
     */
    private function initializeDefaultTemplates() {
        $this->defaultTemplates = [
            // 시그너처 루틴 컨텍스트
            'SR01' => [
                'title' => '루틴 분석 시작',
                'template' => "{{greeting}}\n\n학습 루틴을 분석해 볼게요! 📊\n\n지금까지 {{session_count}}번의 학습 세션이 기록되어 있어요.\n{{analysis_intro}}\n\n{{ending}}"
            ],
            'SR02' => [
                'title' => '패턴 발견',
                'template' => "{{greeting}}\n\n흥미로운 패턴을 발견했어요! ✨\n\n{{pattern_description}}\n\n이런 루틴이 당신에게 잘 맞는 것 같아요.\n\n{{recommendation}}\n\n{{ending}}"
            ],
            'SR03' => [
                'title' => '시그너처 루틴 완성',
                'template' => "{{greeting}}\n\n🎯 당신만의 시그너처 루틴을 찾았어요!\n\n**최적 학습 시간**: {{optimal_time}}\n**추천 세션 길이**: {{optimal_duration}}\n**휴식 패턴**: {{break_pattern}}\n**베스트 요일**: {{best_day}}\n\n{{recommendation}}\n\n{{ending}}"
            ],

            // 시간 패턴 컨텍스트
            'TP01' => [
                'title' => '시간대별 분석',
                'template' => "{{greeting}}\n\n시간대별 학습 효율을 분석해봤어요 ⏰\n\n{{time_analysis}}\n\n{{recommendation}}\n\n{{ending}}"
            ],
            'TP02' => [
                'title' => '골든타임 발견',
                'template' => "{{greeting}}\n\n🌟 당신의 골든타임을 발견했어요!\n\n**{{golden_time}}**에 학습할 때 성과가 {{performance_ratio}}% 더 좋아요!\n\n이 시간대를 최대한 활용해 보세요.\n\n{{ending}}"
            ],
            'TP03' => [
                'title' => '새벽 학습 제안',
                'template' => "{{greeting}}\n\n일찍 시작하는 것을 좋아하시네요! 🌅\n\n아침 시간대 학습 효율이 높게 나타나고 있어요.\n\n{{recommendation}}\n\n{{ending}}"
            ],

            // 피드백 컨텍스트
            'FO01' => [
                'title' => '루틴 추천',
                'template' => "{{greeting}}\n\n맞춤 루틴을 추천해 드릴게요! 📋\n\n{{routine_suggestion}}\n\n{{ending}}"
            ],
            'FO02' => [
                'title' => '개선 제안',
                'template' => "{{greeting}}\n\n학습 효율을 더 높일 수 있는 방법을 찾았어요! 💡\n\n{{improvement_suggestions}}\n\n{{ending}}"
            ],

            // 기본
            'DEFAULT' => [
                'title' => '일반 응답',
                'template' => "{{greeting}}\n\n{{message}}\n\n{{ending}}"
            ]
        ];
    }

    /**
     * 응답 생성
     *
     * @param array $params 파라미터
     * @return array 응답 데이터
     */
    public function generate($params) {
        $context = $params['context'] ?? 'DEFAULT';
        $tone = $params['tone'] ?? 'friendly_exploratory';
        $persona = $params['persona'] ?? null;
        $routineData = $params['routine_data'] ?? [];
        $recommendation = $params['recommendation'] ?? [];
        $userMessage = $params['user_message'] ?? '';

        // 템플릿 로드
        $template = $this->loadTemplate($context);

        // 톤 설정 적용
        $toneConfig = $this->toneSettings[$tone] ?? $this->toneSettings['friendly_exploratory'];

        // 변수 준비
        $variables = $this->prepareVariables($context, $routineData, $recommendation, $toneConfig);

        // 템플릿 렌더링
        $content = $this->renderTemplate($template['template'], $variables);

        // 이모지 처리
        if (!$toneConfig['emoji']) {
            $content = $this->removeEmojis($content);
        }

        return [
            'content' => $content,
            'context' => $context,
            'tone' => $tone,
            'persona' => $persona,
            'template_used' => $template['title'],
            'generated_at' => time()
        ];
    }

    /**
     * 템플릿 로드
     *
     * @param string $context 컨텍스트
     * @return array 템플릿 데이터
     */
    private function loadTemplate($context) {
        // 파일 기반 템플릿 확인
        $templateFile = $this->templatePath . $context . '.php';
        if (file_exists($templateFile)) {
            $template = include $templateFile;
            if (is_array($template)) {
                return $template;
            }
        }

        // 기본 템플릿 사용
        return $this->defaultTemplates[$context] ?? $this->defaultTemplates['DEFAULT'];
    }

    /**
     * 변수 준비
     *
     * @param string $context 컨텍스트
     * @param array $routineData 루틴 데이터
     * @param array $recommendation 추천 데이터
     * @param array $toneConfig 톤 설정
     * @return array 변수 목록
     */
    private function prepareVariables($context, $routineData, $recommendation, $toneConfig) {
        // 기본 변수
        $variables = [
            'greeting' => $toneConfig['greeting'][array_rand($toneConfig['greeting'])],
            'ending' => $toneConfig['endings'][array_rand($toneConfig['endings'])],
            'encouragement' => $toneConfig['encouragement'][array_rand($toneConfig['encouragement'])],
            'session_count' => $routineData['session_count'] ?? 0
        ];

        // 시그너처 루틴 변수
        if (isset($routineData['signature_routine'])) {
            $sr = $routineData['signature_routine'];
            $variables['optimal_time'] = $sr['optimal_time'] ?? '미확인';
            $variables['optimal_duration'] = $sr['optimal_duration'] ?? '45분';
            $variables['break_pattern'] = $this->formatBreakPattern($sr['break_pattern'] ?? 'moderate_breaks');
            $variables['best_day'] = $sr['best_day'] ?? '미확인';
        }

        // 골든타임 변수
        if (isset($routineData['golden_time'])) {
            $gt = $routineData['golden_time'];
            $variables['golden_time'] = $gt['slot_name'] ?? '미확인';
            $variables['performance_ratio'] = isset($gt['avg_score']) ?
                round($gt['avg_score'] * 1.3, 0) : 0;
        }

        // 시간 분석 변수
        if (isset($routineData['time_patterns'])) {
            $variables['time_analysis'] = $this->formatTimeAnalysis($routineData['time_patterns']);
        }

        // 추천 변수
        if (isset($recommendation['primary'])) {
            $variables['recommendation'] = $recommendation['primary'];
        }

        if (isset($recommendation['routine_suggestion'])) {
            $variables['routine_suggestion'] = $this->formatRoutineSuggestion($recommendation['routine_suggestion']);
        }

        // 컨텍스트별 추가 변수
        $variables = $this->addContextSpecificVariables($context, $variables, $routineData);

        return $variables;
    }

    /**
     * 컨텍스트별 추가 변수 생성
     *
     * @param string $context 컨텍스트
     * @param array $variables 기존 변수
     * @param array $routineData 루틴 데이터
     * @return array 업데이트된 변수
     */
    private function addContextSpecificVariables($context, $variables, $routineData) {
        switch ($context) {
            case 'SR01':
                $sessionCount = $routineData['session_count'] ?? 0;
                if ($sessionCount < 5) {
                    $variables['analysis_intro'] = "아직 데이터가 조금 부족해요. 몇 번 더 학습하면 더 정확한 분석이 가능해요!";
                } elseif ($sessionCount < 15) {
                    $variables['analysis_intro'] = "패턴이 조금씩 보이기 시작해요. 조금만 더 모으면 시그너처 루틴을 찾을 수 있어요!";
                } else {
                    $variables['analysis_intro'] = "충분한 데이터가 모였어요! 당신만의 학습 패턴을 분석해 볼게요.";
                }
                break;

            case 'SR02':
                $variables['pattern_description'] = $this->generatePatternDescription($routineData);
                break;

            case 'FO02':
                $variables['improvement_suggestions'] = $this->generateImprovementSuggestions($routineData);
                break;
        }

        return $variables;
    }

    /**
     * 템플릿 렌더링
     *
     * @param string $template 템플릿 문자열
     * @param array $variables 변수
     * @return string 렌더링된 문자열
     */
    private function renderTemplate($template, $variables) {
        $result = $template;

        foreach ($variables as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $result = str_replace('{{' . $key . '}}', $value, $result);
            }
        }

        // 미사용 플레이스홀더 제거
        $result = preg_replace('/\{\{[^}]+\}\}/', '', $result);

        return trim($result);
    }

    /**
     * 이모지 제거
     *
     * @param string $text 텍스트
     * @return string 이모지 제거된 텍스트
     */
    private function removeEmojis($text) {
        return preg_replace('/[\x{1F600}-\x{1F64F}]|[\x{1F300}-\x{1F5FF}]|[\x{1F680}-\x{1F6FF}]|[\x{1F1E0}-\x{1F1FF}]|[\x{2600}-\x{26FF}]|[\x{2700}-\x{27BF}]/u', '', $text);
    }

    /**
     * 휴식 패턴 포맷팅
     *
     * @param string $breakPattern 휴식 패턴
     * @return string 포맷팅된 문자열
     */
    private function formatBreakPattern($breakPattern) {
        $patterns = [
            'no_break' => '집중형 (휴식 없이 몰입)',
            'few_breaks' => '포모도로식 (25분 집중 + 5분 휴식)',
            'moderate_breaks' => '균형형 (적절한 휴식)',
            'frequent_breaks' => '짧은 사이클형 (자주 짧은 휴식)'
        ];

        return $patterns[$breakPattern] ?? '균형형';
    }

    /**
     * 시간 분석 포맷팅
     *
     * @param array $timePatterns 시간 패턴
     * @return string 포맷팅된 문자열
     */
    private function formatTimeAnalysis($timePatterns) {
        $lines = [];
        $slotNames = [
            'early_morning' => '이른 아침',
            'morning' => '오전',
            'afternoon' => '오후',
            'evening' => '저녁',
            'night' => '밤',
            'late_night' => '심야'
        ];

        foreach ($timePatterns as $slot => $data) {
            if ($data['session_count'] > 0) {
                $emoji = $data['avg_score'] >= 70 ? '✨' : ($data['avg_score'] >= 50 ? '📊' : '📉');
                $lines[] = sprintf("- %s %s: 평균 %s점 (%d회)",
                    $emoji,
                    $slotNames[$slot] ?? $slot,
                    $data['avg_score'],
                    $data['session_count']
                );
            }
        }

        return implode("\n", $lines);
    }

    /**
     * 루틴 제안 포맷팅
     *
     * @param array $suggestion 제안 데이터
     * @return string 포맷팅된 문자열
     */
    private function formatRoutineSuggestion($suggestion) {
        $lines = [];

        if (isset($suggestion['start_time'])) {
            $lines[] = "📅 **추천 학습 시간**: {$suggestion['start_time']}";
        }
        if (isset($suggestion['duration'])) {
            $lines[] = "⏱️ **세션 길이**: {$suggestion['duration']}";
        }
        if (isset($suggestion['break_style'])) {
            $lines[] = "☕ **휴식 방식**: " . $this->formatBreakPattern($suggestion['break_style']);
        }
        if (isset($suggestion['preferred_day'])) {
            $lines[] = "🗓️ **추천 요일**: {$suggestion['preferred_day']}";
        }

        return implode("\n", $lines);
    }

    /**
     * 패턴 설명 생성
     *
     * @param array $routineData 루틴 데이터
     * @return string 패턴 설명
     */
    private function generatePatternDescription($routineData) {
        $descriptions = [];

        if (isset($routineData['golden_time']) && $routineData['golden_time']['identified']) {
            $descriptions[] = "🌟 **골든타임**: {$routineData['golden_time']['slot_name']}에 학습할 때 가장 효과적이에요!";
        }

        if (isset($routineData['duration_patterns'])) {
            $optimal = $routineData['duration_patterns']['optimal_duration'];
            $descriptions[] = "⏰ **최적 세션 길이**: {$optimal}분 정도가 가장 적합해요.";
        }

        if (isset($routineData['weekday_patterns']['best_day_name'])) {
            $bestDay = $routineData['weekday_patterns']['best_day_name'];
            $descriptions[] = "📅 **베스트 요일**: {$bestDay}에 성과가 가장 좋아요.";
        }

        return implode("\n\n", $descriptions);
    }

    /**
     * 개선 제안 생성
     *
     * @param array $routineData 루틴 데이터
     * @return string 개선 제안
     */
    private function generateImprovementSuggestions($routineData) {
        $suggestions = [];

        // 세션 길이 관련 제안
        if (isset($routineData['duration_patterns'])) {
            $avg = $routineData['duration_patterns']['avg_duration'];
            $optimal = $routineData['duration_patterns']['optimal_duration'];

            if ($avg < $optimal - 10) {
                $suggestions[] = "💡 세션을 조금 더 길게 유지해 보세요. {$optimal}분 정도가 최적이에요.";
            } elseif ($avg > $optimal + 20) {
                $suggestions[] = "💡 너무 긴 세션은 피로도를 높일 수 있어요. {$optimal}분 단위로 나눠보세요.";
            }
        }

        // 골든타임 활용 제안
        if (isset($routineData['golden_time']) && $routineData['golden_time']['identified']) {
            $goldenTime = $routineData['golden_time']['slot_name'];
            $suggestions[] = "⭐ {$goldenTime}을 최대한 활용해 보세요. 중요한 과목을 이 시간에 배치하면 좋아요!";
        }

        return implode("\n\n", $suggestions);
    }

    /**
     * 사용자 정의 템플릿 등록
     *
     * @param string $context 컨텍스트
     * @param array $template 템플릿 데이터
     */
    public function registerTemplate($context, $template) {
        $this->defaultTemplates[$context] = $template;
    }

    /**
     * 톤 설정 업데이트
     *
     * @param string $toneName 톤 이름
     * @param array $settings 설정
     */
    public function updateToneSettings($toneName, $settings) {
        $this->toneSettings[$toneName] = array_merge(
            $this->toneSettings[$toneName] ?? [],
            $settings
        );
    }
}
