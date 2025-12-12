<?php
/**
 * Agent18 Signature Routine - Action Executor
 *
 * 규칙 매칭 후 액션 실행 처리.
 *
 * @package Agent18_SignatureRoutine
 * @version 1.0
 * @created 2025-12-02
 *
 * File: /alt42/orchestration/agents/agent18_signature_routine/persona_system/engine/ActionExecutor.php
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

class ActionExecutor {

    /** @var DataContext 데이터 컨텍스트 */
    private $dataContext;

    /** @var array 실행된 액션 로그 */
    private $executionLog = [];

    /** @var array 지원하는 액션 타입 */
    private $supportedActions = [
        'identify_persona',
        'set_context',
        'set_tone',
        'set_recommendation',
        'flag',
        'store_pattern',
        'notify',
        'update_profile'
    ];

    /**
     * 생성자
     *
     * @param DataContext $dataContext 데이터 컨텍스트
     */
    public function __construct(DataContext $dataContext) {
        $this->dataContext = $dataContext;
    }

    /**
     * 액션 실행
     *
     * @param array $action 액션 정의
     * @return array 실행 결과
     */
    public function execute($action) {
        $type = $action['type'] ?? '';

        if (!in_array($type, $this->supportedActions)) {
            error_log("[Agent18 ActionExecutor] 지원하지 않는 액션 타입: {$type} at " .
                      __FILE__ . ":" . __LINE__);
            return [
                'success' => false,
                'error' => "지원하지 않는 액션 타입: {$type}"
            ];
        }

        try {
            $result = $this->executeAction($type, $action);

            $this->executionLog[] = [
                'action' => $action,
                'result' => $result,
                'executed_at' => time()
            ];

            return $result;

        } catch (Exception $e) {
            error_log("[Agent18 ActionExecutor] 액션 실행 오류: " . $e->getMessage() .
                      " at " . __FILE__ . ":" . __LINE__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 액션 타입별 실행
     *
     * @param string $type 액션 타입
     * @param array $action 액션 정의
     * @return array 실행 결과
     */
    private function executeAction($type, $action) {
        switch ($type) {
            case 'identify_persona':
                return $this->executeIdentifyPersona($action);

            case 'set_context':
                return $this->executeSetContext($action);

            case 'set_tone':
                return $this->executeSetTone($action);

            case 'set_recommendation':
                return $this->executeSetRecommendation($action);

            case 'flag':
                return $this->executeFlag($action);

            case 'store_pattern':
                return $this->executeStorePattern($action);

            case 'notify':
                return $this->executeNotify($action);

            case 'update_profile':
                return $this->executeUpdateProfile($action);

            default:
                return ['success' => false, 'error' => '알 수 없는 액션'];
        }
    }

    /**
     * 페르소나 식별 액션
     *
     * @param array $action 액션 정의
     * @return array 실행 결과
     */
    private function executeIdentifyPersona($action) {
        $personaId = $action['persona'] ?? '';
        $confidence = $action['confidence'] ?? 0.5;

        if (empty($personaId)) {
            return ['success' => false, 'error' => '페르소나 ID가 필요합니다'];
        }

        // 페르소나 정보를 세션 데이터에 추가
        $identifiedPersonas = $this->dataContext->getField('identified_personas') ?? [];
        $identifiedPersonas[$personaId] = [
            'confidence' => $confidence,
            'identified_at' => time()
        ];

        $this->dataContext->setIdentifiedPersonas($identifiedPersonas);

        return [
            'success' => true,
            'persona_id' => $personaId,
            'confidence' => $confidence
        ];
    }

    /**
     * 컨텍스트 설정 액션
     *
     * @param array $action 액션 정의
     * @return array 실행 결과
     */
    private function executeSetContext($action) {
        $context = $action['context'] ?? '';

        if (empty($context)) {
            return ['success' => false, 'error' => '컨텍스트가 필요합니다'];
        }

        return [
            'success' => true,
            'context' => $context
        ];
    }

    /**
     * 톤 설정 액션
     *
     * @param array $action 액션 정의
     * @return array 실행 결과
     */
    private function executeSetTone($action) {
        $tone = $action['value'] ?? 'friendly_exploratory';

        return [
            'success' => true,
            'tone' => $tone
        ];
    }

    /**
     * 추천 설정 액션
     *
     * @param array $action 액션 정의
     * @return array 실행 결과
     */
    private function executeSetRecommendation($action) {
        $recommendation = $action['value'] ?? '';

        return [
            'success' => true,
            'recommendation' => $recommendation
        ];
    }

    /**
     * 플래그 설정 액션
     *
     * @param array $action 액션 정의
     * @return array 실행 결과
     */
    private function executeFlag($action) {
        $flag = $action['flag'] ?? '';

        return [
            'success' => true,
            'flag' => $flag
        ];
    }

    /**
     * 패턴 저장 액션
     *
     * @param array $action 액션 정의
     * @return array 실행 결과
     */
    private function executeStorePattern($action) {
        global $DB;

        $patternType = $action['pattern_type'] ?? 'general';
        $patternData = $action['pattern_data'] ?? [];
        $confidence = $action['confidence'] ?? 0.5;

        $studentProfile = $this->dataContext->getStudentProfile();
        $userId = $studentProfile['id'] ?? 0;

        if (!$userId) {
            return ['success' => false, 'error' => '사용자 ID가 필요합니다'];
        }

        try {
            $record = new stdClass();
            $record->userid = $userId;
            $record->pattern_type = $patternType;
            $record->pattern_data = json_encode($patternData);
            $record->confidence = $confidence;
            $record->created_at = time();

            $recordId = $DB->insert_record('alt42_agent18_routine_patterns', $record);

            return [
                'success' => true,
                'record_id' => $recordId,
                'pattern_type' => $patternType
            ];

        } catch (Exception $e) {
            error_log("[Agent18 ActionExecutor] 패턴 저장 오류: " . $e->getMessage() .
                      " at " . __FILE__ . ":" . __LINE__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 알림 발송 액션
     *
     * @param array $action 액션 정의
     * @return array 실행 결과
     */
    private function executeNotify($action) {
        $notificationType = $action['notification_type'] ?? 'info';
        $message = $action['message'] ?? '';

        // 실제 알림 발송 로직은 별도 구현 필요
        return [
            'success' => true,
            'notification_type' => $notificationType,
            'message' => $message
        ];
    }

    /**
     * 프로필 업데이트 액션
     *
     * @param array $action 액션 정의
     * @return array 실행 결과
     */
    private function executeUpdateProfile($action) {
        global $DB;

        $field = $action['field'] ?? '';
        $value = $action['value'] ?? '';

        $studentProfile = $this->dataContext->getStudentProfile();
        $userId = $studentProfile['id'] ?? 0;

        if (!$userId || !$field) {
            return ['success' => false, 'error' => '사용자 ID와 필드가 필요합니다'];
        }

        try {
            // 학습 프로필 테이블에 저장
            $existing = $DB->get_record('alt42_agent18_user_profiles', [
                'userid' => $userId,
                'field_name' => $field
            ]);

            $record = new stdClass();
            $record->userid = $userId;
            $record->field_name = $field;
            $record->field_value = is_array($value) ? json_encode($value) : $value;
            $record->updated_at = time();

            if ($existing) {
                $record->id = $existing->id;
                $DB->update_record('alt42_agent18_user_profiles', $record);
            } else {
                $record->created_at = time();
                $DB->insert_record('alt42_agent18_user_profiles', $record);
            }

            return [
                'success' => true,
                'field' => $field,
                'value' => $value
            ];

        } catch (Exception $e) {
            error_log("[Agent18 ActionExecutor] 프로필 업데이트 오류: " . $e->getMessage() .
                      " at " . __FILE__ . ":" . __LINE__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 실행 로그 반환
     *
     * @return array 실행 로그
     */
    public function getExecutionLog() {
        return $this->executionLog;
    }

    /**
     * 배치 액션 실행
     *
     * @param array $actions 액션 목록
     * @return array 실행 결과 목록
     */
    public function executeBatch($actions) {
        $results = [];

        foreach ($actions as $action) {
            $results[] = $this->execute($action);
        }

        return $results;
    }
}

/*
 * DB 테이블 정보:
 *
 * 1. mdl_alt42_agent18_routine_patterns
 *    - id: bigint(10) AUTO_INCREMENT
 *    - userid: bigint(10) NOT NULL
 *    - pattern_type: varchar(50) NOT NULL
 *    - pattern_data: text NOT NULL
 *    - confidence: decimal(3,2) NOT NULL
 *    - created_at: bigint(10) NOT NULL
 *    - updated_at: bigint(10)
 *    - notified: tinyint(1) DEFAULT 0
 *    - golden_time_notified: tinyint(1) DEFAULT 0
 *
 * 2. mdl_alt42_agent18_user_profiles
 *    - id: bigint(10) AUTO_INCREMENT
 *    - userid: bigint(10) NOT NULL
 *    - field_name: varchar(100) NOT NULL
 *    - field_value: text
 *    - created_at: bigint(10) NOT NULL
 *    - updated_at: bigint(10)
 */
