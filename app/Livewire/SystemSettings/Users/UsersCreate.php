<?php

namespace App\Livewire\SystemSettings\Users;

use App\Livewire\Forms\SystemSettings\Users\UserForm;
use App\Services\RateService;
use App\Services\RoleService;
use App\Services\UserService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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

    public bool $buttonDisabled = true;

    public function boot(
        RateService $ratesService,
        RoleService $roleService
    )
    {
        $this->rates = $ratesService->getRates(); // Получить список ставок для селекта
        // Получение ролей для выпадающего списка, если нужно
        $this->roles = $roleService->getRoleOptions();
    }

    public function save(UserService $userService)
    {
        $this->validate();

        // TODO: Вынести в репозиторий
        if ($this->form->photo) {
            $this->form->image_path = $this->form->photo->store('user_photos', 'public');
        }

        $currentAgencyId = session('current_agency_id') ?? (auth()->user()->agency_id ?? null);

        $userData = [
            'login'      => $this->form->login,
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
            'agency_id' => $currentAgencyId,
        ];

        // Создаём пользователя через сервис
        $userService->create($userData);

        session()->flash('success', 'Пользователь успешно создан!');

        return redirect()->route('system-settings.users');
    }

    public function deletePhoto()
    {
        // Просто обнуляем фото в форме (Livewire сам удалит temporaryUrl)
        $this->form->photo = null;
        $this->form->image_path = null;
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
