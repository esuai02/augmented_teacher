<?php
/**
 * Teacher Persona Response Templates
 *
 * 선생님 페르소나별 응답 템플릿 정의
 * T0-T5, E-Series 각 상황에 맞는 응답 패턴
 *
 * URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent06_teacher_feedback/persona_system/templates/teacher_templates.php
 *
 * @package AugmentedTeacher\Agent06\Templates
 * @version 1.0
 * @author Claude Code
 */

namespace AugmentedTeacher\Agent06\Templates;

/**
 * 선생님 응답 템플릿 클래스
 */
class TeacherTemplates {

    /** @var string 현재 파일 경로 */
    private static $currentFile = __FILE__;

    /**
     * T0 (일반 대화) 템플릿
     */
    public static function getT0Templates(): array {
        return [
            'T0_P1' => [ // 친근한 대화형
                'greeting' => [
                    '안녕! 오늘 수학 공부하러 왔구나?',
                    '반가워요! 무엇이든 편하게 물어보세요.',
                    '오늘도 파이팅! 궁금한 거 있으면 말해줘요.'
                ],
                'encouragement' => [
                    '그렇게 생각하는구나! 좋은 시도야.',
                    '질문을 잘 하네요! 좋은 자세예요.',
                    '궁금한 게 많다는 건 좋은 거예요!'
                ],
                'response' => [
                    '{{student_name}}님, {{topic}}에 대해 알려드릴게요.',
                    '좋은 질문이에요! {{explanation}}',
                    '{{topic}}은(는) 이렇게 생각하면 쉬워요. {{hint}}'
                ]
            ],
            'T0_P2' => [ // 균형잡힌 전문가형
                'greeting' => [
                    '안녕하세요. 오늘 학습을 시작해 볼까요?',
                    '좋은 하루입니다. 어떤 부분을 공부할까요?',
                    '반갑습니다. 오늘의 학습 목표를 정해볼까요?'
                ],
                'encouragement' => [
                    '정확한 접근입니다. 잘 이해하고 계시네요.',
                    '논리적으로 잘 생각하셨습니다.',
                    '좋은 시도입니다. 이 방향으로 계속 해보세요.'
                ],
                'response' => [
                    '{{student_name}}님, {{topic}}에 대해 설명드리겠습니다.',
                    '{{topic}}의 핵심 개념은 다음과 같습니다. {{explanation}}',
                    '이 문제는 {{method}} 방법으로 접근하면 좋습니다.'
                ]
            ],
            'T0_P3' => [ // 체계적 분석가형
                'greeting' => [
                    '안녕하세요. 체계적으로 학습해 봅시다.',
                    '학습 준비가 되셨다면 시작하겠습니다.',
                    '오늘 다룰 내용을 정리해 보겠습니다.'
                ],
                'encouragement' => [
                    '분석력이 좋습니다. 핵심을 잘 파악하셨네요.',
                    '논리적 추론이 정확합니다.',
                    '체계적인 접근 방식이 돋보입니다.'
                ],
                'response' => [
                    '{{topic}}을(를) 단계별로 살펴보겠습니다.',
                    '먼저 기본 개념을 정리한 후 응용으로 넘어가겠습니다.',
                    '{{step1}} → {{step2}} → {{step3}} 순서로 진행하겠습니다.'
                ]
            ]
        ];
    }

    /**
     * T1 (격려) 템플릿
     */
    public static function getT1Templates(): array {
        return [
            'T1_P1' => [ // 열정적 동기부여형
                'praise' => [
                    '와! 정말 잘했어요! 완벽해요!',
                    '대단해요! 실력이 많이 늘었네요!',
                    '최고예요! 이 정도면 정말 대단한 거예요!'
                ],
                'motivation' => [
                    '포기하지 마세요! 분명 할 수 있어요!',
                    '조금만 더 힘내면 돼요! 화이팅!',
                    '어려워도 도전하는 모습이 정말 멋져요!'
                ],
                'progress' => [
                    '{{student_name}}님, {{progress_rate}}%나 향상됐어요! 대단해요!',
                    '이전보다 {{improvement}} 더 잘하고 있어요!',
                    '꾸준히 성장하고 있어요! 정말 자랑스러워요!'
                ]
            ],
            'T1_P2' => [ // 따뜻한 지지형
                'praise' => [
                    '잘하고 있어요. 노력이 보여요.',
                    '좋은 시도예요. 계속 이렇게 해봐요.',
                    '착실하게 해나가고 있네요.'
                ],
                'motivation' => [
                    '천천히 해도 괜찮아요. 함께 가요.',
                    '어려울 때 쉬어가도 돼요. 괜찮아요.',
                    '지금 모습 그대로도 충분히 잘하고 있어요.'
                ],
                'progress' => [
                    '{{student_name}}님, 조금씩 성장하고 있어요.',
                    '어제보다 오늘 더 나아졌어요.',
                    '작은 진전도 큰 의미가 있어요.'
                ]
            ]
        ];
    }

    /**
     * T2 (교정) 템플릿
     */
    public static function getT2Templates(): array {
        return [
            'T2_P1' => [ // 건설적 피드백형
                'correction' => [
                    '좋은 시도였어요! 다만, {{error_point}}를 확인해 볼까요?',
                    '거의 다 맞았어요. {{correction}}만 수정하면 완벽해요.',
                    '핵심은 잘 잡았어요. {{improvement}}부분을 더 연습해봐요.'
                ],
                'guidance' => [
                    '이 부분은 {{correct_method}}로 접근하면 더 좋아요.',
                    '{{hint}}를 생각해보면 답이 보일 거예요.',
                    '{{concept}}을(를) 다시 살펴볼까요?'
                ],
                'encouragement' => [
                    '틀려도 괜찮아요. 배우는 과정이에요.',
                    '이런 실수는 누구나 해요. 다음엔 더 잘할 수 있어요.',
                    '실수에서 배우는 게 가장 좋은 학습이에요.'
                ]
            ],
            'T2_P2' => [ // 분석적 교정형
                'correction' => [
                    '오답 원인: {{error_analysis}}. 올바른 풀이: {{correct_solution}}',
                    '{{error_type}} 유형의 실수입니다. {{correction}}',
                    '이 부분에서 {{misconception}}이(가) 있었네요.'
                ],
                'guidance' => [
                    '핵심 공식: {{formula}}. 적용 방법: {{application}}',
                    '문제 유형 분석: {{problem_type}}. 풀이 전략: {{strategy}}',
                    '{{step1}} 후 {{step2}}를 진행하면 됩니다.'
                ],
                'encouragement' => [
                    '오류 패턴을 파악했으니 개선될 거예요.',
                    '이 실수를 알아두면 다음엔 안 해요.',
                    '분석 결과를 토대로 연습해보세요.'
                ]
            ]
        ];
    }

    /**
     * T3 (학습 설계) 템플릿
     */
    public static function getT3Templates(): array {
        return [
            'T3_P1' => [ // 맞춤형 설계형
                'plan' => [
                    '{{student_name}}님의 현재 수준에 맞춰 학습 계획을 세웠어요.',
                    '{{weak_area}}부터 차근차근 다뤄볼게요.',
                    '오늘의 목표: {{today_goal}}. 함께 달성해봐요!'
                ],
                'progress_check' => [
                    '지금까지 {{completed}}을(를) 완료했어요.',
                    '{{remaining}} 남았어요. 잘하고 있어요!',
                    '목표의 {{progress_percent}}%를 달성했어요.'
                ],
                'adaptation' => [
                    '{{student_name}}님의 속도에 맞춰 조정할게요.',
                    '좀 더 쉬운 문제로 연습해볼까요?',
                    '도전적인 문제도 시도해볼 준비가 됐어요!'
                ]
            ],
            'T3_P2' => [ // 전략적 코칭형
                'plan' => [
                    '학습 전략: {{strategy}}. 예상 소요 시간: {{duration}}',
                    '우선순위: 1.{{priority1}} 2.{{priority2}} 3.{{priority3}}',
                    '목표 성취 경로: {{current_level}} → {{target_level}}'
                ],
                'progress_check' => [
                    '진도율: {{progress_rate}}%. 예상 완료: {{expected_completion}}',
                    '성취도 분석: 강점-{{strength}}, 개선점-{{improvement}}',
                    '학습 효율: {{efficiency_score}}점'
                ],
                'adaptation' => [
                    '데이터 기반 조정: {{adjustment_reason}}',
                    '학습 패턴 분석 결과 {{recommendation}}',
                    '최적화된 학습 경로로 수정합니다.'
                ]
            ]
        ];
    }

    /**
     * T4 (정서 지원) 템플릿
     */
    public static function getT4Templates(): array {
        return [
            'T4_P1' => [ // 공감적 지지형
                'empathy' => [
                    '힘드셨죠. 그 마음 충분히 이해해요.',
                    '어려운 상황인 걸 알아요. 괜찮아요.',
                    '지치셨을 텐데, 정말 수고 많으셨어요.'
                ],
                'support' => [
                    '천천히 해도 괜찮아요. 제가 기다릴게요.',
                    '힘들 땐 쉬어가도 돼요. 언제든 다시 시작해요.',
                    '{{student_name}}님은 충분히 잘하고 있어요.'
                ],
                'comfort' => [
                    '모든 게 다 잘 될 거예요. 함께해요.',
                    '어려울 때 도움 요청하는 건 좋은 거예요.',
                    '이 순간도 지나갈 거예요. 힘내세요.'
                ]
            ],
            'T4_P2' => [ // 안정적 상담형
                'empathy' => [
                    '현재 상태를 이해합니다. 어떤 부분이 가장 힘드신가요?',
                    '학습 스트레스가 느껴지시는군요.',
                    '불안한 마음이 드는 건 자연스러운 거예요.'
                ],
                'support' => [
                    '지금 할 수 있는 작은 것부터 시작해봐요.',
                    '목표를 작게 나누면 부담이 줄어들어요.',
                    '완벽하지 않아도 괜찮습니다.'
                ],
                'comfort' => [
                    '충분한 휴식도 학습의 일부입니다.',
                    '자신의 페이스를 존중해주세요.',
                    '지금 느끼는 감정을 인정해주는 게 중요해요.'
                ]
            ]
        ];
    }

    /**
     * T5 (성과 리뷰) 템플릿
     */
    public static function getT5Templates(): array {
        return [
            'T5_P1' => [ // 긍정적 리뷰형
                'summary' => [
                    '{{student_name}}님, 이번 {{period}} 동안 정말 잘했어요!',
                    '{{achievement}}을(를) 달성했어요! 축하해요!',
                    '{{strong_point}}에서 특히 빛났어요.'
                ],
                'analysis' => [
                    '가장 많이 성장한 부분: {{growth_area}}',
                    '앞으로 더 발전할 수 있는 부분: {{potential_area}}',
                    '학습 습관 평가: {{habit_feedback}}'
                ],
                'future' => [
                    '다음 목표로 {{next_goal}}을(를) 도전해봐요!',
                    '이 기세로 계속 가면 {{future_prediction}}',
                    '다음 단계가 기대돼요!'
                ]
            ],
            'T5_P2' => [ // 데이터 기반 리뷰형
                'summary' => [
                    '성과 요약: 정답률 {{accuracy}}%, 학습량 {{study_amount}}',
                    '달성 목표: {{achieved_goals}}/{{total_goals}}',
                    '등급 변화: {{prev_grade}} → {{curr_grade}}'
                ],
                'analysis' => [
                    '강점 분야: {{strengths}}. 개선 필요: {{weaknesses}}',
                    '시간대별 효율: {{peak_hours}}에 가장 효과적',
                    '오답 패턴: {{error_patterns}}'
                ],
                'future' => [
                    '추천 학습 경로: {{recommended_path}}',
                    '다음 목표: {{next_target}}. 예상 달성 기간: {{estimated_days}}일',
                    '집중 개선 영역: {{focus_area}}'
                ]
            ]
        ];
    }

    /**
     * E-Series (비상 상황) 템플릿
     */
    public static function getEmergencyTemplates(): array {
        return [
            'E_CRISIS' => [ // 위기 상황
                'immediate' => [
                    '{{student_name}}님, 많이 힘드시죠. 제가 여기 있어요.',
                    '지금 느끼는 감정을 말씀해 주셔도 괜찮아요.',
                    '어떤 이야기든 들을 준비가 되어 있어요.'
                ],
                'support' => [
                    '혼자가 아니에요. 함께 해결해 나가요.',
                    '지금 가장 힘든 게 뭔지 말씀해 주실 수 있을까요?',
                    '천천히, 편하게 이야기해 주세요.'
                ],
                'resource' => [
                    '도움을 받을 수 있는 곳을 안내해 드릴게요.',
                    '전문 상담이 필요하시면 연결해 드릴 수 있어요.',
                    '학교 상담선생님과 이야기해 보시는 건 어떨까요?'
                ]
            ],
            'E_BURNOUT' => [ // 번아웃 상황
                'recognition' => [
                    '많이 지치셨네요. 충분히 쉬어도 괜찮아요.',
                    '번아웃 신호가 보여요. 잠시 멈춰도 돼요.',
                    '열심히 해온 만큼 쉼도 필요해요.'
                ],
                'recovery' => [
                    '오늘은 가볍게만 하고 푹 쉬세요.',
                    '학습 외에 즐거운 활동도 중요해요.',
                    '작은 성취부터 다시 시작해봐요.'
                ],
                'prevention' => [
                    '앞으로는 페이스 조절이 필요할 것 같아요.',
                    '적정 학습량을 함께 정해볼까요?',
                    '건강한 학습 습관을 만들어 봐요.'
                ]
            ]
        ];
    }

    /**
     * 전체 템플릿 반환
     *
     * @return array
     */
    public static function getAllTemplates(): array {
        return [
            'T0' => self::getT0Templates(),
            'T1' => self::getT1Templates(),
            'T2' => self::getT2Templates(),
            'T3' => self::getT3Templates(),
            'T4' => self::getT4Templates(),
            'T5' => self::getT5Templates(),
            'E' => self::getEmergencyTemplates()
        ];
    }

    /**
     * 특정 페르소나의 템플릿 반환
     *
     * @param string $personaId 페르소나 ID (예: T1_P1, E_CRISIS)
     * @return array|null
     */
    public static function getTemplatesByPersona(string $personaId): ?array {
        // 페르소나 ID 파싱 (T1_P1 → category=T1, sub=P1)
        $parts = explode('_', $personaId);

        if (count($parts) < 2) {
            return null;
        }

        $category = $parts[0];

        // E-Series 처리
        if ($category === 'E') {
            $templates = self::getEmergencyTemplates();
            return $templates[$personaId] ?? null;
        }

        // T0-T5 처리
        $methodName = 'get' . $category . 'Templates';
        if (!method_exists(self::class, $methodName)) {
            return null;
        }

        $templates = self::$methodName();
        return $templates[$personaId] ?? null;
    }

    /**
     * 템플릿 렌더링 (변수 치환)
     *
     * @param string $template 템플릿 문자열
     * @param array $variables 치환 변수
     * @return string
     */
    public static function render(string $template, array $variables): string {
        return preg_replace_callback('/\{\{(\w+)\}\}/', function($matches) use ($variables) {
            $key = $matches[1];
            return $variables[$key] ?? $matches[0];
        }, $template);
    }

    /**
     * 랜덤 템플릿 선택 및 렌더링
     *
     * @param string $personaId 페르소나 ID
     * @param string $category 템플릿 카테고리 (greeting, encouragement, etc.)
     * @param array $variables 치환 변수
     * @return string|null
     */
    public static function getRandomTemplate(string $personaId, string $category, array $variables = []): ?string {
        $templates = self::getTemplatesByPersona($personaId);

        if (!$templates || !isset($templates[$category])) {
            error_log("[TeacherTemplates ERROR] " . self::$currentFile . ":" . __LINE__ .
                      " - 템플릿을 찾을 수 없습니다: {$personaId}/{$category}");
            return null;
        }

        $options = $templates[$category];
        $selected = $options[array_rand($options)];

        return self::render($selected, $variables);
    }
}

/*
 * 관련 DB 테이블: 없음 (정적 템플릿)
 *
 * 참조 파일:
 * - engine/TeacherPersonaEngine.php (템플릿 사용)
 * - personas.md (페르소나 정의)
 */
