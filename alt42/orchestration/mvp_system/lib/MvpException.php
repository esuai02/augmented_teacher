<?php
// File: lib/MvpException.php

/**
 * Base exception class for MVP system
 */
class MvpException extends Exception {

    protected $context = [];

    public function __construct($message = "", $code = 0, array $context = []) {
        parent::__construct($message, $code);
        $this->context = $context;
    }

    /**
     * Get exception context (SQL, params, etc.)
     * @return array
     */
    public function getContext() {
        return $this->context;
    }

    /**
     * Get detailed error message with context
     * @return string
     */
    public function getDetailedMessage() {
        $file = $this->getFile();
        $line = $this->getLine();
        $details = "[{$file}:{$line}] " . $this->getMessage();
        if (!empty($this->context)) {
            $details .= "\nContext: " . json_encode($this->context, JSON_PRETTY_PRINT);
        }
        return $details;
    }

    /**
     * Format exception for log file
     * @return string
     */
    public function toLogFormat() {
        $timestamp = date('Y-m-d H:i:s');
        $class = get_class($this);
        $message = $this->getMessage();
        $file = $this->getFile();
        $line = $this->getLine();

        $log = "[{$timestamp}] [{$class}] {$file}:{$line}\n";
        $log .= "Message: {$message}\n";

        if (!empty($this->context)) {
            foreach ($this->context as $key => $value) {
                if (is_array($value)) {
                    $value = json_encode($value);
                }
                $log .= "{$key}: {$value}\n";
            }
        }

        $log .= "Stack Trace:\n" . $this->getTraceAsString() . "\n";
        $log .= str_repeat('-', 80) . "\n";

        return $log;
    }
}

/**
 * Connection-related exceptions
 * Recovery: Auto-reconnect (max 3 attempts)
 */
class MvpConnectionException extends MvpException {
    // Inherits all base functionality
}

/**
 * Query execution exceptions
 * Recovery: Transaction rollback, detailed logging
 */
class MvpQueryException extends MvpException {
    // Inherits all base functionality
}

/**
 * Data validation exceptions
 * Recovery: User-friendly error messages
 */
class MvpDataException extends MvpException {
    // Inherits all base functionality
}
?>
