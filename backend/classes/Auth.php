<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Load constants from JSON
$constants = json_decode(file_get_contents(__DIR__ . '/../config/constants.json'), true);

/**
 * Class Auth
 *
 * Handles JWT (JSON Web Token) generation and verification.
 * Uses a secret key to securely encode/decode tokens for authentication.
 */
class Auth {
    /** @var string Secret key used for signing JWT tokens */
    private static string $secretKey;

    /**
     * Initializes the Auth class with a secret key.
     *
     * @param string $key Secret key for signing and verifying JWTs
     */
    public static function init(string $key) {
        self::$secretKey = $key;
    }

    /**
     * Generates a signed JWT token for a user.
     *
     * Payload includes:
     * - sub: User ID
     * - username: User's username
     * - role: User's role
     * - userHash: Integrity check hash (username + role + secretKey)
     * - iat: Issued-at timestamp
     * - exp: Expiration timestamp (based on JWT_EXPIRY in config)
     *
     * @param int $userId User's unique ID
     * @param string $username User's username
     * @param string $role User's role (e.g., 'admin', 'player')
     * @return string Encoded JWT token
     */
    public static function generateToken(int $userId, string $username, string $role): string {
        // Load constants from JSON again to ensure fresh values
        $constants = json_decode(file_get_contents(__DIR__ . '/../config/constants.json'), true);

        // Integrity check hash to validate role and username in token
        $userHash = hash('sha256', $username . $role . self::$secretKey);

        // Token payload
        $payload = [
            "sub" => $userId,
            "username" => $username,
            "role" => $role,
            "userHash" => $userHash,
            "iat" => time(),                        // issued at
            "exp" => time() + $constants['JWT_EXPIRY'] // expiration time from JSON config
        ];

        // Encode JWT with secret key and algorithm from config
        return JWT::encode($payload, self::$secretKey, $constants['JWT_ALGO']);
    }

    /**
     * Verifies and decodes a JWT token.
     *
     * @param string $jwt The JWT token to verify
     * @return array|null Decoded token payload as an associative array, or null if verification fails
     */
    public static function verifyToken(string $jwt): ?array {
    try {
        $constants = json_decode(file_get_contents(__DIR__ . '/../config/constants.json'), true);
        $decoded = JWT::decode($jwt, new Key(self::$secretKey, $constants['JWT_ALGO']));
        file_put_contents(__DIR__ . '/../logs/jwt_debug.log', "JWT OK:\n" . print_r($decoded, true) . "\n", FILE_APPEND);
        return (array) $decoded;
    } catch (Exception $e) {
        file_put_contents(__DIR__ . '/../logs/jwt_debug.log', "JWT ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
        return null;
    }
}

}
