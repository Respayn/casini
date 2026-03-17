<?php

namespace App\Models;

use App\Services\RoleHierarchyService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use App\Enums\Role;
use App\Enums\Department;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'first_name',
        'last_name',
        'is_active',
        'login',
        'email',
        'phone',
        'image_path',
        'megaplan_id',
        'enable_important_notifications',
        'enable_notifications',
        'email_verified_at',
        'password',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function agencies(): BelongsToMany
    {
        return $this->belongsToMany(Agency::class);
    }

    public function rateUser()
    {
        return $this->hasMany(RateUser::class, 'user_id');
    }

    public function latestRate()
    {
        return $this->hasOne(RateUser::class, 'user_id', 'id')->latestOfMany();
    }

    public function hasPermissionTo($permission, $guardName = null): bool
    {
        $roleHierarchyService = app(RoleHierarchyService::class);
        return $roleHierarchyService->userHasPermission($this, $permission);
    }

    public function can($abilities, $arguments = []): bool
    {
        $roleHierarchyService = app(RoleHierarchyService::class);
        return $roleHierarchyService->userHasPermission($this, $abilities);
    }

    public function isManager(): bool
    {
        return $this->roles->where('use_in_managers_list', true)->isNotEmpty();
    }

    public function isSpecialist(): bool
    {
        return $this->roles->where('use_in_specialist_list', true)->isNotEmpty();
    }

    public function paymentOperations(): HasMany
    {
        return $this->hasMany(PaymentOperation::class, 'manager_id');
    }

    public function projects()
    {
        if ($this->hasRole(Role::ADMIN)) {
            return Project::query();
        }

        if ($this->hasAnyRole([Role::SEO_SPECIALIST, Role::CA_SPECIALIST])) {
            return $this->projectsAsSpecialist();
        }

        if ($this->hasRole(Role::MANAGER)) {
            return $this->projectsAsManager();
        }

        if ($this->hasRole(Role::MANAGER_DEPARTMENT_HEAD)) {
            return Project::whereNotNull('manager_id');
        }

        // Руководители SEO/КР — только проекты своего департамента
        if ($this->hasRole(Role::SEO_DEPARTMENT_HEAD)) {
            return Project::where('department_id', Department::SEO->value);
        }

        if ($this->hasRole(Role::CA_DEPARTMENT_HEAD)) {
            return Project::where('department_id', Department::CA->value);
        }

        return Project::query()->whereRaw('1 = 0');
    }


    protected function fullName(): Attribute
    {
        return new Attribute(
            get: fn (mixed $value, array $attributes) => $attributes['first_name'] . ' ' . $attributes['last_name']
        );
    }
}
