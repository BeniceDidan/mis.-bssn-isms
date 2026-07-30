<?php

namespace Tests\Unit;

use App\Models\User;
use Tests\TestCase;

/**
 * Locks in the current 2-role model (admin/user, both can write, only
 * verification_status differs — see User::isAdmin()/canWrite()) so a
 * future role change is a deliberate decision, not a silent regression.
 */
class UserRoleTest extends TestCase
{
    public function test_admin_role_is_detected_correctly(): void
    {
        $admin = User::factory()->make(['role' => 'admin']);

        $this->assertTrue($admin->isAdmin());
    }

    public function test_non_admin_role_is_not_admin(): void
    {
        $user = User::factory()->make(['role' => 'user']);

        $this->assertFalse($user->isAdmin());
    }

    public function test_both_roles_can_currently_write(): void
    {
        $admin = User::factory()->make(['role' => 'admin']);
        $user = User::factory()->make(['role' => 'user']);

        $this->assertTrue($admin->canWrite());
        $this->assertTrue($user->canWrite());
    }
}
