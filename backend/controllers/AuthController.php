<?php
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/UserRepository.php';

/**
 * Class AuthController
 *
 * Handles user authentication requests: register, login, logout.
 * Refactored for test-safe behavior (no `exit` calls).
 */
class AuthController
{
    private UserRepository $repo;
    private array $E;
    private array $M;
    private array $C;

    public function __construct(UserRepository $repo)
    {
        $this->repo = $repo;

        $constants = json_decode(file_get_contents(__DIR__ . '/../config/constants.json'), true);
        $this->E = $constants['ERRORS'] ?? [];
        $this->M = $constants['MESSAGES'] ?? [];
        $this->C = $constants;
    }

    /**
     * ✅ Handles user registration.
     * Returns associative array instead of exiting.
     */
    public function register(): array
    {
        $data = json_decode(file_get_contents("php://input"), true);

        $username = $data['username'] ?? null;
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;
        $clientHash = $data['clientHash'] ?? null;

        if (!$username || !$email || !$password || !$clientHash) {
            return [
                "error_id" => $this->E['ERR_ID_905']['id'],
                "message" => $this->E['ERR_ID_905']['message']
            ];
        }

        $computedHash = hash($this->C['REQUEST_HASH_ALGO'], $username . $email . $_ENV['SECRET_KEY']);
        if ($computedHash !== $clientHash) {
            return [
                "error_id" => $this->E['ERR_ID_906']['id'],
                "message" => $this->E['ERR_ID_906']['message']
            ];
        }

        try {
            $this->repo->createUser($username, $email, $password);
            return [
                "message_id" => $this->M['MSG_ID_2006']['id'],
                "message" => $this->M['MSG_ID_2006']['message'],
                "success" => true
            ];
        } catch (Exception $e) {
            if ($e->getMessage() === "USERNAME_EXISTS") {
                return [
                    "error_id" => "ERR_ID_915",
                    "message" => "Username already exists"
                ];
            }
            return [
                "error_id" => $this->E['ERR_ID_910']['id'],
                "message" => $this->E['ERR_ID_910']['message'],
                "details" => ["detail" => $e->getMessage()]
            ];
        }
    }

    /**
     * ✅ Handles user login.
     * Returns data instead of echoing directly.
     */
    public function login(): array
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $username = $data['username'] ?? null;
        $password = $data['password'] ?? null;
        $clientHash = $data['clientHash'] ?? null;

        // === Step 1: Validate input ===
        if (!$username || !$password || !$clientHash) {
            return [
                "error_id" => $this->E['ERR_ID_905']['id'],
                "message" => $this->E['ERR_ID_905']['message']
            ];
        }

        // === Step 2: Validate signature ===
        $computedHash = hash(
            $this->C['REQUEST_HASH_ALGO'],
            $username . $password . $_ENV['SECRET_KEY']
        );
        if ($computedHash !== $clientHash) {
            return [
                "error_id" => $this->E['ERR_ID_906']['id'],
                "message" => $this->E['ERR_ID_906']['message']
            ];
        }

        // === Step 3: Fetch user and verify credentials ===
        $user = $this->repo->getUserByUsername($username);
        if (!$user || !password_verify($password, $user->password_hash)) {
            return [
                "error_id" => $this->E['ERR_ID_904']['id'],
                "message" => $this->E['ERR_ID_904']['message']
            ];
        }

        // === Step 4: Generate JWT ===
        try {
            $token = Auth::generateToken($user->user_id, $user->username, $user->role);
        } catch (Throwable $t) {
            return [
                "error_id" => $this->E['ERR_ID_910']['id'],
                "message" => "Token generation failed: " . $t->getMessage()
            ];
        }

        // === Step 5: Store JWT as HttpOnly cookie (modern attributes) ===
        $jwtConfig = json_decode(file_get_contents(__DIR__ . '/../config/constants.json'), true);

        setcookie(
            $jwtConfig['JWT_COOKIE_NAME'], // usually "accessToken"
            $token,
            [
                'expires'  => time() + ($jwtConfig['JWT_EXPIRY'] ?? 604800),
                'path'     => '/',
                'domain'   => '',          // leave blank for localhost
                'secure'   => false,       // must be false for http://localhost
                'httponly' => true,
                'samesite' => 'Lax'        // ✅ Lax allows same-origin navigation
            ]
        );


        // === Step 6: Return structured response ===
        return [
            "message_id" => $this->M['MSG_ID_2007']['id'] ?? "MSG_ID_2007",
            "message" => $this->M['MSG_ID_2007']['message'] ?? "Login successful.",
            "success" => true,
            // optional: include token only for debugging or mobile clients
            // "token" => $token
        ];
    }

    /**
     * ✅ Handles user logout.
     */
    public function logout(): array
    {
        setcookie(
            $this->C['JWT_COOKIE_NAME'],
            "",
            time() - 3600,
            $this->C['COOKIE_PATH']
        );

        return [
            "message_id" => $this->M['MSG_ID_2008']['id'] ?? "MSG_ID_2008",
            "message" => $this->M['MSG_ID_2008']['message'] ?? "Logged out successfully",
            "success" => true
        ];
    }
}
