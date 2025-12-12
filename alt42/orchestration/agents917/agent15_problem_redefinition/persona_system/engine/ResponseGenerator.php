<?php
/**
 * ResponseGenerator - 응답 생성기
 *
 * 페르소나와 상황에 맞는 응답 메시지 생성
 * 문제 재정의 프레임에 기반한 구조화된 응답
 *
 * @package Agent15_ProblemRedefinition
 * @version 1.0
 * @created 2025-12-02
 */

class ResponseGenerator {

    /** @var array AI 설정 */
    private $aiConfig;

    /** @var string 템플릿 디렉토리 */
    private $templateDir;

    /** @var array 로드된 템플릿 캐시 */
    private $templateCache = [];

    /**
     * 생성자
     *
     * @param array $aiConfig AI 설정
     */
    public function __construct($aiConfig = []) {
        $this->aiConfig = $aiConfig;
        $this->templateDir = dirname(__DIR__) . '/templates';
    }

    /**
     * 응답 생성
     *
     * @param array $persona 페르소나 정보
     * @param string $triggerScenario 트리거 시나리오
     * @param array $context 컨텍스트
     * @param array $actionPlan 조치안
     * @return string 생성된 응답
     */
    public function generate($persona, $triggerScenario, $context, $actionPlan) {
        // 1. 템플릿 로드
        $template = $this->loadTemplate($triggerScenario, $persona);

        // 2. 변수 치환 데이터 준비
        $variables = $this->prepareVariables($persona, $triggerScenario, $context, $actionPlan);

        // 3. 템플릿 렌더링
        $response = $this->renderTemplate($template, $variables);

        // 4. 페르소나 특성에 따른 톤 조정
        $response = $this->adjustTone($response, $persona);

        // 5. 최종 검증
        $response = $this->validateResponse($response);

        return $response;
    }

    /**
     * 템플릿 로드
     *
     * @param string $scenario 시나리오 코드
     * @param array $persona 페르소나
     * @return string 템플릿 내용
     */
    private function loadTemplate($scenario, $persona) {
        // 캐시 확인
        $cacheKey = $scenario . '_' . ($persona['id'] ?? 'default');
        if (isset($this->templateCache[$cacheKey])) {
            return $this->templateCache[$cacheKey];
        }

        $template = '';

        // 시나리오별 템플릿 시도
        $scenarioPath = $this->templateDir . '/' . $scenario . '/response.php';
        if (file_exists($scenarioPath)) {
            $template = file_get_contents($scenarioPath);
        } else {
            // 기본 템플릿
            $defaultPath = $this->templateDir . '/default/response.php';
            if (file_exists($defaultPath)) {
                $template = file_get_contents($defaultPath);
            } else {
                // 하드코딩된 기본 템플릿
                $template = $this->getDefaultTemplate();
            }
        }

        $this->templateCache[$cacheKey] = $template;
        return $template;
    }

    /**
     * 기본 템플릿 반환
     *
     * @return string 기본 템플릿
     */
    private function getDefaultTemplate() {
        return <<<'TEMPLATE'
안녕하세요, {{student_name}}님.

{{greeting_message}}

## 현재 상황 분석

{{situation_analysis}}

## 주요 원인

{{cause_analysis}}

## 문제 재정의

{{redefined_problem}}

## 제안 조치

{{action_recommendations}}

{{closing_message}}

도움이 필요하시면 언제든 말씀해주세요. 함께 해결해 나가겠습니다.
TEMPLATE;
    }

    /**
     * 변수 준비
     *
     * @param array $persona 페르소나
     * @param string $scenario 시나리오
     * @param array $context 컨텍스트
     * @param array $actionPlan 조치안
     * @return array 변수 배열
     */
    private function prepareVariables($persona, $scenario, $context, $actionPlan) {
        $userInfo = $context['user_info'] ?? [];
        $causeAnalysis = $context['cause_analysis'] ?? [];

        return [
            'student_name' => $userInfo['name'] ?? '학생',
            'persona_name' => $persona['name'] ?? '',
            'trigger_scenario' => $this->getScenarioDescription($scenario),
            'greeting_message' => $this->getGreetingMessage($persona, $scenario),
            'situation_analysis' => $this->generateSituationAnalysis($context, $scenario),
            'cause_analysis' => $this->generateCauseAnalysis($causeAnalysis),
            'redefined_problem' => $context['redefined_problem'] ?? '추가 분석이 필요합니다.',
            'action_recommendations' => $this->generateActionRecommendations($actionPlan),
            'closing_message' => $this->getClosingMessage($persona),
            'date' => date('Y년 m월 d일')
        ];
    }

    /**
     * 시나리오 설명 반환
     */
    private function getScenarioDescription($scenario) {
        $descriptions = [
            'S1' => '최근 학습 성과가 하락하는 것으로 보입니다.',
            'S2' => '학습 이탈 징후가 감지되었습니다.',
            'S3' => '같은 유형의 문제에서 반복적인 오답이 나타나고 있습니다.',
            'S4' => '학습 루틴이 불안정한 상태입니다.',
            'S5' => '계획한 시간과 실제 학습 시간 사이에 차이가 있습니다.',
            'S6' => '학습 의욕이 다소 저하된 것 같습니다.',
            'S7' => '특정 개념에 대한 이해가 부족한 것으로 보입니다.',
            'S8' => '선생님의 피드백에서 주의가 필요한 부분이 발견되었습니다.',
            'S9' => '설정된 학습 전략과 실제 학습 행동이 일치하지 않습니다.',
            'S10' => '휴식 후 집중력 회복이 어려운 상황입니다.'
        ];

        return $descriptions[$scenario] ?? '학습 상황을 분석했습니다.';
    }

    /**
     * 인사 메시지 생성
     */
    private function getGreetingMessage($persona, $scenario) {
        $characteristics = $persona['characteristics'] ?? [];

        // 회피형
        if (in_array('avoidant', $characteristics)) {
            return "학습하면서 어려운 점이 있으셨나요? 천천히 함께 살펴보면 좋겠습니다.";
        }

        // 방어형
        if (in_array('defensive', $characteristics)) {
            return "학습 데이터를 살펴보니 몇 가지 이야기 나눠볼 점이 있어요. 같이 확인해볼까요?";
        }

        // 불안형
        if (in_array('anxious', $characteristics)) {
            return "걱정되는 부분이 있으시죠? 하나씩 살펴보면 분명 해결책이 있을 거예요.";
        }

        // 기본
        return "학습 현황을 분석해보았습니다. 몇 가지 개선할 수 있는 부분을 발견했어요.";
    }

    /**
     * 상황 분석 텍스트 생성
     */
    private function generateSituationAnalysis($context, $scenario) {
        $agentData = $context['agent_data'] ?? [];
        $parts = [];

        // 성과 데이터
        if (!empty($agentData['performance'])) {
            $perf = $agentData['performance'];
            if (isset($perf['score_trend']) && $perf['score_trend'] < 0) {
                $parts[] = sprintf(
                    "- 최근 2주간 점수가 약 %.1f점 하락했습니다.",
                    abs($perf['score_trend'])
                );
            }
            if (isset($perf['average'])) {
                $parts[] = sprintf("- 평균 점수: %.1f점", $perf['average']);
            }
        }

        // 학습 패턴
        if (!empty($agentData['study_patterns'])) {
            $patterns = $agentData['study_patterns'];
            if (isset($patterns['pomodoro_completion']) && $patterns['pomodoro_completion'] < 70) {
                $parts[] = sprintf(
                    "- 포모도로 완료율: %.0f%% (목표 대비 낮음)",
                    $patterns['pomodoro_completion']
                );
            }
        }

        if (empty($parts)) {
            return "현재 학습 데이터를 수집 중입니다.";
        }

        return implode("\n", $parts);
    }

    /**
     * 원인 분석 텍스트 생성
     */
    private function generateCauseAnalysis($causeAnalysis) {
        if (empty($causeAnalysis)) {
            return "원인 분석을 위한 추가 데이터가 필요합니다.";
        }

        $parts = [];
        $layerNames = [
            'cognitive' => '인지적',
            'behavioral' => '행동적',
            'motivational' => '동기적',
            'environmental' => '환경적'
        ];

        foreach ($causeAnalysis as $layer => $data) {
            if (empty($data['factors']) || $data['confidence'] < 0.3) {
                continue;
            }

            $layerLabel = $layerNames[$layer] ?? $layer;
            $topFactor = $data['factors'][0] ?? null;

            if ($topFactor) {
                $parts[] = sprintf(
                    "- **%s 요인**: %s (신뢰도 %.0f%%)",
                    $layerLabel,
                    $topFactor['description'],
                    $data['confidence'] * 100
                );
            }
        }

        if (empty($parts)) {
            return "현재 수집된 데이터로는 명확한 원인을 특정하기 어렵습니다.";
        }

        return implode("\n", $parts);
    }

    /**
     * 조치 권장사항 생성
     */
    private function generateActionRecommendations($actionPlan) {
        if (empty($actionPlan)) {
            return "구체적인 조치안을 준비 중입니다.";
        }

        $parts = [];
        $count = 1;

        foreach (array_slice($actionPlan, 0, 3) as $action) {
            $urgencyLabel = '';
            $urgency = $action['urgency'] ?? 0.5;
            if ($urgency >= 0.8) {
                $urgencyLabel = ' ⚠️ 우선';
            } elseif ($urgency >= 0.6) {
                $urgencyLabel = ' 📌 권장';
            }

            $parts[] = sprintf(
                "%d. **%s**%s\n   %s\n   예상 소요: %s",
                $count++,
                $action['title'] ?? '조치안',
                $urgencyLabel,
                $action['description'] ?? '',
                $action['duration'] ?? '미정'
            );
        }

        return implode("\n\n", $parts);
    }

    /**
     * 마무리 메시지 생성
     */
    private function getClosingMessage($persona) {
        $characteristics = $persona['characteristics'] ?? [];

        if (in_array('avoidant', $characteristics)) {
            return "작은 것부터 시작해도 괜찮아요. 한 걸음씩 나아가 봅시다.";
        }

        if (in_array('anxious', $characteristics)) {
            return "너무 걱정하지 않으셔도 돼요. 함께라면 충분히 해결할 수 있습니다.";
        }

        return "위의 제안들을 참고하여 학습 계획을 조정해보시기 바랍니다.";
    }

    /**
     * 템플릿 렌더링
     *
     * @param string $template 템플릿
     * @param array $variables 변수
     * @return string 렌더링된 내용
     */
    private function renderTemplate($template, $variables) {
        $rendered = $template;

        foreach ($variables as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $rendered = str_replace($placeholder, $value, $rendered);
        }

        // 미사용 placeholder 제거
        $rendered = preg_replace('/\{\{[^}]+\}\}/', '', $rendered);

        return trim($rendered);
    }

    /**
     * 톤 조정
     *
     * @param string $response 원본 응답
     * @param array $persona 페르소나
     * @return string 조정된 응답
     */
    private function adjustTone($response, $persona) {
        $characteristics = $persona['characteristics'] ?? [];

        // 부드러운 톤 조정
        if (in_array('sensitive', $characteristics) || in_array('anxious', $characteristics)) {
            // 부정적 표현 완화
            $replacements = [
                '실패' => '아쉬운 결과',
                '부족' => '더 발전할 여지',
                '문제' => '개선점',
                '약점' => '보완할 부분'
            ];

            foreach ($replacements as $from => $to) {
                $response = str_replace($from, $to, $response);
            }
        }

        return $response;
    }

    /**
     * 응답 검증
     *
     * @param string $response 응답
     * @return string 검증된 응답
     */
    private function validateResponse($response) {
        // 빈 응답 처리
        if (empty(trim($response))) {
            return "현재 분석 중입니다. 잠시 후 다시 확인해주세요.";
        }

        // 최대 길이 제한 (2000자)
        if (mb_strlen($response) > 2000) {
            $response = mb_substr($response, 0, 1950) . "\n\n...(자세한 내용은 상담을 통해 확인해주세요)";
        }

        return $response;
    }

    /**
     * 정서적 지원 응답 생성 (E 시리즈용)
     *
     * @param array $persona 페르소나
     * @param array $context 컨텍스트
     * @return string 정서적 지원 응답
     */
    public function generateEmotionalResponse($persona, $context) {
        $emotionLogs = $context['agent_data']['emotion_logs'] ?? [];
        $dominantEmotion = $this->getDominantEmotion($emotionLogs);

        $responses = [
            'frustration' => "좌절감을 느끼고 계시는군요. 그 마음 충분히 이해합니다. 어려운 상황에서도 노력하고 계신 점이 대단해요.",
            'anxiety' => "불안한 마음이 드시는 것 같아요. 괜찮아요, 한 번에 다 해결하려고 하지 않아도 됩니다.",
            'boredom' => "학습이 지루하게 느껴지시나요? 새로운 방법을 시도해보는 건 어떨까요?",
            'hopelessness' => "힘든 시간을 보내고 계시네요. 작은 성취부터 시작해보면 분명 나아질 거예요."
        ];

        return $responses[$dominantEmotion] ?? "함께 이야기 나눠볼까요? 어떤 점이 가장 힘드신가요?";
    }

    /**
     * 주요 감정 추출
     */
    private function getDominantEmotion($emotionLogs) {
        if (empty($emotionLogs)) {
            return 'neutral';
        }

        $counts = [];
        foreach ($emotionLogs as $log) {
            $emotion = $log['emotion'] ?? 'neutral';
            $counts[$emotion] = ($counts[$emotion] ?? 0) + 1;
        }

        arsort($counts);
        return array_key_first($counts);
    }
}
