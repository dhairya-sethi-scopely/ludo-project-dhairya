<?php
/**
 * MockPhpStream
 * --------------
 * Simulates php://input stream for testing API POST requests.
 */
class MockPhpStream {
    private static string $data = '';
    private $position = 0;

    public static function setData(string $data): void {
        self::$data = $data;
    }

    public function stream_open(): bool {
        $this->position = 0;
        return true;
    }

    public function stream_write($data) { return strlen($data); }

    public function stream_read(int $count): string {
        $chunk = substr(self::$data, $this->position, $count);
        $this->position += strlen($chunk);
        return $chunk;
    }

    public function stream_eof(): bool {
        return $this->position >= strlen(self::$data);
    }

    public function stream_stat(): array {
        return [];
    }
}
