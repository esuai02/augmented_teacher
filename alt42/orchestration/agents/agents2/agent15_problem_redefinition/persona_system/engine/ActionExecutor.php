<?php
/**
 * ActionExecutor - 액션 실행기
 *
 * 문제 재정의 결과에 따른 조치안 생성 및 실행
 *
 * @package Agent15_ProblemRedefinition
 * @version 1.0
 * @created 2025-12-02
 */

class ActionExecutor {

    /** @var array AI 설정 */
    private $aiConfig;

    /** @var array 액션 템플릿 */
    private $actionTemplates = [];

    /**
     * 생성자
     *
     * @param array $aiConfig AI 설정
     */
    public function __construct($aiConfig = []) {
        $this->aiConfig = $aiConfig;
        $this->loadActionTemplates();
    }

    /**
     * 액션 템플릿 로드
     */
    private function loadActionTemplates() {
        $this->actionTemplates = [
            // 인지적 요인 대응
            'concept_gap' => [
                'type' => 'learning_intervention',
                'title' => '개념 보충 학습',
                'description' => '부족한 개념에 대한 맞춤형 복습 자료 제공',
                'urgency' => 0.8,
                'impact' => 0.9,
                'duration' => '2-3일'
            ],
            'prerequisite_missing' => [
                'type' => 'learning_intervention',
                'title' => '선수학습 진단 및 보충',
                'description' => '필요한 선수 지식 파악 후 단계별 학습 제안',
                'urgency' => 0.9,
                'impact' => 0.95,
                'duration' => '1주일'
            ],

            // 행동적 요인 대응
            'routine_issue' => [
                'type' => 'behavior_modification',
                'title' => '학습 루틴 재설계',
                'description' => '포모도로 시간 조정 및 휴식 패턴 최적화',
                'urgency' => 0.6,
                'impact' => 0.7,
                'duration' => '1주일'
            ],
            'time_waste' => [
                'type' => 'behavior_modification',
                'title' => '시간 관리 코칭',
                'description' => '계획 수립 방법 및 실행 모니터링 강화',
                'urgency' => 0.7,
                'impact' => 0.75,
                'duration' => '2주일'
            ],

            // 동기적 요인 대응
            'motivation_decline' => [
                'type' => 'motivation_boost',
                'title' => '동기 부여 개입',
                'description' => '성취 경험 설계 및 긍정적 피드백 강화',
                'urgency' => 0.75,
                'impact' => 0.8,
                'duration' => '지속적'
            ],
            'goal_unclear' => [
                'type' => 'goal_setting',
                'title' => '목표 재설정 상담',
                'description' => '단기/중기 목표 명확화 및 달성 로드맵 작성',
                'urgency' => 0.6,
                'impact' => 0.85,
                'duration' => '상담 1회 + 후속'
            ],

            // 환경적 요인 대응
            'environment_issue' => [
                'type' => 'environment_adjustment',
                'title' => '학습 환경 개선',
                'description' => '집중력 향상을 위한 환경 조정 가이드',
                'urgency' => 0.5,
                'impact' => 0.6,
                'duration' => '즉시 적용'
            ],
            'support_lacking' => [
                'type' => 'support_enhancement',
                'title' => '지원 체계 강화',
                'description' => '가정/학교 연계 지원 및 멘토링 연결',
                'urgency' => 0.65,
                'impact' => 0.7,
                'duration' => '1-2주일'
            ]
        ];
    }

    /**
     * 요인에 대한 액션 생성
     *
     * @param array $factor 요인 정보
     * @param array $persona 페르소나 정보
     * @param array $context 컨텍스트
     * @return array|null 생성된 액션
     */
    public function generateAction($factor, $persona, $context) {
        $factorType = $factor['type'] ?? 'unknown';

        // 템플릿에서 기본 액션 가져오기
        $template = $this->actionTemplates[$factorType] ?? null;

        if (!$template) {
            // 범용 액션 생성
            return $this->generateGenericAction($factor, $persona);
        }

        // 페르소나에 맞게 액션 커스터마이즈
        $action = $this->customizeAction($template, $persona, $factor, $context);

        return $action;
    }

    /**
     * 액션 커스터마이즈
     *
     * @param array $template 액션 템플릿
     * @param array $persona 페르소나
     * @param array $factor 요인
     * @param array $context 컨텍스트
     * @return array 커스터마이즈된 액션
     */
    private function customizeAction($template, $persona, $factor, $context) {
        $action = $template;

        // 요인 정보 추가
        $action['factor'] = $factor;
        $action['severity'] = $factor['severity'] ?? 0.5;

        // 페르소나 특성에 따른 조정
        $characteristics = $persona['characteristics'] ?? [];

        // 회피형 페르소나
        if (in_array('avoidant', $characteristics)) {
            $action['approach'] = 'gentle';
            $action['description'] = $this->softenDescription($action['description']);
            $action['steps'] = $this->breakDownSteps($action);
        }

        // 방어형 페르소나
        if (in_array('defensive', $characteristics)) {
            $action['approach'] = 'collaborative';
            $action['description'] = $this->addChoiceElement($action['description']);
        }

        // 학생 레벨에 따른 조정
        $studentLevel = $context['student_level'] ?? 'mid';
        if ($studentLevel === 'low') {
            $action['urgency'] = min($action['urgency'] + 0.1, 1.0);
            $action['steps'] = $this->simplifySteps($action['steps'] ?? []);
        } elseif ($studentLevel === 'high') {
            $action['depth'] = 'advanced';
        }

        // 고유 ID 생성
        $action['id'] = $this->generateActionId();

        return $action;
    }

    /**
     * 범용 액션 생성
     *
     * @param array $factor 요인
     * @param array $persona 페르소나
     * @return array 생성된 액션
     */
    private function generateGenericAction($factor, $persona) {
        return [
            'id' => $this->generateActionId(),
            'type' => 'general_intervention',
            'title' => '맞춤형 개입',
            'description' => $factor['description'] . '에 대한 개선 방안을 제안합니다.',
            'factor' => $factor,
            'severity' => $factor['severity'] ?? 0.5,
            'urgency' => 0.5,
            'impact' => 0.6,
            'duration' => '상황에 따라'
        ];
    }

    /**
     * 설명 완화 (회피형 대응)
     */
    private function softenDescription($description) {
        $softPrefixes = [
            '천천히 진행할 수 있는 ',
            '부담 없이 시도해볼 수 있는 ',
            '작은 것부터 시작하는 '
        ];
        return $softPrefixes[array_rand($softPrefixes)] . $description;
    }

    /**
     * 선택 요소 추가 (방어형 대응)
     */
    private function addChoiceElement($description) {
        return $description . ' (여러 옵션 중 선택 가능)';
    }

    /**
     * 단계 세분화
     */
    private function breakDownSteps($action) {
        return [
            '1. 현재 상황 함께 살펴보기',
            '2. 작은 목표 하나 정하기',
            '3. 첫 단계 시도해보기',
            '4. 결과 확인하고 조정하기'
        ];
    }

    /**
     * 단계 단순화
     */
    private function simplifySteps($steps) {
        if (empty($steps)) {
            return ['1. 한 가지씩 천천히 진행해보세요'];
        }
        // 최대 3단계로 축소
        return array_slice($steps, 0, 3);
    }

    /**
     * 액션 ID 생성
     */
    private function generateActionId() {
        return 'ACT_' . date('Ymd') . '_' . substr(md5(uniqid()), 0, 8);
    }

    /**
     * 액션 실행 (실제 시스템 연동)
     *
     * @param array $action 실행할 액션
     * @param int $userId 사용자 ID
     * @return bool 성공 여부
     */
    public function executeAction($action, $userId) {
        global $DB;

        try {
            // 액션 로그 저장
            $log = new stdClass();
            $log->userid = $userId;
            $log->nagent = 15;
            $log->action_id = $action['id'];
            $log->action_type = $action['type'];
            $log->action_data = json_encode($action);
            $log->status = 'initiated';
            $log->timecreated = time();

            $DB->insert_record('at_agent_action_log', $log);

            // 액션 타입별 실제 실행
            switch ($action['type']) {
                case 'learning_intervention':
                    return $this->executeLearningIntervention($action, $userId);

                case 'behavior_modification':
                    return $this->executeBehaviorModification($action, $userId);

                case 'motivation_boost':
                    return $this->executeMotivationBoost($action, $userId);

                case 'environment_adjustment':
                    return $this->executeEnvironmentAdjustment($action, $userId);

                default:
                    return $this->executeGenericAction($action, $userId);
            }

        } catch (Exception $e) {
            error_log("Action execution failed: " . $e->getMessage() .
                " [" . __FILE__ . ":" . __LINE__ . "]");
            return false;
        }
    }

    // === 액션 타입별 실행 메서드 ===

    private function executeLearningIntervention($action, $userId) {
        // 학습 개입 알림 생성
        $this->createNotification($userId, 'learning', $action);
        return true;
    }

    private function executeBehaviorModification($action, $userId) {
        // 행동 수정 가이드 제공
        $this->createNotification($userId, 'behavior', $action);
        return true;
    }

    private function executeMotivationBoost($action, $userId) {
        // 동기 부여 메시지 전송
        $this->createNotification($userId, 'motivation', $action);
        return true;
    }

    private function executeEnvironmentAdjustment($action, $userId) {
        // 환경 조정 팁 제공
        $this->createNotification($userId, 'environment', $action);
        return true;
    }

    private function executeGenericAction($action, $userId) {
        // 일반 알림 생성
        $this->createNotification($userId, 'general', $action);
        return true;
    }

    /**
     * 알림 생성
     *
     * @param int $userId 사용자 ID
     * @param string $type 알림 타입
     * @param array $action 액션 데이터
     */
    private function createNotification($userId, $type, $action) {
        global $DB;

        try {
            $notification = new stdClass();
            $notification->userid = $userId;
            $notification->type = 'agent15_' . $type;
            $notification->title = $action['title'] ?? '새로운 제안';
            $notification->message = $action['description'] ?? '';
            $notification->data = json_encode($action);
            $notification->status = 'unread';
            $notification->timecreated = time();

            // 알림 테이블이 있으면 저장
            if ($DB->get_manager()->table_exists('at_notifications')) {
                $DB->insert_record('at_notifications', $notification);
            }

        } catch (Exception $e) {
            error_log("Notification creation failed: " . $e->getMessage() .
                " [" . __FILE__ . ":" . __LINE__ . "]");
        }
    }

    /**
     * 액션 템플릿 조회
     *
     * @param string $type 템플릿 타입
     * @return array|null 템플릿
     */
    public function getActionTemplate($type) {
        return $this->actionTemplates[$type] ?? null;
    }

    /**
     * 모든 액션 템플릿 조회
     *
     * @return array 템플릿 목록
     */
    public function getAllTemplates() {
        return $this->actionTemplates;
    }
}
