<?php
require_once __DIR__ . '/User.php';
use Symfony\Component\Yaml\Yaml;

/**
 * Class UserRepository
 *
 * Handles all user-related DB operations using queries defined in queries.yml.
 */
class UserRepository {
    private PDO $conn;
    private array $queries;

    /**
     * Constructor.
     * Loads all SQL queries from YAML configuration.
     *
     * @param PDO $db Active database connection
     */
    public function __construct(PDO $db) {
        $this->conn = $db;

        // ✅ Parse YAML and extract only the `data_manipulation -> users` section
        $yaml = Yaml::parseFile(__DIR__ . '/../config/queries.yml');
        $this->queries = $yaml['data_manipulation']['users'] ?? [];
    }

    /**
     * Fetch all users from the database.
     *
     * @return User[]
     */
    public function getAllUsers(): array {
        $stmt = $this->conn->query($this->queries['get_all']);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => new User($row), $rows);
    }

    /**
     * Fetch a single user by their ID.
     *
     * @param int $id
     * @return User|null
     */
    public function getUserById(int $id): ?User {
        $stmt = $this->conn->prepare($this->queries['get_by_id']);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? new User($row) : null;
    }

    /**
     * Fetch a single user by username.
     * Performs a JOIN between users and pii tables.
     *
     * @param string $username
     * @return User|null
     */
    public function getUserByUsername(string $username): ?User {
        $stmt = $this->conn->prepare($this->queries['get_by_username']);
        $stmt->execute(['username' => $username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null; // no user found
        }

        return new User($row);
    }

    /**
     * Create a new user — inserts into both pii and users tables.
     *
     * @param string $username
     * @param string $email
     * @param string $password
     * @return bool True on success
     * @throws Exception If username already exists
     */
    public function createUser(string $username, string $email, string $password): bool {
        // 🔹 Step 1: Check for duplicate username
        $stmt = $this->conn->prepare($this->queries['check_duplicate_username']);
        $stmt->execute(['username' => $username]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("USERNAME_EXISTS");
        }

        // 🔹 Step 2: Insert into pii
        $stmt = $this->conn->prepare($this->queries['create_pii']);
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt->execute([
            'username' => $username,
            'email' => $email,
            'password_hash' => $hash
        ]);
        $piiId = $this->conn->lastInsertId();

        // 🔹 Step 3: Insert into users (linked to pii_id)
        $stmt2 = $this->conn->prepare($this->queries['create_user']);
        return $stmt2->execute(['pii_id' => $piiId]);
    }

    /**
     * Delete user by ID.
     *
     * @param int $id
     * @return bool
     */
    public function deleteUser(int $id): bool {
        $stmt = $this->conn->prepare($this->queries['delete']);
        return $stmt->execute(['id' => $id]);
    }
}
