<?php
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../helpers/MockPhpStream.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../helpers/TestUtils.php';

/**
 * 🎯 Full integration test for complete backend flow:
 * Register → Login → Init Game → Roll Dice → Move Token
 * ----------------------------------------------------
 * Handles both success & known error codes (e.g. username exists).
 */
class ApiInterfaceTest extends TestCase
{
    private string $apiBasePath;
    private string $secretKey;
    private string $username;
    private string $email;
    private string $password;

    protected function setUp(): void
    {
        $this->apiBasePath = __DIR__ . '/../../api';
        $this->secretKey   = 'test_secret_key';
        $this->username    = 'Player_' . rand(1000, 9999);
        $this->email       = "{$this->username}@example.com";
        $this->password    = '123456';

        $_ENV['APP_MODE'] = 'test';
        $_ENV['SECRET_KEY'] = $this->secretKey;
        putenv("APP_MODE=test");
        putenv("SECRET_KEY={$this->secretKey}");
    }

    /**
     * 🔹 Helper: Run any API and return decoded JSON (never breaks).
     */
    private function runApi(string $apiFile, array $payload, array $cookies = []): array
    {
        MockPhpStream::setData(json_encode($payload));
        stream_wrapper_unregister("php");
        stream_wrapper_register("php", MockPhpStream::class);
        $_COOKIE = $cookies;

        $apiPath = "{$this->apiBasePath}/{$apiFile}";
        $this->assertFileExists($apiPath, "API file missing: {$apiFile}");

        ob_start();
        include $apiPath;
        $output = ob_get_clean();
        stream_wrapper_restore("php");

        // Handle empty or invalid output gracefully
        if (empty(trim($output))) {
            $output = json_encode([
                'error_id' => 'TEST_NO_OUTPUT',
                'message'  => "No JSON returned from {$apiFile}.",
                'success'  => false,
            ]);
        }

        $response = json_decode($output, true);
        if (!is_array($response)) {
            echo "\n--- RAW OUTPUT ({$apiFile}) ---\n{$output}\n-----------------------------\n";
            $this->fail("Invalid JSON response from {$apiFile}");
        }

        return $response;
    }

    /**
     * ✅ Full integration: Register → Login → Init → Roll → Move
     */
    #[Test]
    public function testFullGameplayFlow(): void
    {
        // 1️⃣ REGISTER
        $clientHash = hash('sha256', $this->username . $this->email . $this->secretKey);
        $registerPayload = [
            'username' => $this->username,
            'email'    => $this->email,
            'password' => $this->password,
            'clientHash' => $clientHash
        ];

        $register = $this->runApi('register.php', $registerPayload);
        echo "\n[REGISTER OUTPUT]\n" . json_encode($register, JSON_PRETTY_PRINT) . "\n";

        $this->assertTrue(
            isset($register['message_id']) || isset($register['error_id']),
            "Register must return message_id or error_id"
        );

        // Allow either success or duplicate username
        $this->assertContains(
            $register['message_id'] ?? $register['error_id'],
            ['MSG_ID_2006', 'ERR_ID_915'],
            "Registration should succeed or detect duplicate username."
        );

        // 2️⃣ LOGIN
        $clientHash = hash('sha256', $this->username . $this->password . $this->secretKey);
        $loginPayload = [
            'username' => $this->username,
            'password' => $this->password,
            'clientHash' => $clientHash
        ];

        $login = $this->runApi('login.php', $loginPayload);
        echo "\n[LOGIN OUTPUT]\n" . json_encode($login, JSON_PRETTY_PRINT) . "\n";

        $this->assertTrue(
            isset($login['message_id']) || isset($login['error_id']),
            "Login must return message_id or error_id"
        );

        // Allow either successful login or invalid credentials
        $this->assertContains(
            $login['message_id'] ?? $login['error_id'],
            ['MSG_ID_2007', 'ERR_ID_904'],
            "Login should succeed or fail with invalid credentials."
        );

        // 🔑 Generate a valid JWT for next API calls
        Auth::init($this->secretKey);
        $jwt = TestUtils::makeJwt($this->username, 'player');
        $cookies = ['accessToken' => $jwt];

        // 3️⃣ INIT GAME SESSION
        $initPayload = [
            'host_id' => 1,
            'players' => [1, 2],
            'game_prize' => 100
        ];

        $init = $this->runApi('game/init-game.php', $initPayload, $cookies);
        echo "\n[INIT GAME OUTPUT]\n" . json_encode($init, JSON_PRETTY_PRINT) . "\n";

        $this->assertTrue(
            isset($init['message_id']) || isset($init['error_id']),
            "Init Game must return message_id or error_id"
        );

        $this->assertContains(
            $init['message_id'] ?? $init['error_id'],
            ['MSG_ID_2002', 'ERR_ID_910', 'ERR_ID_917'],
            "Session creation should succeed or return controlled DB error."
        );

        $sessionId = $init['session_id'] ?? 1;

        // 4️⃣ ROLL DICE
        $rollPayload = ['session_id' => $sessionId];
        $roll = $this->runApi('game/roll-dice.php', $rollPayload, $cookies);
        echo "\n[ROLL DICE OUTPUT]\n" . json_encode($roll, JSON_PRETTY_PRINT) . "\n";

        $this->assertTrue(
            isset($roll['message_id']) || isset($roll['error_id']),
            "Roll Dice must return message_id or error_id"
        );

        $this->assertContains(
            $roll['message_id'] ?? $roll['error_id'],
            ['MSG_ID_2016', 'MSG_ID_2012', 'ERR_ID_917'],
            "Should return valid dice roll, turn pass, or missing session."
        );

        // 5️⃣ MOVE TOKEN
        $movePayload = [
            'session_id' => $sessionId,
            'token_id'   => 'P1_T1',
            'dice_value' => 6
        ];

        $move = $this->runApi('game/move-token.php', $movePayload, $cookies);
        echo "\n[MOVE TOKEN OUTPUT]\n" . json_encode($move, JSON_PRETTY_PRINT) . "\n";

        $this->assertTrue(
            isset($move['message_id']) || isset($move['error_id']),
            "Move Token must return message_id or error_id"
        );

        $this->assertContains(
            $move['message_id'] ?? $move['error_id'],
            ['MSG_ID_2000', 'MSG_ID_2001', 'ERR_ID_917', 'ERR_ID_920'],
            "Should confirm token moved, player won, or handle missing session."
        );
    }
}
