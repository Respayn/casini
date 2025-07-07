<?php

namespace App\Livewire\Forms\SystemSettings\Users;

use Livewire\Attributes\Validate;
use Livewire\Form;

class UserForm extends Form
{
    public ?int $id = null;

//    #[Validate('required|string|max:100|unique:users,login,{id}')]
    public string $login = '';

    #[Validate('nullable|string|max:255')]
    public ?string $first_name = null;

    #[Validate('nullable|string|max:255')]
    public ?string $last_name = null;

//    #[Validate('required|email|max:255|unique:users,email,{id}')]
    public string $email = '';

    #[Validate('nullable|string|max:30')]
    public ?string $phone = null;

    #[Validate('nullable|string|max:255')]
    public ?string $image_path = null;

    #[Validate('nullable|string|max:255')]
    public ?string $megaplan_id = null;

    #[Validate('nullable|boolean')]
    public bool $is_active = true;

    // Для загрузки фото
    public $photo = null; // файл

    #[Validate('nullable|integer|exists:rates,id')]
    public ?int $rate_id = null;

    // Роль (id или name)
    #[Validate('nullable|integer|exists:roles,id')]
    public ?int $role_id = null;

    // Статус уведомлений
    #[Validate('nullable|boolean')]
    public bool $enable_important_notifications = true;

    #[Validate('nullable|boolean')]
    public bool $enable_notifications = true;

    // Пароль (только для создания или ручной смены)
    #[Validate('nullable|string|min:8')]
    public ?string $password = null;

    #[Validate('nullable|string|min:8|same:password')]
    public ?string $password_confirmation = null;

    public bool $delete_photo = false;

    public function rules()
    {
        $id = $this->id ?: 'NULL';
        return [
            'login' => "required|string|max:100|unique:users,login,{$id},id",
            'first_name' => 'nullable|string|max:255',
            'last_name'  => 'nullable|string|max:255',
            'email' => "required|email|max:255|unique:users,email,{$id},id",
            'phone'   => 'nullable|string|max:30',
            'image_path' => 'nullable|string|max:255',
            'megaplan_id' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'photo'   => 'nullable|image|mimes:jpg,jpeg,png,gif|max:1024',
            'rate_id' => 'nullable|integer|exists:rates,id',
            'role_id' => 'nullable|integer|exists:roles,id',
            'enable_important_notifications' => 'nullable|boolean',
            'enable_notifications' => 'nullable|boolean',
            'password' => ($this->id ? 'nullable' : 'required') . '|string|min:8',
            'password_confirmation' => ($this->id ? 'nullable' : 'required') . '|string|min:8|same:password',
        ];
    }

    /**
     * Заполнить форму из объекта пользователя (UserData или User).
     */
    public function from($user)
    {
        $this->id = $user->id ?? null;
        $this->login = $user->login ?? '';
        $this->first_name = $user->first_name ?? '';
        $this->last_name = $user->last_name ?? '';
        $this->email = $user->email ?? '';
        $this->phone = $user->phone ?? '';
        $this->image_path = $user->image_path ?? '';
        $this->megaplan_id = $user->megaplan_id ?? '';
        $this->is_active = $user->is_active ?? true;
        $this->rate_id = $user->rate_id ?? null;
        $this->role_id = isset($user->roles) && count($user->roles) ? ($user->roles[0]['id'] ?? null) : null;
        $this->enable_important_notifications = $user->enable_important_notifications ?? true;
        $this->enable_notifications = $user->enable_notifications ?? true;
    }
}
