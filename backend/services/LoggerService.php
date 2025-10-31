<?php
/**
 * Class LoggerService
 *
 * Handles structured, timestamped logging for all APIs.
 * Supports info, warning, and error levels.
 *
 * Example usage:
 *   $logger->info("Player 1 moved token P1_T2", ["session_id" => 101]);
 *   $logger->error("DB failure", ["error_id" => "ERR_ID_910"]);
 */
class LoggerService {
    private string $logFile;

    /**
     * Constructor sets the log file path.
     * Default: logs/app.log (created automatically if missing)
     */
    public function __construct(string $filePath = null) {
        $this->logFile = $filePath ?? __DIR__ . '/../logs/app.log';

        // Create logs directory if it doesn’t exist
        $dir = dirname($this->logFile);
        if (!file_exists($dir)) {
            mkdir($dir, 0775, true);
        }
    }

    /**
     * Write an INFO log entry.
     */
    public function info(string $message, array $context = []): void {
        $this->writeLog('INFO', $message, $context);
    }

    /**
     * Write a WARNING log entry.
     */
    public function warning(string $message, array $context = []): void {
        $this->writeLog('WARNING', $message, $context);
    }

    /**
     * Write an ERROR log entry.
     */
    public function error(string $message, array $context = []): void {
        $this->writeLog('ERROR', $message, $context);
    }

    /**
     * Core method to format and write logs.
     */
    private function writeLog(string $level, string $message, array $context = []): void {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = [
            'timestamp' => $timestamp,
            'level' => $level,
            'message' => $message,
            'context' => $context
        ];

        // Convert to JSON for consistency
        $logJson = json_encode($logEntry, JSON_UNESCAPED_SLASHES);
        file_put_contents($this->logFile, $logJson . PHP_EOL, FILE_APPEND);
    }
}
