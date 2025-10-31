<?php
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../helpers/MockPhpStream.php';
require_once __DIR__ . '/../../config/db.php';

class AIModeApiTest extends TestCase
{
    private string $initApi;
    private string $rollApi;

    protected function setUp(): void
    {
        $this->initApi = __DIR__ . '/../../api/game/init-game.php';
        $this->rollApi = __DIR__ . '/../../api/game/roll-dice.php';

        $_ENV['APP_MODE'] = 'test';
        $_ENV['SECRET_KEY'] = 'test_secret_key';
    }

    #[Test]
    public function createsAIGameSession(): void
    {
        $payload = [
            "host_id" => 1,
            "players" => [1],
            "game_prize" => 100,
            "mode" => "vs_ai"
        ];
        MockPhpStream::setData(json_encode($payload));
        stream_wrapper_unregister("php");
        stream_wrapper_register("php", MockPhpStream::class);

        ob_start();
        include $this->initApi;
        $res = json_decode(ob_get_clean(), true);

        $this->assertEquals('MSG_ID_2101', $res['message_id']);
        $this->assertArrayHasKey('session_id', $res);
        $this->assertTrue($res['success']);
    }

    #[Test]
    public function aiRollsDiceSuccessfully(): void
    {
        $payload = ["session_id" => 1];
        MockPhpStream::setData(json_encode($payload));

        ob_start();
        include $this->rollApi;
        $res = json_decode(ob_get_clean(), true);

        $this->assertContains($res['message_id'], ['MSG_ID_2016', 'MSG_ID_2012']);
    }
}
