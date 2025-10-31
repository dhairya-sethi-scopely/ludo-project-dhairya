<?php
use Symfony\Component\Yaml\Yaml;

/**
 * Repository for game session DB operations.
 * Handles conversion of PHP arrays ⇄ JSON in DB.
 */
class GameSessionRepository {
    private PDO $conn;
    private array $queries;
    private array $constants;
    private array $defaultState;

    /**
     * Constructor: loads all dependencies and parses YAML/JSON configs.
     */
    public function __construct(PDO $db) {
        $this->conn = $db;

        // Parse YAML once and keep only the game_sessions queries
        $yaml = Yaml::parseFile(__DIR__ . '/../config/queries.yml');
        $this->queries = $yaml['data_manipulation']['game_sessions'] ?? [];

        // Load constants and default state
        $this->constants = json_decode(file_get_contents(__DIR__ . '/../config/constants.json'), true);
        $this->defaultState = json_decode(file_get_contents(__DIR__ . '/../config/default_game_state.json'), true);
    }

    /**
     * Create a new game session.
     */
    public function createSession(int $hostId, array $players, int $prize): int {
        $factory = new GameStateFactory($this->constants, $this->defaultState);
        $state = $factory->createInitialState($players);

        $stmt = $this->conn->prepare($this->queries['create']);
        $stmt->execute([
            'host_id'     => $hostId,
            'players'     => json_encode($players),
            'turn'        => 0,
            'game_prize'  => $prize,
            'game_state'  => json_encode($state)
        ]);

        return (int) $this->conn->lastInsertId();
    }

    /**
     * Get session by ID.
     */
    public function getSessionById(int $id): ?array {
        $stmt = $this->conn->prepare($this->queries['get_by_id']);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;

        $row['players'] = json_decode($row['players'], true);
        $row['game_state'] = json_decode($row['game_state'], true);
        return $row;
    }

    /**
     * Update game state + turn.
     */
    public function updateState(int $id, array $state, int $turn): bool {
        $stmt = $this->conn->prepare($this->queries['update_state']);
        return $stmt->execute([
            'id'          => $id,
            'game_state'  => json_encode($state),
            'turn'        => $turn
        ]);
    }

    /**
     * End session with winner.
     */
    public function endSession(int $id, string $winner): bool {
        $stmt = $this->conn->prepare($this->queries['end_session']);
        return $stmt->execute([
            'id'      => $id,
            'winner'  => $winner
        ]);
    }

    /**
     * Get all sessions.
     */
    public function getAllSessions(): array {
        $stmt = $this->conn->query($this->queries['get_all']);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['players'] = json_decode($row['players'], true);
            $row['game_state'] = json_decode($row['game_state'], true);
        }
        return $rows;
    }

    /**
     * Delete session.
     */
    public function deleteSession(int $id): bool {
        $stmt = $this->conn->prepare($this->queries['delete']);
        return $stmt->execute(['id' => $id]);
    }

    // =====================================================
    // === MULTIPLAYER HELPER METHODS (YAML-based queries) ==
    // =====================================================

    /** Check if user already in a pending (inactive) session */
    public function hasPendingSession(int $userId): bool {
        $q = $this->queries['has_pending_session'] ?? null;
        if (!$q) return false;
        $stmt = $this->conn->prepare($q);
        $stmt->execute(['user_id' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['cnt'] ?? 0) > 0;
    }

    /** Find first open session (inactive + grace not expired) */
    public function findOpenSession(): ?array {
        $q = $this->queries['find_open_session'] ?? null;
        if (!$q) return null;

        $stmt = $this->conn->query($q);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Return full decoded session
        if ($result) {
            $result['players'] = json_decode($result['players'], true);
        }

        return $result ?: null;
    }


    /** Create new pending session for host */
    public function createPendingSession(int $hostId, int $minPlayers, int $graceSeconds): int {
        $q = $this->queries['create_pending_session'] ?? null;
        if (!$q) return 0;

        $graceUntil = date('Y-m-d H:i:s', time() + $graceSeconds);
        $players = json_encode([$hostId]); // ✅ must be JSON

        $stmt = $this->conn->prepare($q);
        $stmt->execute([
            'host_id'     => $hostId,
            'players'     => $players,
            'grace_until' => $graceUntil,
            'min_players' => $minPlayers
        ]);

        return (int) $this->conn->lastInsertId();
    }


    /** Append new player to existing session */
    public function addPlayerToSession(int $sessionId, int $userId): void {
    $q = $this->queries['add_player_to_session'] ?? null;
    if (!$q) return;
    $stmt = $this->conn->prepare($q);
    $stmt->execute([
        'user_id' => $userId,
        'id'      => $sessionId
    ]);
}


    /** Mark session as active */
    public function activateSession(int $sessionId): void {
        $q = $this->queries['activate_session'] ?? null;
        if (!$q) return;
        $stmt = $this->conn->prepare($q);
        $stmt->execute(['id' => $sessionId]);
    }

    /** Delete/expire a session */
    public function expireSession(int $sessionId): void {
        $q = $this->queries['expire_session'] ?? null;
        if (!$q) return;
        $stmt = $this->conn->prepare($q);
        $stmt->execute(['id' => $sessionId]);
    }

    /** Fetch full state for any session ID */
    public function getSessionState(int $sessionId): ?array {
        $q = $this->queries['get_session_state'] ?? null;
        if (!$q) return null;
        $stmt = $this->conn->prepare($q);
        $stmt->execute(['id' => $sessionId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
 * Initialize default PvP game state (tokens etc.) when a session first activates.
 */
    /**
 * Initializes game_state once both players have joined a multiplayer session.
 * Ensures only one initialization and activates the session.
 */
public function initializeGameStateIfEmpty(int $sessionId): void
{
    try {
        // 1️⃣ Fetch current state and players
        $stmt = $this->conn->prepare("
            SELECT players, game_state, is_active 
            FROM game_sessions 
            WHERE session_id = :sid
        ");
        $stmt->execute(['sid' => $sessionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            error_log("[INIT_STATE] ❌ Session not found (ID: {$sessionId})");
            return;
        }

        $players = json_decode($row['players'] ?? '[]', true);
        $state   = json_decode($row['game_state'] ?? 'null', true);
        $isActive = (int)($row['is_active'] ?? 0);

        // 2️⃣ Check conditions before initialization
        if (count($players) < 2) {
            error_log("[INIT_STATE] Waiting for second player (Session: {$sessionId})");
            return;
        }

        if (!empty($state) && $isActive === 1) {
            error_log("[INIT_STATE] Already initialized (Session: {$sessionId})");
            return;
        }

        // 3️⃣ Create fresh state
        $factory = new GameStateFactory($this->constants, $this->defaultState);
        $initialState = $factory->createInitialState($players);

        // 4️⃣ Save and activate session
        $update = $this->conn->prepare("
            UPDATE game_sessions
            SET game_state = :state, is_active = 1, updated_at = NOW()
            WHERE session_id = :sid
        ");
        $update->execute([
            'sid'   => $sessionId,
            'state' => json_encode($initialState, JSON_UNESCAPED_SLASHES),
        ]);

        error_log("[INIT_STATE] ✅ Initialized and activated session {$sessionId}");
    } catch (Throwable $t) {
        error_log("[INIT_STATE_ERROR] " . $t->getMessage());
    }
}

}
