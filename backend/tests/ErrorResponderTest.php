<?php
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../classes/ErrorResponder.php';

/**
 * ✅ Unit tests for the real ErrorResponder class.
 * Works in test mode without using exit().
 */
class ErrorResponderTest extends TestCase
{
    private ErrorResponder $err;
    private array $E;

    protected function setUp(): void
    {
        // Ensure config.local.json has "app_mode": "test"
        $this->err = new ErrorResponder();
        $constants = json_decode(file_get_contents(__DIR__ . '/../config/constants.json'), true);
        $this->E = $constants['ERRORS'];
    }

    #[Test]
    public function returnsProperJsonStructure(): void
    {
        // TC-001 ✅ Verifies structure and fields
        $error = $this->E['ERR_ID_900']; // Unauthorized
        $response = $this->err->send($error); // returns array in test mode

        $this->assertIsArray($response, 'Should return array payload in test mode');
        $this->assertArrayHasKey('error_id', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertEquals('ERR_ID_900', $response['error_id']);
    }

    #[Test]
    public function includesExtraDetailsWhenProvided(): void
    {
        // TC-002 ✅ Ensures additional context is included
        $error = $this->E['ERR_ID_901']; // Invalid token
        $extra = ['reason' => 'JWT expired'];

        $response = $this->err->send($error, $extra);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('details', $response);
        $this->assertEquals('JWT expired', $response['details']['reason']);
    }

    #[Test]
    public function defaultsToUnknownErrorIfNoData(): void
    {
        // TC-003 ✅ Fallback logic test
        $response = $this->err->send([]);

        $this->assertEquals('ERR_ID_910', $response['error_id']);
        $this->assertEquals(500, $response['error_code']);
        $this->assertEquals('Unexpected server error', $response['message']);
    }

    #[Test]
    public function returnsCorrectErrorCodeAndMessage(): void
    {
        // TC-004 ✅ Validates code and message consistency
        $error = $this->E['ERR_ID_904']; // Invalid credentials
        $response = $this->err->send($error);

        $this->assertEquals(904, $response['error_code']);
        $this->assertStringContainsString('Invalid credentials', $response['message']);
    }
}
