<?php
require_once __DIR__ . '/ErrorResponder.php';

/**
 * Class AuthorizationService
 * ---------------------------
 * Handles scalable, role-based access control.
 * ✅ Refactored to support test mode and avoid exit().
 * Returns structured payloads in test mode and clean JSON in production.
 */
class AuthorizationService {
    private ErrorResponder $err;
    private array $constants;
    private string $appMode;

    public function __construct() {
        $this->err = new ErrorResponder();

        // Load constants
        $this->constants = json_decode(
            file_get_contents(__DIR__ . '/../config/constants.json'),
            true
        );

        // Detect environment mode from config.local.json
        $configPath = __DIR__ . '/../config/config.local.json';
        if (file_exists($configPath)) {
            $config = json_decode(file_get_contents($configPath), true);
            $this->appMode = $config['app_mode'] ?? 'prod';
        } else {
            $this->appMode = 'prod';
        }
    }

    /**
     * Validate user role against allowed roles.
     *
     * @param array $user Authenticated user payload (from AuthenticationMiddleware)
     * @param array $allowedRoles List of permitted role strings
     * @return array|null Returns structured payload in test mode, null in production
     */
    public function requireRoles(array $user, array $allowedRoles): ?array {
        $E = $this->err->errors();

        // === Check if user’s role is authorized ===
        if (!in_array($user['role'], $allowedRoles, true)) {
            $payload = $this->err->send(
                $E['ERR_ID_907'] ?? [
                    'id' => 'ERR_ID_907',
                    'message' => 'Authorization failed — access denied for this role.'
                ],
                [
                    "required_roles" => $allowedRoles,
                    "user_role"      => $user['role']
                ]
            );

            return $this->handleReturn($payload);
        }

        // ✅ Authorized — no errors
        return null;
    }

    /**
     * Handles environment-aware output:
     * - In "test" mode → returns payload directly.
     * - In "prod" mode → echoes JSON response.
     *
     * @param array|null $payload Error payload from ErrorResponder
     * @return array|null
     */
    private function handleReturn(?array $payload): ?array {
        if ($this->appMode === 'test') {
            return $payload; // Used in PHPUnit assertions
        }

        // In production — output clean JSON response
        if ($payload) {
            header('Content-Type: application/json');
            echo json_encode($payload, JSON_PRETTY_PRINT);
        }

        return null;
    }
}
