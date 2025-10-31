<?php
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../classes/UserRepository.php';
require_once __DIR__ . '/../classes/User.php';

class UserRepositoryTest extends TestCase {

    private PDO $db;
    private UserRepository $repo;

    protected function setUp(): void {
    // ✅ SQLite in-memory DB for testing
    $this->db = new PDO('sqlite::memory:');
    $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ✅ Updated pii table schema
    $this->db->exec("
        CREATE TABLE pii (
            pii_id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE,
            email TEXT,
            password_hash TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // ✅ Updated users table schema
    $this->db->exec("
        CREATE TABLE users (
            user_id INTEGER PRIMARY KEY AUTOINCREMENT,
            pii_id INTEGER,
            role TEXT DEFAULT 'player',
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,  -- ✅ Added this line
            FOREIGN KEY(pii_id) REFERENCES pii(pii_id)
        );
    ");

    $this->repo = new UserRepository($this->db);
}



    #[Test]
    public function createsUserSuccessfully(): void {
        // TC-001 ✅ Normal creation
        $success = $this->repo->createUser('Player1', 'p1@example.com', 'secret');
        $this->assertTrue($success, 'User creation should succeed');

        $row = $this->db->query("SELECT * FROM pii WHERE username='Player1'")->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals('p1@example.com', $row['email']);
    }

    #[Test]
    public function preventsDuplicateUsernames(): void {
        // TC-002 ✅ Duplicate prevention
        $this->repo->createUser('Duplicate', 'd1@example.com', 'pass');
        $this->expectExceptionMessage('USERNAME_EXISTS');
        $this->repo->createUser('Duplicate', 'd2@example.com', 'pass');
    }

    #[Test]
    public function fetchesUserByUsername(): void {
        // TC-003 ✅ Fetch verification
        $this->repo->createUser('FetchUser', 'f@example.com', 'pw123');
        $user = $this->repo->getUserByUsername('FetchUser');
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('FetchUser', $user->username);
    }
}
