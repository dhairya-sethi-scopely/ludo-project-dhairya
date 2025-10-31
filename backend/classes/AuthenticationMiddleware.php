<?php
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/ErrorResponder.php';
require_once __DIR__ . '/UserRepository.php';

/**
 * AuthenticationMiddleware
 * -------------------------
 * Validates JWT for real users, bypasses authentication for AI players.
 * Works in both production and test modes.
 */
class AuthenticationMiddleware {
    private UserRepository $repo;
    private ErrorResponder $err;
    private array $constants;
    private string $appMode;

    public function __construct(UserRepository $repo) {
        
        $this->repo = $repo;
        $this->err = new ErrorResponder();

        // === Load constants ===
        $this->constants = json_decode(
            file_get_contents(__DIR__ . '/../config/constants.json'),
            true
        );

        // === Detect environment mode ===
        $configPath = __DIR__ . '/../config/config.local.json';
        if (file_exists($configPath)) {
            $config = json_decode(file_get_contents($configPath), true);
            $this->appMode = $config['app_mode'] ?? 'prod';
        } else {
            $this->appMode = 'prod';
        }
    }

    /**
     * Authenticate the incoming request.
     * - Returns decoded JWT payload for valid users.
     * - Returns AI identity if AI mode detected.
     * - Returns structured error payload for failures.
     */
    public function check(): ?array {
        $E = $this->err->errors();
        $cookieName = $this->constants['JWT_COOKIE_NAME'] ?? 'accessToken';

        // === STEP 0: Handle AI Mode ===
        // If the request is marked as AI (cookie or header), bypass JWT.
        if (isset($_COOKIE['AI_MODE'])) {
                error_log("AI mode detected");
            return [
               'sub'       => 9999,
                'username'  => 'AI_Player',
                'role'      => 'ai',
                'userHash'  => hash(
                    $this->constants['REQUEST_HASH_ALGO'] ?? 'sha256',
                    'AI_Player' . 'ai' . ($_ENV['SECRET_KEY'] ?? 'test_secret_key')
                )
            ];
        }

        // === STEP 1: Check cookie existence ===
        if (!isset($_COOKIE[$cookieName])) {
            return $this->respond($E['ERR_ID_900']); // Unauthorized
        }

        // === STEP 2: Decode and verify token ===
        $data = Auth::verifyToken($_COOKIE[$cookieName]);
        error_log("JWT verification data: " . json_encode($data));
        if (!$data || empty($data['username'])) {
            return $this->respond($E['ERR_ID_901']); // Invalid or expired token
        }

        // === STEP 3: Ensure user exists in DB ===
        $user = $this->repo->getUserByUsername($data['username']);
        if (!$user) {
            return $this->respond($E['ERR_ID_902']); // User not found
        }

        // === STEP 4: Verify integrity hash ===
        $secret = $_ENV['SECRET_KEY'] ?? 'test_secret_key';
        $computedHash = hash(
            $this->constants['REQUEST_HASH_ALGO'] ?? 'sha256',
            $user->username . $user->role . $secret
        );

        if ($computedHash !== $data['userHash']) {
            return $this->respond($E['ERR_ID_903']); // Authorization failed
        }

        // ✅ STEP 5: Authentication successful
        return $data;
    }

    /**
     * Handles unified response logic depending on app_mode.
     *
     * @param array $error Error constant entry
     * @return array|null
     */
    private function respond(array $error): ?array {
        $payload = $this->err->send($error);

        if ($this->appMode === 'test') {
            // Return payload to allow PHPUnit assertions
            return $payload;
        }

        // Normal production behavior: output JSON
        header('Content-Type: application/json');
        echo json_encode($payload, JSON_PRETTY_PRINT);
        return null;
    }
}
