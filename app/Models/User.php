<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

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
        'name',
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

    public function agencies()
    {
        return $this->belongsToMany(
            \App\Models\AgencySetting::class,
            'agency_admins',
            'user_id',
            'agency_id'
        )->withTimestamps();
    }

    public function rateUser()
    {
        return $this->hasMany(\App\Models\RateUser::class, 'user_id');
    }
}
