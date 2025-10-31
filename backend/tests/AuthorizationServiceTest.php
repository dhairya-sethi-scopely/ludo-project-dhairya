<?php
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../classes/AuthorizationService.php';
require_once __DIR__ . '/../classes/ErrorResponder.php';

/**
 * ✅ Unit tests for AuthorizationService.
 * Uses real ErrorResponder in test mode (no exit).
 */
class AuthorizationServiceTest extends TestCase
{
    private AuthorizationService $authz;
    private ErrorResponder $responder;

    protected function setUp(): void
    {
        // app_mode = test in config.local.json ensures no exit() is called
        $this->authz = new AuthorizationService();
        $this->responder = new ErrorResponder();
    }

    #[Test]
    public function allowsUserWhenRoleIsAllowed(): void
    {
        $user = ['role' => 'player'];

        // No exception or return payload expected here
        $result = $this->authz->requireRoles($user, ['player', 'admin']);

        // ✅ In test mode, requireRoles() should just return null (no error)
        $this->assertNull($result, 'Player role should be allowed');
    }

    #[Test]
    public function deniesUserWhenRoleNotAllowed(): void
    {
        $user = ['role' => 'viewer'];

        // ❌ Should trigger an error payload in test mode
        $result = $this->authz->requireRoles($user, ['player', 'admin']);

        $this->assertIsArray($result, 'Should return error payload in test mode');
        $this->assertEquals('ERR_ID_907', $result['error_id']);
    }

    #[Test]
    public function allowsAdminAccess(): void
    {
        $user = ['role' => 'admin'];

        // ✅ Should be allowed with no payload returned
        $result = $this->authz->requireRoles($user, ['admin']);
        $this->assertNull($result, 'Admin should have access');
    }
}
