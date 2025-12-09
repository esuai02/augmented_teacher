<?php
/**
 * ActionExecutor - 규칙 액션 실행기
 *
 * 매칭된 규칙의 액션을 실행하고 결과를 반환합니다.
 * Agent21 개입 실행에 특화된 액션 핸들러 포함.
 *
 * @package AugmentedTeacher\Agent21\PersonaSystem
 * @version 1.0
 */

class ActionExecutor {

    /** @var string 현재 파일 경로 */
    private $currentFile = __FILE__;

    /** @var array 커스텀 액션 핸들러 */
    private $handlers = [];

    /** @var array 액션 실행 로그 */
    private $executionLog = [];

    /**
     * 생성자 - 기본 액션 핸들러 등록
     */
    public function __construct() {
        $this->registerDefaultHandlers();
        $this->registerInterventionHandlers();
    }

    /**
     * 액션 실행
     *
     * @param array $actions 실행할 액션 목록
     * @param array $context 현재 컨텍스트
     * @return array 실행 결과
     */
    public function execute(array $actions, array $context = []): array {
        $results = [];

        foreach ($actions as $action) {
            $result = $this->executeAction($action, $context);
            $results[] = $result;

            // 실행 로그 기록
            $this->logExecution($action, $result);
        }

        return $results;
    }

    /**
     * 단일 액션 실행
     *
     * @param string $action 액션 문자열
     * @param array $context 컨텍스트
     * @return string 실행 결과
     */
    private function executeAction(string $action, array $context): string {
        // 액션 파싱: "action_name: 'value'" 또는 "action_name"
        $parts = explode(':', $action, 2);
        $actionName = trim($parts[0]);
        $actionValue = isset($parts[1]) ? trim($parts[1], " '\"") : null;

        // 커스텀 핸들러 확인
        if (isset($this->handlers[$actionName])) {
            try {
                $handler = $this->handlers[$actionName];
                return $handler($actionValue, $context);
            } catch (Exception $e) {
                error_log("[ActionExecutor] {$this->currentFile}:" . __LINE__ . " - 핸들러 실행 오류: " . $e->getMessage());
                return $action; // 원본 반환
            }
        }

        // 기본 처리: 원본 액션 문자열 반환
        return $action;
    }

    /**
     * 기본 액션 핸들러 등록
     */
    private function registerDefaultHandlers(): void {
        // 페르소나 식별 액션
        $this->handlers['identify_persona'] = function($value, $context) {
            return "identify_persona: {$value}";
        };

        // 톤 설정 액션
        $this->handlers['set_tone'] = function($value, $context) {
            $validTones = ['Professional', 'Warm', 'Encouraging', 'Calm', 'Playful', 'Direct', 'Empathetic', 'Patient', 'Assertive'];
            if (!in_array($value, $validTones)) {
                error_log("[ActionExecutor] {$this->currentFile}:" . __LINE__ . " - 유효하지 않은 톤: {$value}");
                $value = 'Professional';
            }
            return "set_tone: {$value}";
        };

        // 페이스 설정 액션
        $this->handlers['set_pace'] = function($value, $context) {
            $validPaces = ['slow', 'normal', 'fast', 'adaptive', 'patient'];
            if (!in_array($value, $validPaces)) {
                error_log("[ActionExecutor] {$this->currentFile}:" . __LINE__ . " - 유효하지 않은 페이스: {$value}");
                $value = 'normal';
            }
            return "set_pace: {$value}";
        };

        // 개입 우선순위 설정
        $this->handlers['prioritize_intervention'] = function($value, $context) {
            $validInterventions = [
                'EmotionalSupport',     // 정서적 지지
                'InformationProvision', // 정보 제공
                'SkillBuilding',        // 기술 구축
                'BehaviorModification', // 행동 수정
                'SafetyNet',            // 안전망
                'PlanDesign',           // 계획 설계
                'AssessmentDesign',     // 평가 설계
                'GapAnalysis',          // 갭 분석
                'ParentCoordination',   // 부모 조율
                'GoalSetting',          // 목표 설정
                'CrisisIntervention',   // 위기 개입
                'MotivationBoost',      // 동기 부여
                'ResistanceHandling',   // 저항 대응
                'FollowUpReminder'      // 후속 리마인더
            ];
            if (!in_array($value, $validInterventions)) {
                error_log("[ActionExecutor] {$this->currentFile}:" . __LINE__ . " - 유효하지 않은 개입 유형: {$value}");
                $value = 'InformationProvision';
            }
            return "prioritize_intervention: {$value}";
        };

        // 정보 깊이 설정
        $this->handlers['set_information_depth'] = function($value, $context) {
            $validDepths = ['surface', 'moderate', 'deep', 'comprehensive'];
            if (!in_array($value, $validDepths)) {
                $value = 'moderate';
            }
            return "set_information_depth: {$value}";
        };

        // 플래그 추가
        $this->handlers['add_flag'] = function($value, $context) {
            return "add_flag: {$value}";
        };

        // 응답 스타일 설정
        $this->handlers['set_response_style'] = function($value, $context) {
            $validStyles = ['concise', 'detailed', 'structured', 'conversational', 'technical', 'supportive'];
            if (!in_array($value, $validStyles)) {
                $value = 'conversational';
            }
            return "set_response_style: {$value}";
        };

        // 예시 사용 설정
        $this->handlers['use_examples'] = function($value, $context) {
            $validLevels = ['none', 'minimal', 'moderate', 'extensive'];
            if (!in_array($value, $validLevels)) {
                $value = 'moderate';
            }
            return "use_examples: {$value}";
        };

        // 격려 수준 설정
        $this->handlers['set_encouragement_level'] = function($value, $context) {
            $validLevels = ['low', 'medium', 'high', 'very_high'];
            if (!in_array($value, $validLevels)) {
                $value = 'medium';
            }
            return "set_encouragement_level: {$value}";
        };

        // 피드백 빈도 설정
        $this->handlers['set_feedback_frequency'] = function($value, $context) {
            $validFrequencies = ['minimal', 'standard', 'frequent', 'continuous'];
            if (!in_array($value, $validFrequencies)) {
                $value = 'standard';
            }
            return "set_feedback_frequency: {$value}";
        };

        // 질문 스타일 설정
        $this->handlers['set_question_style'] = function($value, $context) {
            $validStyles = ['open', 'closed', 'guided', 'socratic', 'reflective'];
            if (!in_array($value, $validStyles)) {
                $value = 'guided';
            }
            return "set_question_style: {$value}";
        };

        // 컨텍스트 업데이트
        $this->handlers['update_context'] = function($value, $context) {
            return "update_context: {$value}";
        };

        // 에스컬레이션 트리거
        $this->handlers['trigger_escalation'] = function($value, $context) {
            $validLevels = ['monitor', 'alert', 'human_review', 'immediate'];
            if (!in_array($value, $validLevels)) {
                $value = 'monitor';
            }
            return "trigger_escalation: {$value}";
        };
    }

    /**
     * Agent21 개입 실행 특화 액션 핸들러 등록
     */
    private function registerInterventionHandlers(): void {
        // 반응 유형 설정
        $this->handlers['set_response_type'] = function($value, $context) {
            $validTypes = ['acceptance', 'resistance', 'no_response', 'delayed'];
            if (!in_array($value, $validTypes)) {
                error_log("[ActionExecutor] {$this->currentFile}:" . __LINE__ . " - 유효하지 않은 반응 유형: {$value}");
                $value = 'acceptance';
            }
            return "set_response_type: {$value}";
        };

        // 저항 수준 설정
        $this->handlers['set_resistance_level'] = function($value, $context) {
            $validLevels = ['none', 'mild', 'moderate', 'strong', 'severe'];
            if (!in_array($value, $validLevels)) {
                $value = 'none';
            }
            return "set_resistance_level: {$value}";
        };

        // 개입 강도 설정
        $this->handlers['set_intervention_intensity'] = function($value, $context) {
            $validIntensities = ['gentle', 'moderate', 'firm', 'intensive'];
            if (!in_array($value, $validIntensities)) {
                $value = 'moderate';
            }
            return "set_intervention_intensity: {$value}";
        };

        // 후속 조치 설정
        $this->handlers['set_followup_action'] = function($value, $context) {
            $validActions = ['none', 'reminder', 'check_in', 'escalate', 'parent_notify'];
            if (!in_array($value, $validActions)) {
                $value = 'none';
            }
            return "set_followup_action: {$value}";
        };

        // 대기 시간 설정 (무응답/지연 반응용)
        $this->handlers['set_wait_duration'] = function($value, $context) {
            // value는 시간 문자열 (예: "24h", "3d", "1w")
            return "set_wait_duration: {$value}";
        };

        // 리마인더 스케줄 설정
        $this->handlers['schedule_reminder'] = function($value, $context) {
            return "schedule_reminder: {$value}";
        };

        // 대안 전략 활성화
        $this->handlers['activate_alternative_strategy'] = function($value, $context) {
            $validStrategies = ['simplify', 'gamify', 'parent_involve', 'peer_support', 'break_down'];
            if (!in_array($value, $validStrategies)) {
                $value = 'simplify';
            }
            return "activate_alternative_strategy: {$value}";
        };

        // 성공 기록
        $this->handlers['log_success'] = function($value, $context) {
            return "log_success: {$value}";
        };

        // 진행 상태 업데이트
        $this->handlers['update_progress'] = function($value, $context) {
            return "update_progress: {$value}";
        };
    }

    /**
     * 커스텀 액션 핸들러 등록
     *
     * @param string $actionName 액션 이름
     * @param callable $handler 핸들러 함수
     */
    public function registerHandler(string $actionName, callable $handler): void {
        $this->handlers[$actionName] = $handler;
    }

    /**
     * 핸들러 존재 여부 확인
     *
     * @param string $actionName 액션 이름
     * @return bool 존재 여부
     */
    public function hasHandler(string $actionName): bool {
        return isset($this->handlers[$actionName]);
    }

    /**
     * 등록된 핸들러 목록 반환
     *
     * @return array 핸들러 이름 목록
     */
    public function getRegisteredHandlers(): array {
        return array_keys($this->handlers);
    }

    /**
     * 실행 로그 기록
     *
     * @param string $action 액션
     * @param string $result 결과
     */
    private function logExecution(string $action, string $result): void {
        $this->executionLog[] = [
            'action' => $action,
            'result' => $result,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * 실행 로그 반환
     *
     * @return array 실행 로그
     */
    public function getExecutionLog(): array {
        return $this->executionLog;
    }

    /**
     * 실행 로그 초기화
     */
    public function clearExecutionLog(): void {
        $this->executionLog = [];
    }

    /**
     * 액션 배치 실행 (트랜잭션 방식)
     *
     * @param array $actionGroups 액션 그룹 배열
     * @param array $context 컨텍스트
     * @return array 그룹별 실행 결과
     */
    public function executeBatch(array $actionGroups, array $context = []): array {
        $batchResults = [];

        foreach ($actionGroups as $groupName => $actions) {
            $batchResults[$groupName] = $this->execute($actions, $context);
        }

        return $batchResults;
    }

    /**
     * 액션 유효성 검증
     *
     * @param string $action 액션 문자열
     * @return array 검증 결과 ['valid' => bool, 'message' => string]
     */
    public function validateAction(string $action): array {
        $parts = explode(':', $action, 2);
        $actionName = trim($parts[0]);

        if (empty($actionName)) {
            return ['valid' => false, 'message' => '액션 이름이 비어있습니다'];
        }

        // 기본 핸들러 또는 커스텀 핸들러 존재 확인
        if (!$this->hasHandler($actionName)) {
            return ['valid' => true, 'message' => '기본 처리 적용 (핸들러 없음)'];
        }

        return ['valid' => true, 'message' => '유효한 액션'];
    }
}

/*
 * 지원 액션 타입 (기본):
 * - identify_persona: 페르소나 식별
 * - set_tone: 톤 설정 (Professional, Warm, Encouraging, Calm, Playful, Direct, Empathetic, Patient, Assertive)
 * - set_pace: 페이스 설정 (slow, normal, fast, adaptive, patient)
 * - prioritize_intervention: 개입 우선순위 (EmotionalSupport, InformationProvision, SkillBuilding, etc.)
 * - set_information_depth: 정보 깊이 (surface, moderate, deep, comprehensive)
 * - add_flag: 플래그 추가
 * - set_response_style: 응답 스타일 (concise, detailed, structured, conversational, technical, supportive)
 * - use_examples: 예시 사용 수준 (none, minimal, moderate, extensive)
 * - set_encouragement_level: 격려 수준 (low, medium, high, very_high)
 * - set_feedback_frequency: 피드백 빈도 (minimal, standard, frequent, continuous)
 * - set_question_style: 질문 스타일 (open, closed, guided, socratic, reflective)
 * - update_context: 컨텍스트 업데이트
 * - trigger_escalation: 에스컬레이션 트리거 (monitor, alert, human_review, immediate)
 *
 * Agent21 개입 특화 액션:
 * - set_response_type: 반응 유형 (acceptance, resistance, no_response, delayed)
 * - set_resistance_level: 저항 수준 (none, mild, moderate, strong, severe)
 * - set_intervention_intensity: 개입 강도 (gentle, moderate, firm, intensive)
 * - set_followup_action: 후속 조치 (none, reminder, check_in, escalate, parent_notify)
 * - set_wait_duration: 대기 시간 설정
 * - schedule_reminder: 리마인더 스케줄
 * - activate_alternative_strategy: 대안 전략 (simplify, gamify, parent_involve, peer_support, break_down)
 * - log_success: 성공 기록
 * - update_progress: 진행 상태 업데이트
 */
