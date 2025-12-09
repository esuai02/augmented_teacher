<?php
// 파일: mvp_system/lib/logger.php (Line 1)
// Mathking Agentic MVP System - Logging Utility

require_once(__DIR__ . '/../config/app.config.php');

/**
 * MVP Logger Class
 * 구조화된 로깅 with 파일명/라인번호 추적
 */
class MVPLogger {
    private $component;
    private $log_file;
    private $log_level;

    const LEVEL_DEBUG = 0;
    const LEVEL_INFO = 1;
    const LEVEL_WARNING = 2;
    const LEVEL_ERROR = 3;

    /**
     * Constructor
     * @param string $component Component name (e.g., 'sensing', 'decision')
     */
    public function __construct($component) {
        $this->component = $component;
        $this->log_file = mvp_config('MVP_LOG_PATH', __DIR__ . '/../logs') . "/{$component}.log";
        $this->log_level = $this->get_level_from_config();

        // Ensure log directory exists
        $log_dir = dirname($this->log_file);
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
    }

    /**
     * Log INFO message
     * @param string $message Log message
     * @param array $context Additional context data
     */
    public function info($message, $context = []) {
        if ($this->log_level <= self::LEVEL_INFO) {
            $this->write_log('INFO', $message, $context);
        }
    }

    /**
     * Log WARNING message
     * @param string $message Log message
     * @param array $context Additional context data
     */
    public function warning($message, $context = []) {
        if ($this->log_level <= self::LEVEL_WARNING) {
            $this->write_log('WARNING', $message, $context);
        }
    }

    /**
     * Log ERROR message
     * @param string $message Log message
     * @param Exception|null $exception Exception object
     * @param array $context Additional context data
     */
    public function error($message, $exception = null, $context = []) {
        if ($this->log_level <= self::LEVEL_ERROR) {
            if ($exception) {
                $context['exception'] = $exception->getMessage();
                $context['trace'] = $exception->getTraceAsString();
            }
            $this->write_log('ERROR', $message, $context);
        }
    }

    /**
     * Log DEBUG message
     * @param string $message Log message
     * @param array $context Additional context data
     */
    public function debug($message, $context = []) {
        if ($this->log_level <= self::LEVEL_DEBUG) {
            $this->write_log('DEBUG', $message, $context);
        }
    }

    /**
     * Write log entry to file
     * @param string $level Log level
     * @param string $message Log message
     * @param array $context Additional context
     */
    private function write_log($level, $message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');

        // Get caller information
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $caller = isset($backtrace[2]) ? $backtrace[2] : $backtrace[1];
        $file = isset($caller['file']) ? basename($caller['file']) : 'unknown';
        $line = isset($caller['line']) ? $caller['line'] : 0;

        // Build log entry
        $log_entry = sprintf(
            "[%s] [%s] [%s] %s | File: %s:%d",
            $timestamp,
            $level,
            $this->component,
            $message,
            $file,
            $line
        );

        // Add context if provided
        if (!empty($context)) {
            $log_entry .= " | Context: " . json_encode($context, JSON_UNESCAPED_UNICODE);
        }

        $log_entry .= PHP_EOL;

        // Write to file
        if (mvp_config('LOG_TO_FILE', true)) {
            file_put_contents($this->log_file, $log_entry, FILE_APPEND | LOCK_EX);
        }

        // Also write to PHP error_log for ERROR level
        if ($level === 'ERROR') {
            error_log($log_entry);
        }

        // Echo to console if CLI mode
        if (mvp_is_cli()) {
            echo $log_entry;
        }
    }

    /**
     * Get log level from config
     * @return int Log level constant
     */
    private function get_level_from_config() {
        $config_level = mvp_config('LOG_LEVEL', 'INFO');

        $levels = [
            'DEBUG' => self::LEVEL_DEBUG,
            'INFO' => self::LEVEL_INFO,
            'WARNING' => self::LEVEL_WARNING,
            'ERROR' => self::LEVEL_ERROR
        ];

        return isset($levels[$config_level]) ? $levels[$config_level] : self::LEVEL_INFO;
    }

    /**
     * Get log file path
     * @return string Log file path
     */
    public function get_log_file() {
        return $this->log_file;
    }

    /**
     * Clear log file
     */
    public function clear_log() {
        if (file_exists($this->log_file)) {
            unlink($this->log_file);
            $this->info("Log file cleared");
        }
    }

    /**
     * Get recent log entries
     * @param int $lines Number of lines to retrieve
     * @return array Log entries
     */
    public function get_recent_logs($lines = 50) {
        if (!file_exists($this->log_file)) {
            return [];
        }

        $log_content = file_get_contents($this->log_file);
        $log_lines = explode(PHP_EOL, $log_content);

        return array_slice(array_reverse(array_filter($log_lines)), 0, $lines);
    }
}
?>
