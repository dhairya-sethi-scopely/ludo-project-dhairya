<?php
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../services/WinConditionService.php';

class WinConditionServiceTest extends TestCase {

    private WinConditionService $service;

    protected function setUp(): void {
        $this->service = new WinConditionService();
    }

    #[Test]
    public function detectsWinWhenAllTokensAtHome(): void {
        $tokens = [
            ['position' => 'HOME'], ['position' => 'HOME'],
            ['position' => 'HOME'], ['position' => 'HOME']
        ];
        $this->assertTrue($this->service->checkWin($tokens));
    }

    #[Test]
    public function detectsNoWinWhenTokenOutsideHome(): void {
        $tokens = [
            ['position' => 'HOME'], ['position' => 'PATH'],
            ['position' => 'HOME'], ['position' => 'HOME']
        ];
        $this->assertFalse($this->service->checkWin($tokens));
    }
}
