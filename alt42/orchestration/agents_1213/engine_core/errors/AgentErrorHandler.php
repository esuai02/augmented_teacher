<?php
/**
 * AgentErrorHandler.php
 *
 * 에이전트 표준 에러 핸들러 클래스
 * 모든 에이전트에서 사용하는 통일된 에러 처리 로직
 *
 * @package     AugmentedTeacher
 * @subpackage  EngineCore
 * @author      AI Agent Integration Team
 * @version     1.0.0
 * @created     2025-12-09
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/engine_core/errors/AgentErrorHandler.php
 *
 * 사용 예시:
 * ```php
 * try {
 *     // 에이전트 로직
 *     $result = $agent->execute($params);
 * } catch (Exception $e) {
 *     $response = AgentErrorHandler::handle($e, 'agent04', 'weakpoint_analysis');
 *     echo json_encode($response);
 *     exit;
 * }
 * ```
 *
 * 에러 코드 범위:
 * - 1000-1999: 데이터베이스 에러
 * - 2000-2999: 검증 에러
 * - 3000-3999: 인증/권한 에러
 * - 4000-4999: 외부 서비스 에러
 * - 5000-5999: 시스템 에러
 * - 9000-9999: 알 수 없는 에러
 */

defined('MOODLE_INTERNAL') || die();

class AgentErrorHandler
{
    /** @var string 로그 파일 경로 */
    private static $logPath = '';

    /** @var bool 디버그 모드 */
    private static $debugMode = false;

    /** @var array 에러 코드 매핑 */
    private static $errorCodeMap = [
        // 데이터베이스 에러
        'dml_exception' => 1001,
        'dml_read_exception' => 1002,
        'dml_write_exception' => 1003,
        'dml_connection_exception' => 1004,

        // 검증 에러
        'validation_error' => 2001,
        'invalid_parameter' => 2002,
        'missing_required_field' => 2003,
        'data_type_mismatch' => 2004,

        // 인증/권한 에러
        'require_login_exception' => 3001,
        'moodle_exception' => 3002,
        'access_denied' => 3003,

        // 외부 서비스 에러
        'curl_exception' => 4001,
        'api_timeout' => 4002,
        'python_execution_error' => 4003,

        // 시스템 에러
        'file_not_found' => 5001,
        'permission_denied' => 5002,
        'memory_limit' => 5003,
        'timeout' => 5004,

        // 기본값
        'unknown' => 9999
    ];

    /** @var array 심각도 레벨 */
    private static $severityLevels = [
        'critical' => 1,   // 즉시 조치 필요
        'error' => 2,      // 기능 실패
        'warning' => 3,    // 잠재적 문제
        'notice' => 4,     // 정보성 알림
        'debug' => 5       // 디버깅용
    ];

    /**
     * 에러 핸들러 초기화
     *
     * @param string $logPath 로그 파일 경로
     * @param bool $debugMode 디버그 모드 활성화
     */
    public static function init(string $logPath = '', bool $debugMode = false): void
    {
        self::$logPath = $logPath;
        self::$debugMode = $debugMode;
    }

    /**
     * 예외 처리 메인 메서드
     *
     * @param Exception $e 예외 객체
     * @param string $agentId 에이전트 식별자
     * @param string $context 추가 컨텍스트 정보
     * @return array 표준화된 에러 응답
     */
    public static function handle(Exception $e, string $agentId, string $context = ''): array
    {
        $errorData = self::buildErrorData($e, $agentId, $context);

        // 로깅
        self::logError($errorData);

        // 심각한 에러는 추가 알림
        if ($errorData['severity'] === 'critical') {
            self::notifyCriticalError($errorData);
        }

        // 응답 생성
        return self::buildResponse($errorData);
    }

    /**
     * 에러 데이터 구조 생성
     *
     * @param Exception $e 예외 객체
     * @param string $agentId 에이전트 식별자
     * @param string $context 추가 컨텍스트
     * @return array 에러 데이터
     */
    private static function buildErrorData(Exception $e, string $agentId, string $context): array
    {
        $errorType = self::getErrorType($e);
        $severity = self::determineSeverity($e);

        return [
            'timestamp' => date('Y-m-d H:i:s'),
            'agent_id' => $agentId,
            'error_type' => $errorType,
            'error_code' => self::getErrorCode($errorType),
            'severity' => $severity,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'context' => $context,
            'trace' => self::$debugMode ? $e->getTraceAsString() : self::getSimplifiedTrace($e),
            'previous' => $e->getPrevious() ? $e->getPrevious()->getMessage() : null,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        ];
    }

    /**
     * 예외 타입 식별
     *
     * @param Exception $e 예외 객체
     * @return string 에러 타입
     */
    private static function getErrorType(Exception $e): string
    {
        $className = strtolower(get_class($e));

        // Moodle 특정 예외 처리
        if (strpos($className, 'dml') !== false) {
            if (strpos($className, 'read') !== false) {
                return 'dml_read_exception';
            }
            if (strpos($className, 'write') !== false) {
                return 'dml_write_exception';
            }
            if (strpos($className, 'connection') !== false) {
                return 'dml_connection_exception';
            }
            return 'dml_exception';
        }

        if (strpos($className, 'require_login') !== false) {
            return 'require_login_exception';
        }

        if (strpos($className, 'moodle') !== false) {
            return 'moodle_exception';
        }

        // 메시지 기반 타입 추론
        $message = strtolower($e->getMessage());

        if (strpos($message, 'validation') !== false || strpos($message, 'invalid') !== false) {
            return 'validation_error';
        }

        if (strpos($message, 'timeout') !== false) {
            return 'timeout';
        }

        if (strpos($message, 'permission') !== false || strpos($message, 'denied') !== false) {
            return 'permission_denied';
        }

        if (strpos($message, 'not found') !== false || strpos($message, 'does not exist') !== false) {
            return 'file_not_found';
        }

        if (strpos($message, 'memory') !== false) {
            return 'memory_limit';
        }

        if (strpos($message, 'python') !== false || strpos($message, 'script') !== false) {
            return 'python_execution_error';
        }

        return 'unknown';
    }

    /**
     * 에러 코드 조회
     *
     * @param string $errorType 에러 타입
     * @return int 에러 코드
     */
    public static function getErrorCode(string $errorType): int
    {
        return self::$errorCodeMap[$errorType] ?? self::$errorCodeMap['unknown'];
    }

    /**
     * 심각도 결정
     *
     * @param Exception $e 예외 객체
     * @return string 심각도 레벨
     */
    private static function determineSeverity(Exception $e): string
    {
        $errorType = self::getErrorType($e);

        // 데이터베이스 연결 에러는 critical
        if ($errorType === 'dml_connection_exception') {
            return 'critical';
        }

        // 메모리/타임아웃은 critical
        if (in_array($errorType, ['memory_limit', 'timeout'])) {
            return 'critical';
        }

        // 인증 관련은 error
        if (strpos($errorType, 'require_login') !== false || $errorType === 'access_denied') {
            return 'error';
        }

        // 검증 에러는 warning
        if (strpos($errorType, 'validation') !== false) {
            return 'warning';
        }

        // 기본값
        return 'error';
    }

    /**
     * 간소화된 스택 트레이스 생성
     *
     * @param Exception $e 예외 객체
     * @return string 간소화된 트레이스
     */
    private static function getSimplifiedTrace(Exception $e): string
    {
        $trace = $e->getTrace();
        $simplified = [];

        // 최대 5개의 프레임만 포함
        $maxFrames = min(5, count($trace));

        for ($i = 0; $i < $maxFrames; $i++) {
            $frame = $trace[$i];
            $file = isset($frame['file']) ? basename($frame['file']) : 'unknown';
            $line = $frame['line'] ?? '?';
            $function = $frame['function'] ?? 'unknown';
            $class = isset($frame['class']) ? $frame['class'] . '::' : '';

            $simplified[] = "#{$i} {$file}:{$line} {$class}{$function}()";
        }

        if (count($trace) > $maxFrames) {
            $simplified[] = "... and " . (count($trace) - $maxFrames) . " more frames";
        }

        return implode("\n", $simplified);
    }

    /**
     * 에러 로깅
     *
     * @param array $errorData 에러 데이터
     */
    private static function logError(array $errorData): void
    {
        $logMessage = sprintf(
            "[%s] [%s] [%s] [%s] %s at %s:%d | Context: %s",
            $errorData['timestamp'],
            strtoupper($errorData['severity']),
            $errorData['agent_id'],
            $errorData['error_code'],
            $errorData['message'],
            basename($errorData['file']),
            $errorData['line'],
            $errorData['context'] ?: 'none'
        );

        error_log($logMessage);

        // 커스텀 로그 파일에도 기록
        if (!empty(self::$logPath)) {
            $fullLog = json_encode($errorData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            file_put_contents(self::$logPath, $fullLog . "\n\n", FILE_APPEND | LOCK_EX);
        }
    }

    /**
     * 심각한 에러 알림 처리
     *
     * @param array $errorData 에러 데이터
     */
    private static function notifyCriticalError(array $errorData): void
    {
        // 추후 알림 시스템 연동 (Slack, Email 등)
        $criticalLog = sprintf(
            "[CRITICAL ALERT] Agent: %s, Error: %s, Code: %d, File: %s:%d",
            $errorData['agent_id'],
            $errorData['message'],
            $errorData['error_code'],
            basename($errorData['file']),
            $errorData['line']
        );

        error_log($criticalLog);
    }

    /**
     * 표준 응답 생성
     *
     * @param array $errorData 에러 데이터
     * @return array API 응답 형식
     */
    private static function buildResponse(array $errorData): array
    {
        $response = [
            'success' => false,
            'error' => [
                'code' => $errorData['error_code'],
                'message' => $errorData['message'],
                'type' => $errorData['error_type'],
                'severity' => $errorData['severity']
            ],
            'meta' => [
                'agent_id' => $errorData['agent_id'],
                'timestamp' => $errorData['timestamp'],
                'file' => basename($errorData['file']),
                'line' => $errorData['line']
            ]
        ];

        // 디버그 모드에서는 추가 정보 포함
        if (self::$debugMode) {
            $response['debug'] = [
                'context' => $errorData['context'],
                'trace' => $errorData['trace'],
                'memory_usage' => self::formatBytes($errorData['memory_usage']),
                'peak_memory' => self::formatBytes($errorData['peak_memory'])
            ];
        }

        return $response;
    }

    /**
     * 바이트를 읽기 쉬운 형식으로 변환
     *
     * @param int $bytes 바이트 수
     * @return string 포맷된 문자열
     */
    private static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.2f %s", $bytes / pow(1024, $factor), $units[$factor]);
    }

    /**
     * 수동 에러 로깅 (예외 없이 에러 기록)
     *
     * @param string $agentId 에이전트 ID
     * @param string $message 에러 메시지
     * @param string $severity 심각도 (error, warning, notice)
     * @param string $context 추가 컨텍스트
     * @param string $file 파일명 (__FILE__ 사용)
     * @param int $line 라인 번호 (__LINE__ 사용)
     */
    public static function log(
        string $agentId,
        string $message,
        string $severity = 'error',
        string $context = '',
        string $file = '',
        int $line = 0
    ): void {
        $logMessage = sprintf(
            "[%s] [%s] [%s] %s at %s:%d | Context: %s",
            date('Y-m-d H:i:s'),
            strtoupper($severity),
            $agentId,
            $message,
            $file ? basename($file) : 'unknown',
            $line,
            $context ?: 'none'
        );

        error_log($logMessage);
    }

    /**
     * 에러 코드로 메시지 조회
     *
     * @param int $errorCode 에러 코드
     * @return string 에러 타입 설명
     */
    public static function getErrorTypeByCode(int $errorCode): string
    {
        $flipped = array_flip(self::$errorCodeMap);
        return $flipped[$errorCode] ?? 'unknown';
    }

    /**
     * 특정 에이전트의 최근 에러 목록 조회 (DB 연동 필요시 확장)
     *
     * @param string $agentId 에이전트 ID
     * @param int $limit 조회 개수
     * @return array 에러 목록
     */
    public static function getRecentErrors(string $agentId, int $limit = 10): array
    {
        // TODO: DB 테이블 mdl_alt42_agent_errors 연동
        // 현재는 빈 배열 반환
        return [];
    }

    /**
     * 에러 통계 조회 (DB 연동 필요시 확장)
     *
     * @param string $agentId 에이전트 ID (null이면 전체)
     * @param string $period 기간 (day, week, month)
     * @return array 통계 데이터
     */
    public static function getErrorStats(?string $agentId = null, string $period = 'day'): array
    {
        // TODO: DB 테이블 연동하여 통계 생성
        return [
            'total' => 0,
            'by_severity' => [
                'critical' => 0,
                'error' => 0,
                'warning' => 0,
                'notice' => 0
            ],
            'by_type' => [],
            'period' => $period
        ];
    }
}

/**
 * 관련 DB 테이블 (향후 생성 필요):
 *
 * mdl_alt42_agent_errors:
 * - id (bigint, auto_increment)
 * - agent_id (varchar 50)
 * - error_code (int)
 * - error_type (varchar 50)
 * - severity (varchar 20)
 * - message (text)
 * - file (varchar 255)
 * - line (int)
 * - context (text)
 * - trace (text)
 * - student_id (bigint, nullable)
 * - memory_usage (bigint)
 * - created_at (timestamp)
 */
