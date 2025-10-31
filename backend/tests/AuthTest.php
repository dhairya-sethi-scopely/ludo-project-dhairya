<?php
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../classes/Auth.php';

//The tests verify token generation, payload integrity, signature validation, and expiry handling.
class AuthTest extends TestCase {

    private string $secretKey;

    protected function setUp(): void {
        // ✅ Use a mock secret key for testing
        $this->secretKey = 'test_secret_key_123';
        Auth::init($this->secretKey);
    }

    #[Test]
    public function generatesValidJwtString(): void {
        // TC-001 - this should confirms that the auth class actually returns syntactically valid JWT string

        $token = Auth::generateToken(1, 'PlayerTest', 'player');
        // The result is a string.
        $this->assertIsString($token, "JWT token should be a string");
        // The result contains at least one dot (.), because JWTs always have 3 parts separated by dots (header.payload.signature).
        $this->assertStringContainsString('.', $token, "JWT token should contain at least one '.'");
    }

    #[Test]
    public function decodesValidJwtSuccessfully(): void {
        // TC-002 -> Confirms that valid JWTs can be decoded correctly and that the payload isn’t corrupted.
        $token = Auth::generateToken(1, 'PlayerTest', 'player');
        $decoded = Auth::verifyToken($token);

        $this->assertIsArray($decoded, "Decoded data should be an array");
        $this->assertEquals(1, $decoded['sub']);
        $this->assertEquals('PlayerTest', $decoded['username']);
        $this->assertEquals('player', $decoded['role']);
    }

    #[Test]
    public function failsForTamperedToken(): void {
        // TC-003 -> Validates the integrity check — ensures tampered or fake JWTs are rejected.
        $token = Auth::generateToken(1, 'PlayerTest', 'player');
        // Tamper the token by changing one character
        $tampered = substr_replace($token, 'A', -10, 1);

        $decoded = Auth::verifyToken($tampered);
        $this->assertNull($decoded, "Tampered token should not be verified");
    }

    #[Test]
    public function containsExpectedPayloadKeys(): void {
        // TC-005 -> Ensures the Auth class consistently includes all necessary fields for user verification
        $token = Auth::generateToken(1, 'PlayerTest', 'player');
        $decoded = Auth::verifyToken($token);

        $this->assertArrayHasKey('sub', $decoded);
        $this->assertArrayHasKey('username', $decoded);
        $this->assertArrayHasKey('role', $decoded);
        $this->assertArrayHasKey('userHash', $decoded);
    }

    #[Test]
    public function failsForExpiredToken(): void {
        // TC-004
        // Generate token that expired 1 second ago
        $payload = [
            "sub" => 1,
            "username" => "ExpiredUser",
            "role" => "player",
            "iat" => time() - 100,
            "exp" => time() - 1,
            "userHash" => hash('sha256', "ExpiredUser" . "player" . $this->secretKey)
        ];

        // Encode manually using JWT class
        $token = Firebase\JWT\JWT::encode($payload, $this->secretKey, 'HS256');

        // Should fail verification
        $decoded = Auth::verifyToken($token);
        $this->assertNull($decoded, "Expired token should not be verified");
    }
}
