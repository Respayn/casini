<?php

namespace App\Models;

use App\Enums\UserAccountStatus;
use App\Services\RoleHierarchyService;
use Database\Factories\UserFactory;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements CanResetPasswordContract
{
    /** @use HasFactory<UserFactory> */
    use CanResetPassword, HasFactory, HasRoles, Notifiable;

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
            'is_active' => 'boolean',
        ];
    }

    public function agencies(): BelongsToMany
    {
        return $this->belongsToMany(Agency::class);
    }

    public function accountStatus(): UserAccountStatus
    {
        if ($this->is_active) {
            return UserAccountStatus::Active;
        }

        return $this->email_verified_at === null
            ? UserAccountStatus::PendingEmail
            : UserAccountStatus::Inactive;
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
}
