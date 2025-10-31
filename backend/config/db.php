<?php
/**
 * Database Connection Class
 * -------------------------
 * Handles secure connection to the MySQL database using PDO.
 * 
 * This class encapsulates database credentials and creates
 * a reusable PDO connection instance with error handling.
 */

class Database
{
    /** @var string $host Database host (e.g. localhost) */
    private string $host;

    /** @var string $db_name Database name */
    private string $db_name;

    /** @var string $username Database username */
    private string $username;

    /** @var string $password Database password */
    private string $password;

    /** @var int $port Database port (default: 3306) */
    private int $port;

    /** @var PDO|null $conn PDO connection instance (nullable) */
    public ?PDO $conn = null;

    /**
     * Constructor
     * -----------
     * Initializes database configuration from the provided array.
     * 
     * @param array $config  Associative array containing:
     *                       [
     *                         'host' => string,
     *                         'db_name' => string,
     *                         'username' => string,
     *                         'password' => string,
     *                         'port' => int
     *                       ]
     */
    public function __construct(array $config)
    {
        $this->host     = $config['host'];
        $this->db_name  = $config['db_name'];
        $this->username = $config['username'];
        $this->password = $config['password'];
        $this->port     = $config['port'];
    }

    /**
     * Establish and Return PDO Connection
     * -----------------------------------
     * Creates a PDO connection using the provided configuration.
     * 
     * @return PDO|null Returns a valid PDO instance or null on failure.
     * 
     * @throws PDOException If connection fails.
     */
    public function getConnection(): ?PDO
    {
        try {
            // Build DSN (Data Source Name)
            $dsn = sprintf(
                "mysql:host=%s;dbname=%s;port=%d;charset=utf8mb4",
                $this->host,
                $this->db_name,
                $this->port
            );

            // Create PDO connection
            $this->conn = new PDO($dsn, $this->username, $this->password);

            // Set PDO attributes for better error handling
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        } catch (PDOException $e) {
            // Log error securely (don’t expose credentials)
            error_log("DB Connection failed: " . $e->getMessage());

            // In production, avoid revealing details to the client
            die(json_encode([
                "error"   => "Database connection failed.",
                "message" => "Please contact the administrator."
            ]));
        }

        return $this->conn;
    }
}
