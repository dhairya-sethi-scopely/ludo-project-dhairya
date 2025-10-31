<?php
require_once __DIR__ . '/../classes/Auth.php';

/**
 * TestUtils
 * ----------
 * Shared helper methods for integration and interface tests.
 */
class TestUtils
{
    /**
     * Generate a valid JWT token for a mock player.
     */
    public static function makeJwt(string $username = 'PlayerAPI', string $role = 'player'): string
    {
        $_ENV['SECRET_KEY'] = $_ENV['SECRET_KEY'] ?? 'test_secret_key';
        Auth::init($_ENV['SECRET_KEY']);
        return Auth::generateToken(1, $username, $role);
    }

    /**
     * Create a standard test environment for all API tests.
     */
    public static function initEnv(): void
    {
        $_ENV['APP_MODE'] = 'test';
        $_ENV['SECRET_KEY'] = 'test_secret_key';
        putenv("APP_MODE=test");
        putenv("SECRET_KEY=test_secret_key");
    }
}
