<?php

namespace Tests\Unit\Support;

use App\Support\DefaultRole;
use Tests\TestCase;

class DefaultRoleTest extends TestCase
{
    public function test_granted_permission_names(): void
    {
        $this->assertSame([
            'read channels',
            'read statistics',
            'read reports',
            'read planning',
            'read clients and projects self',
        ], DefaultRole::grantedPermissionNames());
    }

    public function test_filter_permission_names_keeps_only_allowlist(): void
    {
        $filtered = DefaultRole::filterPermissionNames([
            'read channels',
            'edit channels',
            'full reports',
            'read planning',
            'read system settings',
        ]);

        $this->assertSame([
            'read channels',
            'read planning',
        ], $filtered);
    }

    public function test_is_read_editable(): void
    {
        $this->assertTrue(DefaultRole::isReadEditable('channels'));
        $this->assertTrue(DefaultRole::isReadEditable('clients and projects self'));
        $this->assertFalse(DefaultRole::isReadEditable('clients and projects all'));
        $this->assertFalse(DefaultRole::isReadEditable('system settings'));
    }
}
