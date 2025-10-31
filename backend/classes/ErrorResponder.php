<?php
/**
 * Centralized API error responder.
 * Works in both production and test environments.
 * 
 * ✅ Returns structured payload (no exit)
 * The API endpoint decides whether to echo or return it.
 */
class ErrorResponder {
    private array $constants;
    private string $appMode;

    public function __construct() {
        $this->constants = json_decode(
            file_get_contents(__DIR__ . '/../config/constants.json'),
            true
        );

        // Determine app mode
        $configPath = __DIR__ . '/../config/config.local.json';
        if (getenv('APP_MODE')) {
            $this->appMode = getenv('APP_MODE');
        } elseif (file_exists($configPath)) {
            $config = json_decode(file_get_contents($configPath), true);
            $this->appMode = $config['app_mode'] ?? 'prod';
        } else {
            $this->appMode = 'prod';
        }
    }

    /**
     * Build a standardized error response payload.
     *
     * @param array $error  Error entry from constants.json
     * @param array $extra  Additional context or details
     * @return array Structured error payload
     */
    public function send(array $error, array $extra = []): array {
        $payload = [
            'error_id'   => $error['id'] ?? 'ERR_ID_910',
            'error_code' => $error['code'] ?? 500,
            'message'    => $error['message'] ?? 'Unexpected server error',
        ];

        if (!empty($extra)) {
            $payload['details'] = $extra;
        }

        // Return — do not exit
        return $payload;
    }

    /** Get all available error constants */
    public function errors(): array {
        return $this->constants['ERRORS'] ?? [];
    }
}
