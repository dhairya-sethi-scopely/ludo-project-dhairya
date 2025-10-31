<?php
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../classes/AuthenticationMiddleware.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/UserRepository.php';
require_once __DIR__ . '/../classes/ErrorResponder.php';

class MockUserRepository extends UserRepository {
    public function __construct() {} // skip DB
    public function getUserByUsername(string $username): ?User {
        $mockData = [
            'user_id' => 1,
            'username' => $username,
            'email' => $username . '@example.com',
            'role' => 'player',
            'password_hash' => password_hash('password', PASSWORD_BCRYPT)
        ];
        return new User($mockData);
    }
}

class AuthenticationMiddlewareTest extends TestCase {
    private AuthenticationMiddleware $middleware;

    protected function setUp(): void {
        Auth::init('test_secret_key');
        $repo = new MockUserRepository();
        $this->middleware = new AuthenticationMiddleware($repo);
    }

    #[Test]
    public function authenticatesValidJwt(): void {
        $token = Auth::generateToken(1, 'PlayerTest', 'player');
        $_COOKIE['accessToken'] = $token;

        $result = $this->middleware->check();
        $this->assertIsArray($result);
        $this->assertEquals('PlayerTest', $result['username']);
    }

    #[Test]
    public function returnsErrorPayloadForInvalidToken(): void {
        $_COOKIE['accessToken'] = 'fake_invalid_token';

        $result = $this->middleware->check();  // now returns payload instead of exit
        $this->assertEquals('ERR_ID_901', $result['error_id']);
    }
}
