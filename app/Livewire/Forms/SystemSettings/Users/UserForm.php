<?php

namespace App\Livewire\Forms\SystemSettings\Users;

use App\Enums\UserAccountStatus;
use App\Models\User;
use App\Support\UserPasswordRules;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Form;

class UserForm extends Form
{
    public ?int $id = null;

    public string $login = '';

    #[Validate('required|string|max:255')]
    public ?string $first_name = null;

    #[Validate('nullable|string|max:255')]
    public ?string $last_name = null;

    public string $email = '';

    #[Validate('nullable|string|max:30')]
    public ?string $phone = null;

    #[Validate('nullable|string|max:255')]
    public ?string $image_path = null;

    public ?string $bitrix24_id = null;

    public string $account_status = UserAccountStatus::Active->value;

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
        return UserPasswordRules::changeRules($prefix);
    }

    public function passwordChangeMessages(string $prefix = ''): array
    {
        return UserPasswordRules::changeMessages($prefix);
    }

    public function rules()
    {
        $this->first_name = trim((string) $this->first_name);
        if ($this->first_name === '') {
            $this->first_name = null;
        }

        $id = $this->id ?: 'NULL';

        $passwordRules = 'nullable|string';
        $passwordConfirmationRules = 'nullable|string';

        return [
            'login' => "required|string|max:100|unique:users,login,{$id},id",
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => "required|email|max:255|unique:users,email,{$id},id",
            'phone' => 'nullable|string|max:30',
            'image_path' => 'nullable|string|max:255',
            'bitrix24_id' => "nullable|integer|min:1|unique:users,bitrix24_id,{$id},id",
            'account_status' => ['required', 'string', Rule::in($this->allowedAccountStatuses())],
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

    public function messages(): array
    {
        return [
            'email.required' => 'Поле email обязательно для заполнения',
            'email.email' => 'некорректный email',
            'email.max' => 'Поле email не должно превышать 255 символов',
            'email.unique' => 'Пользователь с таким email уже существует',
            'login.required' => 'Поле логин обязательно для заполнения',
            'first_name.required' => 'Поле имя обязательно для заполнения',
            'login.unique' => 'Пользователь с таким логином уже существует',
            'password.required' => 'Поле пароль обязательно для заполнения',
            'password.min' => 'слишком короткий пароль',
            'password.regex' => 'пароль должен состоять не менее чем из 6 символов и содержит латинские буквы и цифры',
            'password_confirmation.required' => 'Поле повторите пароль обязательно для заполнения',
            'password_confirmation.min' => 'слишком короткий пароль',
            'password_confirmation.same' => 'пароли не совпадают',
            'photo.max' => 'Можно загружать фото не больше 2 Мб',
            'photo.image' => 'Можно загружать только jpg, jpeg, png или gif',
            'photo.mimes' => 'Можно загружать только jpg, jpeg, png или gif',
            'bitrix24_id.integer' => 'ID в Битрикс24 должен быть числом',
            'bitrix24_id.min' => 'ID в Битрикс24 должен быть числом',
            'bitrix24_id.unique' => 'Этот ID Битрикс24 уже указан у другого пользователя',
            'account_status.in' => 'Этот статус нельзя выбрать',
        ];
    }

    /**
     * @return list<string>
     */
    private function allowedAccountStatuses(): array
    {
        $allowed = [
            UserAccountStatus::Active->value,
            UserAccountStatus::Inactive->value,
        ];

        if ($this->id === null) {
            return [UserAccountStatus::PendingEmail->value];
        }

        $existing = User::query()->find($this->id);
        if ($existing?->accountStatus() === UserAccountStatus::PendingEmail) {
            $allowed[] = UserAccountStatus::PendingEmail->value;
        }

        return $allowed;
    }

    public function mirrorNotificationFlag(): void
    {
        $this->enable_important_notifications = $this->enable_notifications;
    }

    public function guardPhoto(): void
    {
        if ($this->photo === null) {
            return;
        }

        try {
            $this->validateOnly('photo');
        } catch (ValidationException $exception) {
            $this->photo->delete();
            $this->photo = null;

            throw $exception;
        }
    }

    public function isEmailValid(): bool
    {
        $email = trim($this->email);

        return $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Заполнить форму из объекта пользователя User.
     */
    public function from(User $user): void
    {
        $this->id = $user->id ?? null;
        $this->login = $user->login ?? '';
        $this->first_name = $user->first_name ?? '';
        $this->last_name = $user->last_name ?? '';
        $this->email = $user->email ?? '';
        $this->phone = $user->phone ?? '';
        $this->image_path = $user->image_path ?? '';
        $this->bitrix24_id = $user->bitrix24_id !== null ? (string) $user->bitrix24_id : null;
        $this->account_status = $user->accountStatus()->value;
        $this->rate_id = $user->latestRate?->rateValue->rate_id ?? null;
        $this->role_id = isset($user->roles) && count($user->roles) ? ($user->roles[0]['id'] ?? null) : null;
        $this->enable_notifications = ($user->enable_notifications ?? true)
            && ($user->enable_important_notifications ?? true);
        $this->mirrorNotificationFlag();
        $this->clearPasswordFields();
    }

    /**
     * Подменяет account_status на is_active / email_verified_at для сохранения.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function applyAccountStatus(array $data, ?User $existing = null): array
    {
        unset($data['account_status']);

        $status = UserAccountStatus::tryFrom($this->account_status) ?? UserAccountStatus::Active;

        return array_merge($data, User::persistenceForAccountStatus($status, $existing));
    }
}
