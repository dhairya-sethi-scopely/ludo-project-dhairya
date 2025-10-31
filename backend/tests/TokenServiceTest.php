<?php
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../services/TokenService.php';

/**
 * 🔹 Unit tests for TokenService.
 * Validates token movement, capture, and home rules.
 */
class TokenServiceTest extends TestCase {

    private TokenService $service;

    protected function setUp(): void {
        $this->service = new TokenService();
    }

    #[Test]
    public function movesTokenFromYardToPathOnSix(): void {
        // TC-001: Token leaves YARD when dice = 6
        $token = ['id' => 'P1_T1', 'position' => 'YARD', 'steps' => 0];
        $this->service->moveToken($token, 6);

        $this->assertEquals('PATH', $token['position']);
        $this->assertEquals(1, $token['steps']);
    }

    #[Test]
    public function movesTokenAlongPathNormally(): void {
        // TC-002: Token on PATH moves forward by dice value
        $token = ['id' => 'P1_T2', 'position' => 'PATH', 'steps' => 5];
        $this->service->moveToken($token, 3);

        $this->assertEquals('PATH', $token['position']);
        $this->assertEquals(8, $token['steps']);
    }

    #[Test]
    public function tokenReachesHomeAtFinalStep(): void {
        // TC-003: Token should move to HOME after 57 steps (Ludo board total path length)
        $token = ['id' => 'P1_T3', 'position' => 'PATH', 'steps' => 51];
        $this->service->moveToken($token, 1);

        $this->assertEquals('HOME', $token['position']);
        $this->assertEquals(7, $token['steps']);
    }

    #[Test]
    public function tokenCannotMoveBeyondHome(): void {
        // TC-004: Token stays at HOME even if dice would exceed 57
        $token = ['id' => 'P1_T4', 'position' => 'PATH', 'steps' => 57];
        $this->service->moveToken($token, 3);

        $this->assertEquals('PATH', $token['position'], 'Token should not move beyond home.');
        $this->assertEquals(51, $token['steps']);
    }

    #[Test]
    public function tokenCannotMoveFromYardWithoutSix(): void {
        // TC-005: If token is in YARD and dice != 6, it should not move
        $token = ['id' => 'P2_T1', 'position' => 'YARD', 'steps' => 0];
        $this->service->moveToken($token, 3);

        $this->assertEquals('YARD', $token['position']);
        $this->assertEquals(0, $token['steps']);
    }

    #[Test]
    public function detectsCaptureOfOpponentToken(): void {
        // TC-006: Token lands on same position as opponent (capture)
        $movingToken = ['id' => 'P1_T1', 'position' => 'PATH', 'steps' => 20];
        $opponents = [
            ['id' => 'P2_T1', 'position' => 'PATH', 'steps' => 20],
            ['id' => 'P2_T2', 'position' => 'PATH', 'steps' => 15]
        ];

        $result = $this->service->checkCapture($movingToken, $opponents);

        $this->assertTrue($result, 'Capture should be detected when steps match.');
    }

    #[Test]
    public function noCaptureWhenNoOpponentAtSameStep(): void {
        // TC-007: No capture if opponent not on same path step
        $movingToken = ['id' => 'P1_T3', 'position' => 'PATH', 'steps' => 25];
        $opponents = [
            ['id' => 'P2_T1', 'position' => 'PATH', 'steps' => 10],
            ['id' => 'P2_T2', 'position' => 'PATH', 'steps' => 30]
        ];

        $result = $this->service->checkCapture($movingToken, $opponents);

        $this->assertFalse($result, 'No capture expected.');
    }

    #[Test]
    public function resetsCapturedTokenToYard(): void {
        // TC-008: Captured token should return to YARD
        $capturedToken = ['id' => 'P2_T1', 'position' => 'PATH', 'steps' => 20];
        $this->service->resetTokenToYard($capturedToken);

        $this->assertEquals('YARD', $capturedToken['position']);
        $this->assertEquals(0, $capturedToken['steps']);
    }
}
