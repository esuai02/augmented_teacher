<?php
/**
 * AgentErrorHandler.php
 *
 * 에이전트 표준 에러 핸들러 - 모든 에이전트에서 공통 사용
 * 일관된 에러 로깅, 보고, 복구 메커니즘 제공
 *
 * @package     AugmentedTeacher
 * @subpackage  EngineCore/Errors
 * @author      AI Agent Integration Team
 * @version     1.0.0
 * @created     2025-12-09
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents_1204/engine_core/errors/AgentErrorHandler.php
 */

defined('MOODLE_INTERNAL') || die();

/**
 * 에러 심각도 레벨
 */
class ErrorSeverity {
    const DEBUG     = 'DEBUG';
    const INFO      = 'INFO';
    const WARNING   = 'WARNING';
    const ERROR     = 'ERROR';
    const CRITICAL  = 'CRITICAL';

    /**
     * 심각도 레벨 우선순위 반환
     */
    public static function getPriority(string $level): int {
        $priorities = [
            self::DEBUG     => 1,
            self::INFO      => 2,
            self::WARNING   => 3,
            self::ERROR     => 4,
            self::CRITICAL  => 5
        ];
        return $priorities[$level] ?? 0;
    }
}

/**
 * 에이전트 에러 핸들러
 */
class AgentErrorHandler {

    /**
     * @var string 현재 에이전트 ID
     */
    private $agentId;

    /**
     * @var array 에러 이력
     */
    private $errorHistory = [];

    /**
     * @var string 최소 로깅 레벨
     */
    private $minLogLevel = ErrorSeverity::INFO;

    /**
     * @var bool DB 로깅 활성화 여부
     */
    private $dbLoggingEnabled = true;

    /**
     * @var int 최대 에러 이력 수
     */
    private const MAX_HISTORY = 100;

    /**
     * 생성자
     *
     * @param string $agentId 에이전트 ID (예: 'Agent04', 'Agent21')
     * @param string $minLogLevel 최소 로깅 레벨
     */
    public function __construct(string $agentId, string $minLogLevel = ErrorSeverity::INFO) {
        $this->agentId = $agentId;
        $this->minLogLevel = $minLogLevel;
    }

    /**
     * Exception 처리 (주요 메서드)
     *
     * @param Exception $e 예외 객체
     * @param string $context 추가 컨텍스트 정보
     * @param string $severity 에러 심각도
     * @return array 에러 응답 데이터
     */
    public static function handle(Exception $e, string $agentId, string $context = '', string $severity = ErrorSeverity::ERROR): array {
        $errorData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'agent_id' => $agentId,
            'severity' => $severity,
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'message' => $e->getMessage(),
            'context' => $context,
            'code' => $e->getCode(),
            'trace' => self::formatTrace($e->getTraceAsString())
        ];

        // 에러 로깅
        self::logToFile($errorData);

        // API 응답용 데이터 반환
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'error_code' => self::getErrorCode($e),
            'file' => basename($e->getFile()),
            'line' => $e->getLine(),
            'agent_id' => $agentId,
            'timestamp' => $errorData['timestamp']
        ];
    }

    /**
     * 에러 로그 기록 (인스턴스 메서드)
     *
     * @param string $message 에러 메시지
     * @param string $severity 심각도
     * @param array $data 추가 데이터
     */
    public function log(string $message, string $severity = ErrorSeverity::ERROR, array $data = []): void {
        // 최소 레벨 체크
        if (ErrorSeverity::getPriority($severity) < ErrorSeverity::getPriority($this->minLogLevel)) {
            return;
        }

        $errorData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'agent_id' => $this->agentId,
            'severity' => $severity,
            'message' => $message,
            'data' => $data,
            'file' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['file'] ?? 'unknown',
            'line' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['line'] ?? 0
        ];

        // 에러 이력에 추가
        $this->addToHistory($errorData);

        // 파일 로그
        self::logToFile($errorData);

        // DB 로그 (활성화된 경우)
        if ($this->dbLoggingEnabled && $severity !== ErrorSeverity::DEBUG) {
            $this->logToDb($errorData);
        }
    }

    /**
     * 에러 코드 생성
     *
     * @param Exception $e 예외 객체
     * @return string 에러 코드
     */
    private static function getErrorCode(Exception $e): string {
        $exceptionClass = get_class($e);

        $codeMap = [
            'dml_exception' => 'DB_ERROR',
            'moodle_exception' => 'MOODLE_ERROR',
            'InvalidArgumentException' => 'INVALID_ARGUMENT',
            'RuntimeException' => 'RUNTIME_ERROR',
            'TypeError' => 'TYPE_ERROR'
        ];

        foreach ($codeMap as $class => $code) {
            if (stripos($exceptionClass, $class) !== false) {
                return $code;
            }
        }

        if ($e->getCode()) {
            return 'ERR_' . $e->getCode();
        }

        return 'UNKNOWN_ERROR';
    }

    /**
     * 스택 트레이스 포맷팅 (간소화)
     *
     * @param string $trace 원본 트레이스
     * @return string 포맷된 트레이스
     */
    private static function formatTrace(string $trace): string {
        // 트레이스를 최대 5줄로 제한
        $lines = explode("\n", $trace);
        $formatted = array_slice($lines, 0, 5);
        return implode("\n", $formatted);
    }

    /**
     * 파일 로그 기록
     *
     * @param array $errorData 에러 데이터
     */
    private static function logToFile(array $errorData): void {
        $logMessage = sprintf(
            "[%s][%s][%s] %s - %s:%d",
            $errorData['timestamp'],
            $errorData['agent_id'],
            $errorData['severity'] ?? 'ERROR',
            $errorData['message'],
            basename($errorData['file'] ?? 'unknown'),
            $errorData['line'] ?? 0
        );

        if (!empty($errorData['context'])) {
            $logMessage .= " | Context: " . $errorData['context'];
        }

        error_log($logMessage);
    }

    /**
     * DB 로그 기록
     *
     * @param array $errorData 에러 데이터
     */
    private function logToDb(array $errorData): void {
        global $DB;

        try {
            // mdl_at_agent_logs 테이블에 기록
            $record = new stdClass();
            $record->agent_id = $this->agentId;
            $record->severity = $errorData['severity'];
            $record->message = substr($errorData['message'], 0, 1000); // 1000자 제한
            $record->file_path = $errorData['file'] ?? '';
            $record->line_number = $errorData['line'] ?? 0;
            $record->context_data = json_encode($errorData['data'] ?? []);
            $record->created_at = time();

            // 테이블 존재 확인 후 삽입
            if ($DB->get_manager()->table_exists(new xmldb_table('at_agent_logs'))) {
                $DB->insert_record('at_agent_logs', $record);
            }
        } catch (Exception $e) {
            // DB 로깅 실패 시 파일 로그로 대체
            error_log("[AgentErrorHandler] DB logging failed: " . $e->getMessage());
        }
    }

    /**
     * 에러 이력에 추가
     *
     * @param array $errorData 에러 데이터
     */
    private function addToHistory(array $errorData): void {
        $this->errorHistory[] = $errorData;

        // 최대 이력 수 제한
        if (count($this->errorHistory) > self::MAX_HISTORY) {
            array_shift($this->errorHistory);
        }
    }

    /**
     * 에러 이력 조회
     *
     * @param string|null $severity 필터링할 심각도
     * @return array 에러 이력
     */
    public function getHistory(?string $severity = null): array {
        if ($severity === null) {
            return $this->errorHistory;
        }

        return array_filter($this->errorHistory, function($error) use ($severity) {
            return $error['severity'] === $severity;
        });
    }

    /**
     * DB 로깅 활성화/비활성화
     *
     * @param bool $enabled
     */
    public function setDbLogging(bool $enabled): void {
        $this->dbLoggingEnabled = $enabled;
    }

    /**
     * 최소 로깅 레벨 설정
     *
     * @param string $level
     */
    public function setMinLogLevel(string $level): void {
        $this->minLogLevel = $level;
    }

    /**
     * JSON 응답 생성 (API용)
     *
     * @param Exception $e 예외 객체
     * @param string $agentId 에이전트 ID
     * @param bool $outputAndExit 출력 후 종료 여부
     * @return string JSON 문자열
     */
    public static function jsonResponse(Exception $e, string $agentId, bool $outputAndExit = true): string {
        $response = self::handle($e, $agentId);
        $json = json_encode($response, JSON_UNESCAPED_UNICODE);

        if ($outputAndExit) {
            header('Content-Type: application/json; charset=utf-8');
            echo $json;
            exit;
        }

        return $json;
    }

    /**
     * Try-Catch 래퍼 (콜백 함수 실행)
     *
     * @param callable $callback 실행할 함수
     * @param string $agentId 에이전트 ID
     * @param string $context 컨텍스트
     * @param mixed $defaultValue 에러 시 기본값
     * @return mixed 실행 결과 또는 기본값
     */
    public static function tryExecute(callable $callback, string $agentId, string $context = '', $defaultValue = null) {
        try {
            return $callback();
        } catch (Exception $e) {
            self::handle($e, $agentId, $context);
            return $defaultValue;
        }
    }
}

/**
 * 헬퍼 함수: 간단한 에러 처리
 *
 * @param Exception $e 예외 객체
 * @param string $agentId 에이전트 ID
 * @param string $context 컨텍스트
 * @return array 에러 응답
 */
function handle_agent_error(Exception $e, string $agentId, string $context = ''): array {
    return AgentErrorHandler::handle($e, $agentId, $context);
}

/**
 * 헬퍼 함수: JSON 에러 응답
 *
 * @param Exception $e 예외 객체
 * @param string $agentId 에이전트 ID
 */
function json_error_response(Exception $e, string $agentId): void {
    AgentErrorHandler::jsonResponse($e, $agentId, true);
}

/*
 * ═══════════════════════════════════════════════════════════════════════════
 * 사용 예시
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * // 방법 1: 정적 메서드 사용 (간단한 에러 처리)
 * require_once(__DIR__ . '/../engine_core/errors/AgentErrorHandler.php');
 *
 * try {
 *     // 에이전트 로직
 *     $result = someRiskyOperation();
 * } catch (Exception $e) {
 *     $errorResponse = AgentErrorHandler::handle($e, 'Agent04', '학생 데이터 조회 중');
 *     echo json_encode($errorResponse);
 *     exit;
 * }
 *
 * // 방법 2: 인스턴스 메서드 사용 (상세 로깅)
 * $errorHandler = new AgentErrorHandler('Agent04');
 * $errorHandler->log('데이터베이스 연결 지연', ErrorSeverity::WARNING, ['delay_ms' => 1500]);
 *
 * // 방법 3: Try-Catch 래퍼 사용
 * $result = AgentErrorHandler::tryExecute(
 *     function() use ($studentId) {
 *         return getStudentData($studentId);
 *     },
 *     'Agent04',
 *     '학생 데이터 조회',
 *     []  // 기본값
 * );
 *
 * // 방법 4: JSON API 응답
 * try {
 *     // API 로직
 * } catch (Exception $e) {
 *     json_error_response($e, 'Agent04'); // 자동 exit
 * }
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * DB 관련 정보
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * 참조 테이블: mdl_at_agent_logs
 *
 * 필드:
 * - id (int): PK
 * - agent_id (varchar): 에이전트 ID
 * - severity (varchar): 에러 심각도
 * - message (text): 에러 메시지
 * - file_path (varchar): 파일 경로
 * - line_number (int): 라인 번호
 * - context_data (text): JSON 형식 추가 데이터
 * - created_at (int): 생성 시간 (timestamp)
 *
 * ═══════════════════════════════════════════════════════════════════════════
 */
