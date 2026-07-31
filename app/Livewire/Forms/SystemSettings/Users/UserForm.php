<?php

namespace App\Livewire\Forms\SystemSettings\Users;

use App\Models\User;
use Livewire\Attributes\Validate;
use Livewire\Form;

class UserForm extends Form
{
    public ?int $id = null;

    public string $login = '';

    #[Validate('nullable|string|max:255')]
    public ?string $first_name = null;

    #[Validate('nullable|string|max:255')]
    public ?string $last_name = null;

    public string $email = '';

    #[Validate('nullable|string|max:30')]
    public ?string $phone = null;

    #[Validate('nullable|string|max:255')]
    public ?string $image_path = null;

    #[Validate('nullable|string|max:255')]
    public ?string $megaplan_id = null;

    #[Validate('nullable|boolean')]
    public bool $is_active = true;

    public $photo = null;

    #[Validate('nullable|integer|exists:rates,id')]
    public ?int $rate_id = null;

    #[Validate('nullable|integer|exists:roles,id')]
    public ?int $role_id = null;

    #[Validate('nullable|boolean')]
    public bool $enable_important_notifications = true;

    #[Validate('nullable|boolean')]
    public bool $enable_notifications = true;

    public ?string $current_password = null;

    public ?string $password = null;

    public ?string $password_confirmation = null;

    public bool $delete_photo = false;

    public function hasPasswordChange(): bool
    {
        return filled(trim((string) $this->current_password))
            || filled(trim((string) $this->password))
            || filled(trim((string) $this->password_confirmation));
    }

    public function clearPasswordFields(): void
    {
        $this->current_password = null;
        $this->password = null;
        $this->password_confirmation = null;
    }

    public function passwordChangeRules(string $prefix = ''): array
    {
        $passwordField = $prefix.'password';

        return [
            $prefix.'current_password' => [
                'required',
                'string',
            ],
            $prefix.'password' => [
                'required',
                'min:6',
                'regex:/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{6,}$/',
            ],
            $prefix.'password_confirmation' => [
                'required',
                'same:'.$passwordField,
            ],
        ];
    }

    public function passwordChangeMessages(string $prefix = ''): array
    {
        return [
            $prefix.'current_password.required' => 'Поле текущий пароль обязательно для заполнения',
            $prefix.'password.required' => 'Поле пароль обязательно для заполнения',
            $prefix.'password.min' => 'слишком короткий пароль',
            $prefix.'password.regex' => 'пароль должен состоять не менее чем из 6 символов и содержит латинские буквы и цифры',
            $prefix.'password_confirmation.required' => 'Поле повторите пароль обязательно для заполнения',
            $prefix.'password_confirmation.same' => 'пароли не совпадают',
        ];
    }

    public function rules()
    {
        $id = $this->id ?: 'NULL';

        $passwordRules = $this->id
            ? 'nullable|string'
            : ['required', 'string', 'min:6', 'regex:/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{6,}$/'];

        $passwordConfirmationRules = $this->id
            ? 'nullable|string'
            : 'required|string|min:6|same:password';

        return [
            'login' => "required|string|max:100|unique:users,login,{$id},id",
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => "required|email|max:255|unique:users,email,{$id},id",
            'phone' => 'nullable|string|max:30',
            'image_path' => 'nullable|string|max:255',
            'megaplan_id' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
            'rate_id' => 'nullable|integer|exists:rates,id',
            'role_id' => 'nullable|integer|exists:roles,id',
            'enable_important_notifications' => 'nullable|boolean',
            'enable_notifications' => 'nullable|boolean',
            'current_password' => 'nullable|string',
            'password' => $passwordRules,
            'password_confirmation' => $passwordConfirmationRules,
        ];
    }

    /**
     * Заполнить форму из объекта пользователя User.
     */
    public function from(User $user)
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
        $this->rate_id = $user->latestRate?->rateValue->rate_id ?? null;
        $this->role_id = isset($user->roles) && count($user->roles) ? ($user->roles[0]['id'] ?? null) : null;
        $this->enable_important_notifications = $user->enable_important_notifications ?? true;
        $this->enable_notifications = $user->enable_notifications ?? true;
        $this->clearPasswordFields();
    }
}
