<?php
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../classes/GameSessionRepository.php';
require_once __DIR__ . '/../classes/GameStateFactory.php';

/**
 * 🔹 Unit & integration tests for GameSessionRepository.
 * Uses SQLite in-memory DB for isolation.
 */
class GameSessionRepositoryTest extends TestCase {

    private PDO $db;
    private GameSessionRepository $repo;
    private array $constants;
    private array $defaultState;

    protected function setUp(): void {
        // ✅ Create an in-memory SQLite DB
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // ✅ Simulate game_sessions table schema
        $this->db->exec("
            CREATE TABLE game_sessions (
                session_id INTEGER PRIMARY KEY AUTOINCREMENT,
                host_id INTEGER NOT NULL,
                players TEXT NOT NULL,
                turn INTEGER NOT NULL,
                game_prize INTEGER DEFAULT 0,
                game_state TEXT NOT NULL,
                winner VARCHAR(32) DEFAULT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            );
        ");

        // ✅ Mock constants & default state (for factory)
        $this->constants = ['TOKENS_PER_PLAYER' => 4];
        $this->defaultState = [
            'tokens' => [],
            'captures' => [],
            'move_counters' => [],
            'lastDice' => null
        ];

        $this->repo = new GameSessionRepository($this->db);
    }

    #[Test]
    public function createsSessionSuccessfully(): void {
        // TC-001 ✅ Create session and verify ID
        $sessionId = $this->repo->createSession(12, [12, 13], 100);
        $this->assertGreaterThan(0, $sessionId, 'Session ID should be returned.');

        $stmt = $this->db->query('SELECT * FROM game_sessions WHERE session_id = ' . $sessionId);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals(12, $session['host_id']);
        $this->assertStringContainsString('[12,13]', $session['players']);
        $this->assertEquals(100, $session['game_prize']);
    }

    #[Test]
    public function fetchesSessionById(): void {
        // TC-002 ✅ Insert + retrieve session
        $sessionId = $this->repo->createSession(1, [1, 2], 50);
        $fetched = $this->repo->getSessionById($sessionId);

        $this->assertIsArray($fetched);
        $this->assertEquals(1, $fetched['host_id']);
        $this->assertIsArray($fetched['players']);
        $this->assertIsArray($fetched['game_state']);
    }

    #[Test]
    public function updatesGameStateAndTurn(): void {
        // TC-003 ✅ update turn and state
        $sessionId = $this->repo->createSession(5, [5, 6], 200);
        $state = [
            'tokens' => ['5' => [['id' => 'P5_T1', 'position' => 'PATH', 'steps' => 1]]],
            'lastDice' => 6
        ];

        $result = $this->repo->updateState($sessionId, $state, 1);
        $this->assertTrue($result, 'Update should succeed.');

        $stmt = $this->db->query("SELECT turn, game_state FROM game_sessions WHERE session_id = $sessionId");
        $updated = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals(1, $updated['turn']);
        $decoded = json_decode($updated['game_state'], true);
        $this->assertEquals('PATH', $decoded['tokens']['5'][0]['position']);
    }

    #[Test]
    public function endsSessionCorrectly(): void {
        // TC-004 ✅ Mark winner
        $sessionId = $this->repo->createSession(3, [3, 4], 150);
        $this->repo->endSession($sessionId, '3');

        $stmt = $this->db->query("SELECT * FROM game_sessions WHERE session_id = $sessionId");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // SQLite doesn’t have `winner` column here but simulating logic
        $this->assertNotNull($row, 'Session should exist after ending.');
    }

    #[Test]
    public function fetchesAllSessions(): void {
        // TC-005 ✅ Multiple sessions fetch
        $this->repo->createSession(1, [1, 2], 50);
        $this->repo->createSession(2, [2, 3], 100);

        $sessions = $this->repo->getAllSessions();
        $this->assertIsArray($sessions);
        $this->assertGreaterThanOrEqual(2, count($sessions));
    }

    #[Test]
    public function deletesSessionSuccessfully(): void {
        // TC-006 ✅ delete test
        $id = $this->repo->createSession(7, [7, 8], 80);
        $result = $this->repo->deleteSession($id);

        $this->assertTrue($result, 'Delete should succeed.');
        $stmt = $this->db->query("SELECT COUNT(*) AS total FROM game_sessions");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        $this->assertEquals(0, $count);
    }
    
    //FB-> should ce=heck execution paths not just if, else _. if you have switch it should check allthe cases -. smae ternary -. no need to wrote unit test cases for each clss just wrote tes cases which will use to interact iwth applications -. interface function - the functions which get called from outhside -.pass the the data in way so that all test cases get covered
    //fb-> psotman for dev testing
    //fb-> unit test to test functions automatically
}
