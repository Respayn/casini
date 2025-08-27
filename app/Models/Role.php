<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected function casts(): array
    {
        return [
            'use_in_project_filter' => 'boolean'
        ];
    }

    public function childRoles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_hierarchy', 'parent_id', 'child_id');
    }

    public function parentRoles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_hierarchy', 'child_id', 'parent_id');
    }
}
