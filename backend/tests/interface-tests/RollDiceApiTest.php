<?php
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../helpers/MockPhpStream.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/User.php';
require_once __DIR__ . '/../../classes/UserRepository.php';

/**
 * ✅ Minimal mock of UserRepository for test mode.
 * Prevents DB lookups by returning a fake user object.
 */
class MockUserRepository extends UserRepository {
    public function __construct() {}
    public function getUserByUsername(string $username): ?User {
        return new User([
            'user_id' => 1,
            'username' => $username,
            'email' => $username . '@example.com',
            'role' => 'player',
            'password_hash' => password_hash('password', PASSWORD_BCRYPT)
        ]);
    }
}

/**
 * 🎯 Integration Test: roll-dice.php
 * Ensures the dice roll API behaves correctly in test mode.
 */
class RollDiceApiTest extends TestCase
{
    private string $apiPath;
    private string $secretKey;

    protected function setUp(): void
    {
        $this->apiPath = __DIR__ . '/../../api/game/roll-dice.php';
        $this->secretKey = 'test_secret_key';

        // ✅ Force test mode globally
        $_ENV['APP_MODE'] = 'test';
        $_ENV['SECRET_KEY'] = $this->secretKey;
        putenv("APP_MODE=test");
        putenv("SECRET_KEY={$this->secretKey}");

        // ✅ Inject a mock repository globally
        $GLOBALS['userRepo'] = new MockUserRepository();

        // ✅ Register Mock stream for php://input
        stream_wrapper_unregister("php");
        stream_wrapper_register("php", MockPhpStream::class);
    }

    protected function tearDown(): void
    {
        stream_wrapper_restore("php");
        unset($_COOKIE['accessToken']);
    }

    /**
     * TC-001 ✅ Successful dice roll (authorized, valid payload)
     */
    #[Test]
    public function rollsDiceSuccessfully(): void
    {
        // Mock valid input
        $payload = ["session_id" => 1];
        MockPhpStream::setData(json_encode($payload));

        // Generate a valid JWT
        Auth::init($this->secretKey);
        $token = Auth::generateToken(1, 'TestPlayer', 'player');
        $_COOKIE['accessToken'] = $token;

        // Capture API output
        ob_start();
        include $this->apiPath;
        $rawOutput = ob_get_clean();

        echo "\n--- RAW OUTPUT ---\n{$rawOutput}\n------------------\n";

        $response = json_decode($rawOutput, true);
        $this->assertIsArray($response, "Response should be a valid JSON array");

        if (isset($response['error_id'])) {
            $this->fail("API returned error: {$response['message']} ({$response['error_id']})");
        }

        $this->assertArrayHasKey('message_id', $response, "Response must include message_id");
        $this->assertArrayHasKey('dice_value', $response);
        $this->assertArrayHasKey('success', $response);

        $this->assertContains(
            $response['message_id'],
            ['MSG_ID_2016', 'MSG_ID_2012'],
            "Should return valid dice roll or turn pass message"
        );
    }

    /**
     * TC-002 ⚠️ Missing session_id
     */
    #[Test]
    public function failsForMissingSessionId(): void
    {
        MockPhpStream::setData(json_encode([]));

        Auth::init($this->secretKey);
        $_COOKIE['accessToken'] = Auth::generateToken(1, 'TestPlayer', 'player');

        ob_start();
        include $this->apiPath;
        $output = ob_get_clean();

        echo "\n--- RAW OUTPUT (Missing Session) ---\n{$output}\n------------------\n";

        $response = json_decode($output, true);
        $this->assertIsArray($response, "Error response should be valid JSON");
        $this->assertArrayHasKey('error_id', $response);
        $this->assertEquals('ERR_ID_905', $response['error_id'], "Should fail due to missing session_id");
    }

    /**
     * TC-003 ⚠️ Unauthorized (no JWT cookie)
     */
    #[Test]
    public function failsWithoutJwtCookie(): void
    {
        MockPhpStream::setData(json_encode(['session_id' => 1]));
        unset($_COOKIE['accessToken']); // simulate missing JWT

        ob_start();
        include $this->apiPath;
        $output = ob_get_clean();

        echo "\n--- RAW OUTPUT (Unauthorized) ---\n{$output}\n------------------\n";

        $response = json_decode($output, true);
        $this->assertIsArray($response, "Unauthorized response should still be valid JSON");
        $this->assertArrayHasKey('error_id', $response);
        $this->assertEquals('ERR_ID_900', $response['error_id'], "Should return unauthorized error");
    }
}
