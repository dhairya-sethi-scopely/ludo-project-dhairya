<?php
/**
 * Class User
 *
 * Represents a user with basic authentication and role information.
 */
class User
{
    /** @var int User ID (primary key) */
    public int $user_id;

    /** @var string Username */
    public string $username;

    /** @var string Email address */
    public string $email;

    /** @var string Password hash */
    public string $password_hash;

    /** @var string User role (default: player) */
    public string $role;

    /**
     * Constructor
     *
     * @param array $data User data array
     */
    public function __construct(array $data)
    {
        $this->user_id       = isset($data['user_id']) ? (int) $data['user_id'] : 0;
        $this->username      = $data['username'] ?? '';
        $this->email         = $data['email'] ?? '';
        $this->password_hash = $data['password_hash'] ?? '';
        $this->role          = $data['role'] ?? 'player';
    }
}
