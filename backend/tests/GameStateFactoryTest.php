<?php
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../classes/GameStateFactory.php';

/**
 * 🔹 Unit tests for GameStateFactory.
 * Ensures that initial game state is generated correctly.
 */
class GameStateFactoryTest extends TestCase {

    private GameStateFactory $factory;
    private array $constants;
    private array $defaultState;

    protected function setUp(): void {
        // ✅ Simulate constants.json
        $this->constants = [
            'TOKENS_PER_PLAYER' => 4
        ];

        // ✅ Simulate default_game_state.json
        $this->defaultState = [
            'tokens' => [],
            'captures' => [],
            'move_counters' => [],
            'lastDice' => null
        ];

        $this->factory = new GameStateFactory($this->constants, $this->defaultState);
    }

    #[Test]
    public function createsTokensForAllPlayers(): void {
        // TC-001: ensures 4 tokens created per player
        $players = [12, 13];
        $state = $this->factory->createInitialState($players);

        $this->assertArrayHasKey('tokens', $state);
        $this->assertCount(4, $state['tokens'][12]);
        $this->assertCount(4, $state['tokens'][13]);
    }

    #[Test]
    public function initializesTokensInYard(): void {
        // TC-002: ensures all tokens start in "YARD"
        $players = [12];
        $state = $this->factory->createInitialState($players);

        foreach ($state['tokens'][12] as $token) {
            $this->assertEquals('YARD', $token['position']);
            $this->assertEquals(0, $token['steps']);
        }
    }

    #[Test]
    public function assignsUniqueTokenIds(): void {
        // TC-003: ensures unique IDs like P12_T1, P12_T2, etc.
        $players = [12];
        $state = $this->factory->createInitialState($players);

        $ids = array_column($state['tokens'][12], 'id');
        $this->assertEquals(['P12_T1', 'P12_T2', 'P12_T3', 'P12_T4'], $ids);
    }

    #[Test]
    public function initializesCountersAndCaptures(): void {
        // TC-004: ensures move counters and captures = 0
        $players = [12, 13];
        $state = $this->factory->createInitialState($players);

        $this->assertEquals(0, $state['move_counters'][12]);
        $this->assertEquals(0, $state['move_counters'][13]);
        $this->assertEquals(0, $state['captures'][12]);
        $this->assertEquals(0, $state['captures'][13]);
    }

    #[Test]
    public function maintainsDefaultKeysInState(): void {
        // TC-005: ensures existing default keys (like lastDice) remain intact
        $players = [10];
        $state = $this->factory->createInitialState($players);

        $this->assertArrayHasKey('lastDice', $state);
        $this->assertNull($state['lastDice']);
    }
}
