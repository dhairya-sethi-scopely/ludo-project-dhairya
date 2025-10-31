<?php
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../services/TurnManager.php';

class TurnManagerTest extends TestCase {

    private TurnManager $manager;

    protected function setUp(): void {
        $this->manager = new TurnManager();
    }

    #[Test]
    public function switchesToNextPlayerOnNonSix(): void {
        // TC-001
        $players = [12, 13];
        $next = $this->manager->getNextTurn(0, 4, $players); // current=0, dice=4
        $this->assertEquals(1, $next, "Turn should switch to next player on non-6 dice roll");
    }

    #[Test]
    public function staysSamePlayerOnSix(): void {
        // TC-002
        $players = [12, 13];
        $next = $this->manager->getNextTurn(0, 6, $players); // current=0, dice=6
        $this->assertEquals(0, $next, "Turn should stay with same player on rolling a 6");
    }

    #[Test]
    public function wrapsBackToFirstAfterLastPlayer(): void {
        // TC-003
        $players = [12, 13, 14];
        $next = $this->manager->getNextTurn(2, 2, $players); // current=2, dice=2
        $this->assertEquals(0, $next, "Turn should wrap back to first player after the last");
    }

    #[Test]
    public function singlePlayerAlwaysKeepsTurn(): void {
        // TC-004
        $players = [42]; // only one player
        $next = $this->manager->getNextTurn(0, 4, $players);
        $this->assertEquals(0, $next, "Single player should always keep turn");
    }
}