<?php
require_once __DIR__ . '/../classes/GameSessionRepository.php';
require_once __DIR__ . '/../factories/GameStateFactory.php';

/**
 * Service layer that manages high-level game session operations.
 * Acts as a bridge between API routes (controllers) and repository (data layer).
 *
 * Responsibilities:
 * - Orchestrates creation, loading, saving, and ending of game sessions.
 * - Uses GameSessionRepository for database operations.
 * - Uses GameStateFactory for initializing default game states.
 */
class GameSessionService {
    private GameSessionRepository $repo;
    private GameStateFactory $factory;

    /**
     * Constructor initializes repository and factory dependencies.
     *
     * @param PDO $db Active PDO database connection.
     */
    public function __construct(PDO $db) {
        // Create repository for DB operations
        $repo = new GameSessionRepository($db);

        // Create factory for generating initial game state using config files
        $factory = new GameStateFactory(
            json_decode(file_get_contents(__DIR__ . '/../config/constants.json'), true),
            json_decode(file_get_contents(__DIR__ . '/../config/default_game_state.json'), true)
        );

        $this->repo = $repo;
        $this->factory = $factory;
    }

    /**
     * Create a new game session.
     *
     * @param int $hostId ID of the player who hosts the game.
     * @param array $players Array of player IDs participating in the session.
     * @param int $prize Game prize or reward value.
     *
     * @return int Newly created session ID.
     */
    public function createGame(int $hostId, array $players, int $prize): int {
        return $this->repo->createSession($hostId, $players, $prize);
    }

    /**
     * Load an existing game session from the database.
     *
     * @param int $sessionId ID of the game session to load.
     *
     * @return array|null Session data (decoded as associative array) or null if not found.
     */
    public function loadGame(int $sessionId): ?array {
        return $this->repo->getSessionById($sessionId);
    }

    /**
     * Save the current state of a running game session.
     *
     * @param int $sessionId ID of the session to update.
     * @param array $state Updated game state data.
     * @param int $turn Current player's turn index.
     *
     * @return bool True if update succeeded, false otherwise.
     */
    public function saveGame(int $sessionId, array $state, int $turn): bool {
        return $this->repo->updateState($sessionId, $state, $turn);
    }

    /**
     * Mark a session as ended and declare the winner.
     *
     * @param int $sessionId ID of the game session.
     * @param string $winner Player ID or name of the winner.
     *
     * @return bool True if session updated successfully, false otherwise.
     */
    public function endGame(int $sessionId, string $winner): bool {
        return $this->repo->endSession($sessionId, $winner);
    }
}
