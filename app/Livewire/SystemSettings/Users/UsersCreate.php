<?php

namespace App\Livewire\SystemSettings\Users;

use App\Livewire\Forms\SystemSettings\Users\UserForm;
use App\Services\RateService;
use App\Services\UserService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.system-settings')]
class UsersCreate extends Component
{
    use WithFileUploads;

    public UserForm $form;
    public Collection $rates;
    public array $roles = [];

    public function boot(RateService $ratesService)
    {
        $this->rates = $ratesService->getRates(); // Получить список ставок для селекта
        // Получение ролей для выпадающего списка, если нужно
        // $this->roles = app(RoleService::class)->getAll(); // если используется сервис ролей
    }

    public function save(UserService $userService)
    {
        $this->validate();

        // Обработка фото
        if ($this->form->photo) {
            $path = $this->form->photo->store('user_photos', 'public');
            $this->form->image_path = $path;
        }

        $userData = [
            'login'      => $this->form->login,
            'name'       => $this->form->name,
            'first_name' => $this->form->first_name,
            'last_name'  => $this->form->last_name,
            'email'      => $this->form->email,
            'phone'      => $this->form->phone,
            'image_path' => $this->form->image_path,
            'megaplan_id'=> $this->form->megaplan_id,
            'is_active'  => $this->form->is_active,
            'rate_id'    => $this->form->rate_id,
            'role_id'    => $this->form->role_id,
            'enable_important_notifications' => $this->form->enable_important_notifications,
            'enable_notifications' => $this->form->enable_notifications,
            'password'   => $this->form->password,
        ];

        // Сохраняем пользователя через сервис
        $userService->create($userData);

        session()->flash('success', 'Пользователь успешно создан!');

        return redirect()->route('system-settings.system-settings.users');
    }

    public function getButtonDisabledProperty(): bool
    {
        $required = $this->form->login
            && $this->form->email
            && $this->form->role_id
            && $this->form->is_active !== null; // Для селекта обязательно строго !== null!

        // Если это создание пользователя — нужны пароли
        if (!isset($this->form->id)) {
            $required = $required
                && $this->form->password
                && $this->form->password_confirmation;
        }

        // disabled если НЕ заполнено
        return !$required;
    }

    public function render()
    {
        return view('livewire.system-settings.users.users-create', [
            'form' => $this->form,
            'rates' => $this->rates,
            'roles' => $this->roles,
        ]);
    }
}
